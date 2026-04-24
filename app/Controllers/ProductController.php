<?php
namespace App\Controllers;

use App\Config\Database;
use App\Helpers\QRGenerator;
use App\Models\Magasin;
use App\Helpers\Auth;
use App\Helpers\Session;

class ProductController
{
    private $db;
    private $qrGenerator;
    private $currentMagasin;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->qrGenerator = new QRGenerator();
        $magasinModel = new Magasin();
        $this->currentMagasin = $magasinModel->getCurrentMagasin();
    }
    
    private function requireLogin()
    {
        if (!Auth::check()) {
            Session::setFlash('danger', 'Veuillez vous connecter');
            header('Location: ' . \BASE_PATH . '/index.php?action=login');
            exit;
        }
    }
    
    private function generateBarcode()
    {
        $prefix = '590';
        $timestamp = date('YmdHis');
        $random = str_pad(rand(0, 999), 3, '0', STR_PAD_LEFT);
        $barcode = $prefix . $timestamp . $random;
        
        $check = $this->db->fetchOne("SELECT id_produit FROM produits WHERE code_barre = ?", [$barcode]);
        if ($check) {
            return $this->generateBarcode();
        }
        
        return substr($barcode, 0, 13);
    }
    
    public function index()
    {
        $this->requireLogin();
        
        $magasinFilter = $this->currentMagasin ? " AND p.id_magasin = " . $this->currentMagasin['id_magasin'] : "";
        
        $products = $this->db->fetchAll(
            "SELECT p.*, c.nom_categorie FROM produits p 
             LEFT JOIN categories c ON p.id_categorie = c.id_categorie 
             WHERE p.est_actif = 1" . $magasinFilter . " 
             ORDER BY p.id_produit DESC"
        );
        
        $categories = $this->db->fetchAll("SELECT * FROM categories ORDER BY nom_categorie");
        
        $magasinModel = new Magasin();
        $userMagasins = $magasinModel->getMagasinsByUser($_SESSION['user_id']);
        $currentMagasin = $this->currentMagasin;
        $userName = $_SESSION['user_name'] ?? 'Utilisateur';
        
        // Données pour la vue
        $data = [
            'title' => 'Gestion des produits',
            'products' => $products,
            'categories' => $categories,
            'userMagasins' => $userMagasins,
            'currentMagasin' => $currentMagasin,
            'userName' => $userName
        ];
        
        // Démarrer la bufferisation
        ob_start();
        extract($data);
        include __DIR__ . '/../views/products/index.php';
        $content = ob_get_clean();
        
        // Inclure le layout principal
        include __DIR__ . '/../views/layouts/main.php';
    }
    
    public function add()
    {
        $this->requireLogin();
        
        $code_barre = trim($_POST['code_barre'] ?? '');
        if (empty($code_barre)) {
            $code_barre = $this->generateBarcode();
        }
        
        $nom_produit = trim($_POST['nom_produit'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $id_categorie = !empty($_POST['id_categorie']) ? $_POST['id_categorie'] : null;
        $prix_achat_ht = floatval($_POST['prix_achat_ht'] ?? 0);
        $prix_vente_ht = floatval($_POST['prix_vente_ht'] ?? 0);
        $tva = floatval($_POST['tva'] ?? 18);
        $stock_actuel = intval($_POST['stock_actuel'] ?? 0);
        $stock_minimum = intval($_POST['stock_minimum'] ?? 5);
        $stock_securite = intval($_POST['stock_securite'] ?? 10);
        $unite_mesure = trim($_POST['unite_mesure'] ?? 'pièce');
        $emplacement = trim($_POST['emplacement'] ?? '');
        $id_magasin = $this->currentMagasin ? $this->currentMagasin['id_magasin'] : null;
        
        $errors = [];
        if (empty($nom_produit)) $errors[] = "Le nom du produit est requis";
        if ($prix_achat_ht <= 0) $errors[] = "Le prix d'achat HT doit être supérieur à 0";
        if ($prix_vente_ht <= 0) $errors[] = "Le prix de vente HT doit être supérieur à 0";
        
        $check = $this->db->fetchOne(
            "SELECT id_produit FROM produits WHERE code_barre = ? AND id_magasin = ?",
            [$code_barre, $id_magasin]
        );
        if ($check) $errors[] = "Ce code-barres existe déjà dans ce magasin";
        
        if (!empty($errors)) {
            Session::setFlash('danger', implode('<br>', $errors));
            header('Location: ' . \BASE_PATH . '/index.php?action=products');
            exit;
        }
        
        try {
            // CORRECTION: Supprimer prix_vente_ttc de l'INSERT (colonne générée)
            $this->db->query(
                "INSERT INTO produits (code_barre, nom_produit, description, id_categorie, id_magasin, 
                 prix_achat_ht, prix_vente_ht, tva, stock_actuel, stock_minimum, 
                 stock_securite, unite_mesure, emplacement, est_actif, date_creation) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())",
                [$code_barre, $nom_produit, $description, $id_categorie, $id_magasin, 
                 $prix_achat_ht, $prix_vente_ht, $tva, $stock_actuel, 
                 $stock_minimum, $stock_securite, $unite_mesure, $emplacement]
            );
            
            Session::setFlash('success', 'Produit ajouté avec succès ! Code-barres: ' . $code_barre);
        } catch (\Exception $e) {
            Session::setFlash('danger', 'Erreur: ' . $e->getMessage());
        }
        
        header('Location: ' . \BASE_PATH . '/index.php?action=products');
        exit;
    }
    
    public function edit()
    {
        $this->requireLogin();
        
        $id = intval($_POST['id_produit'] ?? 0);
        $code_barre = trim($_POST['code_barre'] ?? '');
        $nom_produit = trim($_POST['nom_produit'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $id_categorie = !empty($_POST['id_categorie']) ? $_POST['id_categorie'] : null;
        $prix_achat_ht = floatval($_POST['prix_achat_ht'] ?? 0);
        $prix_vente_ht = floatval($_POST['prix_vente_ht'] ?? 0);
        $tva = floatval($_POST['tva'] ?? 18);
        $stock_actuel = intval($_POST['stock_actuel'] ?? 0);
        $stock_minimum = intval($_POST['stock_minimum'] ?? 5);
        $stock_securite = intval($_POST['stock_securite'] ?? 10);
        $unite_mesure = trim($_POST['unite_mesure'] ?? 'pièce');
        $emplacement = trim($_POST['emplacement'] ?? '');
        $id_magasin = $this->currentMagasin ? $this->currentMagasin['id_magasin'] : null;
        
        $errors = [];
        if (empty($code_barre)) $errors[] = "Le code-barres est requis";
        if (empty($nom_produit)) $errors[] = "Le nom du produit est requis";
        if ($prix_achat_ht <= 0) $errors[] = "Le prix d'achat HT doit être supérieur à 0";
        if ($prix_vente_ht <= 0) $errors[] = "Le prix de vente HT doit être supérieur à 0";
        
        $check = $this->db->fetchOne(
            "SELECT id_produit FROM produits WHERE code_barre = ? AND id_magasin = ? AND id_produit != ?",
            [$code_barre, $id_magasin, $id]
        );
        if ($check) $errors[] = "Ce code-barres est déjà utilisé par un autre produit dans ce magasin";
        
        if (!empty($errors)) {
            Session::setFlash('danger', implode('<br>', $errors));
            header('Location: ' . \BASE_PATH . '/index.php?action=products');
            exit;
        }
        
        try {
            // CORRECTION: Supprimer prix_vente_ttc de l'UPDATE (colonne générée)
            $this->db->query(
                "UPDATE produits SET code_barre=?, nom_produit=?, description=?, id_categorie=?, 
                 prix_achat_ht=?, prix_vente_ht=?, tva=?, stock_actuel=?, 
                 stock_minimum=?, stock_securite=?, unite_mesure=?, emplacement=? 
                 WHERE id_produit=? AND id_magasin=?",
                [$code_barre, $nom_produit, $description, $id_categorie, 
                 $prix_achat_ht, $prix_vente_ht, $tva, $stock_actuel, 
                 $stock_minimum, $stock_securite, $unite_mesure, $emplacement, $id, $id_magasin]
            );
            Session::setFlash('success', 'Produit modifié avec succès !');
        } catch (\Exception $e) {
            Session::setFlash('danger', 'Erreur: ' . $e->getMessage());
        }
        
        header('Location: ' . \BASE_PATH . '/index.php?action=products');
        exit;
    }
    
    public function delete()
    {
        $this->requireLogin();
        
        $id = intval($_GET['id'] ?? 0);
        $id_magasin = $this->currentMagasin ? $this->currentMagasin['id_magasin'] : null;
        
        try {
            $this->db->query(
                "UPDATE produits SET est_actif = 0 WHERE id_produit = ? AND id_magasin = ?",
                [$id, $id_magasin]
            );
            Session::setFlash('success', 'Produit supprimé avec succès !');
        } catch (\Exception $e) {
            Session::setFlash('danger', 'Erreur: ' . $e->getMessage());
        }
        
        header('Location: ' . \BASE_PATH . '/index.php?action=products');
        exit;
    }
    
    public function get()
    {
        $this->requireLogin();
        
        $id = intval($_GET['id'] ?? 0);
        $id_magasin = $this->currentMagasin ? $this->currentMagasin['id_magasin'] : null;
        
        $product = $this->db->fetchOne(
            "SELECT * FROM produits WHERE id_produit = ? AND id_magasin = ?",
            [$id, $id_magasin]
        );
        header('Content-Type: application/json');
        echo json_encode($product);
        exit;
    }
    
    public function scan()
    {
        $this->requireLogin();
        
        $barcode = trim($_GET['barcode'] ?? '');
        
        if (empty($barcode)) {
            header('Location: ' . \BASE_PATH . '/index.php?action=products');
            exit;
        }
        
        $id_magasin = $this->currentMagasin ? $this->currentMagasin['id_magasin'] : null;
        
        $product = $this->db->fetchOne(
            "SELECT * FROM produits WHERE code_barre = ? AND id_magasin = ? AND est_actif = 1",
            [$barcode, $id_magasin]
        );
        
        // Vue simple pour le scan
        $title = 'Scan produit';
        ob_start();
        ?>
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card text-center">
                        <div class="card-body">
                            <?php if ($product): ?>
                                <div class="text-success" style="font-size: 48px;">✅</div>
                                <h2>Produit trouvé !</h2>
                                <div class="alert alert-success">
                                    <strong>📦 Produit :</strong> <?php echo htmlspecialchars($product['nom_produit']); ?><br>
                                    <strong>🔢 Code-barres :</strong> <?php echo htmlspecialchars($product['code_barre']); ?><br>
                                    <strong>💰 Prix vente TTC :</strong> <?php echo number_format($product['prix_vente_ttc'], 0, ',', ' '); ?> G<br>
                                    <strong>📊 Stock :</strong> <?php echo $product['stock_actuel']; ?>
                                </div>
                            <?php else: ?>
                                <div class="text-danger" style="font-size: 48px;">❌</div>
                                <h2>Produit non trouvé</h2>
                                <div class="alert alert-danger">
                                    Code-barres: <?php echo htmlspecialchars($barcode); ?>
                                </div>
                            <?php endif; ?>
                            <a href="<?php echo \BASE_PATH; ?>/index.php?action=products" class="btn btn-primary">
                                ← Retour aux produits
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
        $content = ob_get_clean();
        include __DIR__ . '/../views/layouts/main.php';
        exit;
    }
}