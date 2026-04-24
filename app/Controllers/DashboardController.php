<?php
namespace App\Controllers;

use App\Config\Database;
use App\Models\Magasin;
use App\Helpers\Auth;
use App\Helpers\Session;

class DashboardController
{
    private $db;
    private $currentMagasin;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
        $magasinModel = new Magasin();
        $this->currentMagasin = $magasinModel->getCurrentMagasin();
    }
    
    private function requireLogin()
    {
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            header('Location: ' . \BASE_PATH . '/index.php?action=login');
            exit;
        }
    }
    
    public function index()
    {
        $this->requireLogin();
        
        $id_magasin = $this->currentMagasin ? $this->currentMagasin['id_magasin'] : null;
        
        // Ventes du jour
        $todaySales = $this->db->fetchOne("
            SELECT 
                COUNT(*) as total_ventes,
                COALESCE(SUM(montant_total_ttc), 0) as total_htg,
                COALESCE(SUM(CASE WHEN mode_paiement = 'cash' THEN montant_total_ttc ELSE 0 END), 0) as cash,
                COALESCE(SUM(CASE WHEN mode_paiement = 'card' THEN montant_total_ttc ELSE 0 END), 0) as card,
                COALESCE(SUM(CASE WHEN mode_paiement = 'mobile' THEN montant_total_ttc ELSE 0 END), 0) as mobile
            FROM ventes 
            WHERE DATE(date_vente) = CURDATE() 
            AND id_magasin = ?
        ", [$id_magasin]);
        
        // Ventes d'hier pour comparaison
        $yesterdaySales = $this->db->fetchOne("
            SELECT 
                COUNT(*) as total_ventes,
                COALESCE(SUM(montant_total_ttc), 0) as total_htg
            FROM ventes 
            WHERE DATE(date_vente) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)
            AND id_magasin = ?
        ", [$id_magasin]);
        
        // Calcul de l'évolution par rapport à hier
        $evolution = 0;
        if ($yesterdaySales['total_htg'] > 0) {
            $evolution = (($todaySales['total_htg'] - $yesterdaySales['total_htg']) / $yesterdaySales['total_htg']) * 100;
        }
        
        // Ventes du mois en cours
        $monthSales = $this->db->fetchOne("
            SELECT 
                COUNT(*) as total_ventes,
                COALESCE(SUM(montant_total_ttc), 0) as total_htg,
                COALESCE(AVG(montant_total_ttc), 0) as panier_moyen
            FROM ventes 
            WHERE MONTH(date_vente) = MONTH(CURDATE()) 
            AND YEAR(date_vente) = YEAR(CURDATE())
            AND id_magasin = ?
        ", [$id_magasin]);
        
        // Ventes du mois dernier pour comparaison
        $lastMonthSales = $this->db->fetchOne("
            SELECT 
                COALESCE(SUM(montant_total_ttc), 0) as total_htg
            FROM ventes 
            WHERE MONTH(date_vente) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))
            AND YEAR(date_vente) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))
            AND id_magasin = ?
        ", [$id_magasin]);
        
        // Évolution mensuelle
        $monthEvolution = 0;
        if ($lastMonthSales['total_htg'] > 0) {
            $monthEvolution = (($monthSales['total_htg'] - $lastMonthSales['total_htg']) / $lastMonthSales['total_htg']) * 100;
        }
        
        // Ventes des 7 derniers jours (inclut aujourd'hui)
        $weeklySalesData = $this->db->fetchAll("
            SELECT 
                DATE(date_vente) as date,
                COALESCE(SUM(montant_total_ttc), 0) as total
            FROM ventes 
            WHERE date_vente >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
            AND id_magasin = ?
            GROUP BY DATE(date_vente)
            ORDER BY date ASC
        ", [$id_magasin]);
        
        $weeklySales = [
            'labels' => [],
            'values' => []
        ];
        
        // Créer un tableau associatif pour un accès rapide
        $salesByDate = [];
        foreach ($weeklySalesData as $sale) {
            $salesByDate[$sale['date']] = (float)$sale['total'];
        }
        
        // Remplir les 7 derniers jours
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $label = date('d/m', strtotime($date));
            $weeklySales['labels'][] = $label;
            $weeklySales['values'][] = $salesByDate[$date] ?? 0;
        }
        
        // Top produits du mois
        $topProducts = $this->db->fetchAll("
            SELECT 
                p.nom_produit,
                SUM(dv.quantite) as quantite_vendue,
                SUM(dv.montant_total) as chiffre_affaires
            FROM details_ventes dv
            JOIN produits p ON dv.id_produit = p.id_produit
            JOIN ventes v ON dv.id_vente = v.id_vente
            WHERE v.id_magasin = ?
            AND MONTH(v.date_vente) = MONTH(CURDATE())
            AND YEAR(v.date_vente) = YEAR(CURDATE())
            GROUP BY p.id_produit, p.nom_produit
            ORDER BY quantite_vendue DESC
            LIMIT 5
        ", [$id_magasin]);
        
        // Statistiques stock
        $stockStats = $this->db->fetchOne("
            SELECT 
                COALESCE(SUM(prix_vente_ttc * stock_actuel), 0) as valeur_stock,
                COUNT(CASE WHEN stock_actuel <= stock_minimum AND stock_actuel > 0 THEN 1 END) as low_stock,
                COUNT(CASE WHEN stock_actuel = 0 THEN 1 END) as out_of_stock,
                COUNT(*) as total_products
            FROM produits 
            WHERE est_actif = 1 
            AND id_magasin = ?
        ", [$id_magasin]);
        
        // Dernières ventes - CORRECTION : Utiliser CONCAT pour le nom complet du client
        $recentSales = $this->db->fetchAll("
            SELECT 
                v.id_vente,
                v.numero_facture,
                v.date_vente,
                v.montant_total_ttc,
                v.mode_paiement,
                v.type_vente,
                v.statut,
                COALESCE(CONCAT(c.nom, ' ', c.prenom), 'Client en compte') as nom_client
            FROM ventes v
            LEFT JOIN clients c ON v.id_client = c.id_client
            WHERE v.id_magasin = ?
            ORDER BY v.date_vente DESC
            LIMIT 10
        ", [$id_magasin]);
        
        // Récupérer les magasins pour l'affichage
        $magasinModel = new Magasin();
        $userMagasins = $magasinModel->getMagasinsByUser($_SESSION['user_id']);
        $currentMagasin = $this->currentMagasin;
        $userName = $_SESSION['user_name'] ?? 'Utilisateur';
        
        // Données à passer à la vue
        $data = [
            'title' => 'Tableau de bord - Total Family',
            'todaySales' => $todaySales,
            'yesterdaySales' => $yesterdaySales,
            'evolution' => $evolution,
            'monthSales' => $monthSales,
            'monthEvolution' => $monthEvolution,
            'weeklySales' => $weeklySales,
            'topProducts' => $topProducts,
            'stockStats' => $stockStats,
            'recentSales' => $recentSales,
            'userMagasins' => $userMagasins,
            'currentMagasin' => $currentMagasin,
            'userName' => $userName
        ];
        
        // Démarrer la bufferisation pour capturer le contenu de la vue
        ob_start();
        
        $viewPath = __DIR__ . '/../Views/dashboard/index.php';
        if (file_exists($viewPath)) {
            extract($data);
            include $viewPath;
        } else {
            $this->renderDashboardContent($data);
        }
        
        $content = ob_get_clean();
        
        $layoutPath = __DIR__ . '/../Views/layouts/main.php';
        if (file_exists($layoutPath)) {
            extract($data);
            include $layoutPath;
        } else {
            $this->renderFallbackLayout($content, $data);
        }
    }
    
    /**
     * Rendre le contenu du tableau de bord directement (fallback)
     */
    private function renderDashboardContent($data)
    {
        extract($data);
        ?>
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="alert alert-info">
                        <h4><i class="fas fa-info-circle"></i> Tableau de bord</h4>
                        <p>Bienvenue <?php echo htmlspecialchars($userName); ?> !</p>
                        <hr>
                        <p>Bienvenue sur votre tableau de bord Total Family Multi-Services</p>
                    </div>
                    
                    <!-- Statistiques Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3 mb-3">
                            <div class="card stat-card bg-primary text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-0">Ventes du jour</h6>
                                            <h2 class="mb-0"><?php echo $todaySales['total_ventes'] ?? 0; ?></h2>
                                            <?php if (isset($evolution) && $evolution != 0): ?>
                                                <small class="text-white-50">
                                                    <?php echo $evolution > 0 ? '+' : ''; ?><?php echo round($evolution, 1); ?>% vs hier
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                        <i class="fas fa-shopping-cart fa-3x opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card stat-card bg-success text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-0">CA du jour</h6>
                                            <h2 class="mb-0"><?php echo number_format($todaySales['total_htg'] ?? 0, 0, ',', ' '); ?> G</h2>
                                        </div>
                                        <i class="fas fa-chart-line fa-3x opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card stat-card bg-info text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-0">CA du mois</h6>
                                            <h2 class="mb-0"><?php echo number_format($monthSales['total_htg'] ?? 0, 0, ',', ' '); ?> G</h2>
                                            <?php if (isset($monthEvolution) && $monthEvolution != 0): ?>
                                                <small class="text-white-50">
                                                    <?php echo $monthEvolution > 0 ? '+' : ''; ?><?php echo round($monthEvolution, 1); ?>% vs mois dernier
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                        <i class="fas fa-calendar-alt fa-3x opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card stat-card bg-warning text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-0">Stock bas</h6>
                                            <h2 class="mb-0"><?php echo $stockStats['low_stock'] ?? 0; ?></h2>
                                            <small class="text-white-50">
                                                <?php echo $stockStats['out_of_stock'] ?? 0; ?> produits en rupture
                                            </small>
                                        </div>
                                        <i class="fas fa-exclamation-triangle fa-3x opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Graphiques -->
                    <div class="row">
                        <div class="col-md-8 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i> Évolution des ventes (7 derniers jours)</h5>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container" style="height: 300px;">
                                        <canvas id="salesChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i> Répartition des paiements</h5>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container" style="height: 300px;">
                                        <canvas id="paymentChart"></canvas>
                                    </div>
                                    <div class="mt-4">
                                        <div class="d-flex justify-content-between mb-2">
                                            <span><i class="fas fa-circle text-primary"></i> Espèces</span>
                                            <span class="fw-bold"><?php echo number_format($todaySales['cash'] ?? 0, 0, ',', ' '); ?> G</span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span><i class="fas fa-circle text-success"></i> Carte</span>
                                            <span class="fw-bold"><?php echo number_format($todaySales['card'] ?? 0, 0, ',', ' '); ?> G</span>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <span><i class="fas fa-circle text-info"></i> Mobile Money</span>
                                            <span class="fw-bold"><?php echo number_format($todaySales['mobile'] ?? 0, 0, ',', ' '); ?> G</span>
                                        </div>
                                        <hr>
                                        <div class="d-flex justify-content-between">
                                            <span><strong>Total</strong></span>
                                            <span><strong><?php echo number_format($todaySales['total_htg'] ?? 0, 0, ',', ' '); ?> G</strong></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Top produits et Dernières ventes -->
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-trophy me-2"></i> Top 5 produits du mois</h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>#</th>
                                                    <th>Produit</th>
                                                    <th>Quantité vendue</th>
                                                    <th>Chiffre d'affaires</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($topProducts)): ?>
                                                <tr>
                                                    <td colspan="4" class="text-center text-muted">
                                                        <i class="fas fa-info-circle"></i> Aucune vente ce mois-ci
                                                    </td>
                                                </tr>
                                                <?php else: ?>
                                                <?php foreach ($topProducts as $index => $product): ?>
                                                <tr>
                                                    <td><?php echo $index + 1; ?></td>
                                                    <td><?php echo htmlspecialchars($product['nom_produit']); ?></td>
                                                    <td>
                                                        <span class="badge bg-primary"><?php echo $product['quantite_vendue']; ?></span>
                                                    </td>
                                                    <td class="fw-bold text-success">
                                                        <?php echo number_format($product['chiffre_affaires'], 0, ',', ' '); ?> G
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-history me-2"></i> Dernières ventes</h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Facture</th>
                                                    <th>Client</th>
                                                    <th>Date</th>
                                                    <th>Montant</th>
                                                    <th>Paiement</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($recentSales)): ?>
                                                <tr>
                                                    <td colspan="5" class="text-center text-muted">
                                                        <i class="fas fa-info-circle"></i> Aucune vente récente
                                                    </td>
                                                </tr>
                                                <?php else: ?>
                                                <?php foreach ($recentSales as $sale): ?>
                                                <tr>
                                                    <td>
                                                        <a href="<?php echo \BASE_PATH; ?>/index.php?action=view_sale&id=<?php echo $sale['id_vente']; ?>">
                                                            <?php echo htmlspecialchars($sale['numero_facture']); ?>
                                                        </a>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($sale['nom_client']); ?></td>
                                                    <td><?php echo date('d/m/Y H:i', strtotime($sale['date_vente'])); ?></td>
                                                    <td class="fw-bold"><?php echo number_format($sale['montant_total_ttc'], 0, ',', ' '); ?> G</td>
                                                    <td>
                                                        <?php
                                                        $badgeClass = 'secondary';
                                                        if ($sale['mode_paiement'] == 'cash') $badgeClass = 'success';
                                                        elseif ($sale['mode_paiement'] == 'card') $badgeClass = 'primary';
                                                        elseif ($sale['mode_paiement'] == 'mobile') $badgeClass = 'info';
                                                        ?>
                                                        <span class="badge bg-<?php echo $badgeClass; ?>">
                                                            <?php echo $sale['mode_paiement']; ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Graphique des ventes
            const salesCtx = document.getElementById('salesChart');
            if (salesCtx) {
                new Chart(salesCtx.getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: <?php echo json_encode($weeklySales['labels']); ?>,
                        datasets: [{
                            label: 'Ventes (G)',
                            data: <?php echo json_encode($weeklySales['values']); ?>,
                            borderColor: '#667eea',
                            backgroundColor: 'rgba(102, 126, 234, 0.1)',
                            borderWidth: 2,
                            tension: 0.4,
                            fill: true,
                            pointBackgroundColor: '#667eea',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            pointRadius: 4,
                            pointHoverRadius: 6
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: { position: 'top' },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return context.parsed.y.toLocaleString() + ' G';
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return value.toLocaleString() + ' G';
                                    }
                                }
                            }
                        }
                    }
                });
            }
            
            // Graphique des paiements
            const paymentCtx = document.getElementById('paymentChart');
            if (paymentCtx) {
                new Chart(paymentCtx.getContext('2d'), {
                    type: 'doughnut',
                    data: {
                        labels: ['Espèces', 'Carte', 'Mobile Money'],
                        datasets: [{
                            data: [
                                <?php echo $todaySales['cash'] ?? 0; ?>,
                                <?php echo $todaySales['card'] ?? 0; ?>,
                                <?php echo $todaySales['mobile'] ?? 0; ?>
                            ],
                            backgroundColor: ['#667eea', '#10b981', '#0ea5e9'],
                            borderWidth: 0,
                            hoverOffset: 10
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: { position: 'bottom' },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                        const percentage = ((context.raw / total) * 100).toFixed(1);
                                        return context.label + ': ' + context.raw.toLocaleString() + ' G (' + percentage + '%)';
                                    }
                                }
                            }
                        }
                    }
                });
            }
        });
        </script>
        <?php
    }
    
    /**
     * Rendre le layout de fallback si main.php n'existe pas
     */
    private function renderFallbackLayout($content, $data)
    {
        extract($data);
        ?>
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?php echo $title; ?></title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; }
                .header {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    padding: 15px 30px;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    flex-wrap: wrap;
                    gap: 15px;
                }
                .logo-container { display: flex; align-items: center; gap: 15px; }
                .logo { height: 50px; width: auto; border-radius: 10px; background: rgba(255,255,255,0.1); padding: 5px; }
                .company-name { font-size: 22px; font-weight: bold; margin: 0; }
                .company-name small { font-size: 12px; opacity: 0.9; display: block; }
                .user-info { display: flex; align-items: center; gap: 15px; flex-wrap: wrap; }
                .btn-link {
                    background: rgba(255,255,255,0.2);
                    padding: 8px 15px;
                    border-radius: 5px;
                    text-decoration: none;
                    color: white;
                    transition: background 0.3s;
                }
                .btn-link:hover { background: rgba(255,255,255,0.3); color: white; }
                .container-fluid { max-width: 1400px; margin: 0 auto; padding: 20px; }
                .card {
                    background: white;
                    border-radius: 10px;
                    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
                    margin-bottom: 20px;
                }
                .card-header { padding: 15px 20px; border-bottom: 1px solid #eee; font-weight: bold; }
                .card-body { padding: 20px; }
                .footer { text-align: center; padding: 20px; color: #666; margin-top: 40px; border-top: 1px solid #e0e0e0; }
                .bg-primary { background: linear-gradient(135deg, #667eea, #764ba2); }
                .bg-success { background: linear-gradient(135deg, #11998e, #38ef7d); }
                .bg-info { background: linear-gradient(135deg, #4facfe, #00f2fe); }
                .bg-warning { background: linear-gradient(135deg, #fa709a, #fee140); }
                .chart-container { position: relative; height: 300px; }
                .stat-card { transition: transform 0.3s; cursor: pointer; }
                .stat-card:hover { transform: translateY(-5px); }
            </style>
        </head>
        <body>
            <div class="header">
                <div class="logo-container">
                    <div class="company-name">
                        Total Family Multi-Services
                        <small>Votre partenaire auto de confiance</small>
                    </div>
                </div>
                <div class="user-info">
                    <span>👤 <?php echo htmlspecialchars($userName); ?></span>
                    <a href="<?php echo \BASE_PATH; ?>/index.php?action=dashboard" class="btn-link">
                        <i class="fas fa-tachometer-alt"></i> Tableau de bord
                    </a>
                    <a href="<?php echo \BASE_PATH; ?>/index.php?action=pos" class="btn-link">
                        <i class="fas fa-shopping-cart"></i> POS
                    </a>
                    <a href="<?php echo \BASE_PATH; ?>/index.php?action=logout" class="btn-link">
                        <i class="fas fa-sign-out-alt"></i> Déconnexion
                    </a>
                </div>
            </div>
            
            <?php echo $content; ?>
            
            <div class="footer">
                <p>&copy; <?php echo date('Y'); ?> Total Family Multi-Services. Tous droits réservés.</p>
            </div>
        </body>
        </html>
        <?php
    }
}