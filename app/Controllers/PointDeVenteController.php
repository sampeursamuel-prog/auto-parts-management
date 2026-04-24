<?php
namespace App\Controllers;

use App\Config\Database;
use App\Models\Magasin;
use App\Models\Devise;
use App\Models\Client;

class PointDeVenteController
{
    private $db;
    private $currentMagasin;
    private $deviseModel;
    private $clientModel;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
        $magasinModel = new Magasin();
        $this->currentMagasin = $magasinModel->getCurrentMagasin();
        $this->deviseModel = new Devise();
        $this->clientModel = new Client();
    }
    
    private function requireLogin()
    {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . \BASE_PATH . '/index.php?action=login');
            exit;
        }
    }
    
    private function calculateTotal($cart)
    {
        $total = 0;
        foreach ($cart as $item) {
            $total += $item['price'] * $item['quantity'];
        }
        return $total;
    }
    
    public function index()
    {
        $this->requireLogin();
        
        // Récupérer l'ID magasin depuis la session (mis à jour par le sélecteur)
        $sessionMagasinId = $_SESSION['current_magasin_id'] ?? null;
        
        // Si un magasin est sélectionné en session, le charger
        if ($sessionMagasinId) {
            $magasinModel = new Magasin();
            $this->currentMagasin = $magasinModel->getMagasinById($sessionMagasinId);
        }
        
        // Initialiser le panier si inexistant
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        
        $cart = $_SESSION['cart'];
        $total = $this->calculateTotal($cart);
        $tva = $total * 0.18;
        $totalTTC = $total + $tva;
        $userName = $_SESSION['user_name'] ?? 'Caissier';
        
        // Récupérer les devises
        $devises = $this->deviseModel->getAllDevises();
        $defaultDevise = $this->deviseModel->getDefaultDevise();
        $currentDevise = $_SESSION['current_devise'] ?? ($defaultDevise['code'] ?? 'HTG');
        
        $id_magasin = $this->currentMagasin ? $this->currentMagasin['id_magasin'] : null;
        
        // Récupérer les produits du magasin actif
        $products = [];
        if ($id_magasin) {
            $products = $this->db->fetchAll("
                SELECT id_produit, code_barre, nom_produit, prix_vente_ttc, stock_actuel 
                FROM produits 
                WHERE est_actif = 1 AND id_magasin = ? 
                ORDER BY nom_produit ASC
            ", [$id_magasin]);
        }
        
        // Récupérer les clients
        $clients = $this->db->fetchAll("
            SELECT id_client, nom, prenom, telephone, email, code_client 
            FROM clients 
            WHERE est_actif = 1 
            ORDER BY nom ASC
        ");
        
        // Récupérer les magasins de l'utilisateur pour le switch
        $magasinModel = new Magasin();
        $userMagasins = $magasinModel->getMagasinsByUser($_SESSION['user_id']);
        $currentMagasin = $this->currentMagasin;
        
        // Message si aucun magasin ou pas de produits
        $noProductsMessage = '';
        if (!$id_magasin) {
            $noProductsMessage = 'Aucun magasin sélectionné. Veuillez sélectionner un magasin.';
        } elseif (empty($products)) {
            $noProductsMessage = 'Aucun produit trouvé dans ce magasin. Veuillez ajouter des produits.';
        }
        
        // Utiliser le header unifié
        $title = 'Point de vente';
        $headerPath = __DIR__ . '/../views/layouts/header_unified.php';
        
        if (file_exists($headerPath)) {
            require_once $headerPath;
        } else {
            $this->renderLegacyHeader($userName, $currentMagasin);
        }
        ?>
        
        <div class="container-fluid">
            <!-- Sélecteur de magasin -->
            <div class="row mb-3">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body py-2">
                            <div class="row align-items-center">
                                <div class="col-md-2">
                                    <strong><i class="fas fa-store"></i> Magasin :</strong>
                                </div>
                                <div class="col-md-7">
                                    <select id="magasinSelector" class="form-select">
                                        <option value="">-- Sélectionnez un magasin --</option>
                                        <?php foreach ($userMagasins as $mag): ?>
                                        <option value="<?php echo $mag['id_magasin']; ?>" 
                                            <?php echo ($currentMagasin && $currentMagasin['id_magasin'] == $mag['id_magasin']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($mag['nom_magasin']); ?> 
                                            (<?php echo htmlspecialchars($mag['ville'] ?? ''); ?>)
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <button class="btn btn-primary btn-sm w-100" onclick="switchMagasin()">
                                        <i class="fas fa-sync-alt"></i> Changer de magasin
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php if ($noProductsMessage): ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i> <?php echo $noProductsMessage; ?>
            </div>
            <?php endif; ?>
            
            <div class="row">
                <!-- Panier -->
                <div class="col-md-5 mb-4">
                    <div class="card">
                        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-shopping-cart"></i> Panier en cours</h5>
                            <button class="btn btn-sm btn-danger" onclick="clearCart()">Vider</button>
                        </div>
                        <div class="card-body" style="max-height: 500px; overflow-y: auto;">
                            <div id="cartItems">
                                <?php if (empty($cart)): ?>
                                <p class="text-muted text-center">Aucun article dans le panier</p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="card-footer">
                            <div class="row mb-2">
                                <div class="col-12">
                                    <div class="d-flex justify-content-between">
                                        <span>Sous-total HT:</span>
                                        <span id="subtotal"><?php echo number_format($total, 0, ',', ' '); ?> <?php echo htmlspecialchars($currentDevise); ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span>TVA (18%):</span>
                                        <span id="tax"><?php echo number_format($tva, 0, ',', ' '); ?> <?php echo htmlspecialchars($currentDevise); ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <strong>Total TTC:</strong>
                                        <strong id="totalAmount" class="text-primary"><?php echo number_format($totalTTC, 0, ',', ' '); ?> <?php echo htmlspecialchars($currentDevise); ?></strong>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12">
                                    <button class="btn btn-success w-100" onclick="checkout()" id="checkoutBtn" <?php echo empty($cart) ? 'disabled' : ''; ?>>
                                        <i class="fas fa-check"></i> Valider la vente
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Liste des produits -->
                <div class="col-md-7 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <div class="row align-items-center">
                                <div class="col-md-4">
                                    <h5 class="mb-0"><i class="fas fa-boxes"></i> Catalogue produits</h5>
                                </div>
                                <div class="col-md-4">
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                                        <input type="text" id="searchProduct" class="form-control" placeholder="Rechercher..." onkeyup="searchProducts()">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-barcode"></i></span>
                                        <input type="text" id="barcodeInput" class="form-control" placeholder="Scanner code-barres..." autofocus>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-body" style="max-height: 500px; overflow-y: auto;">
                            <div class="row" id="productsList">
                                <?php if (empty($products)): ?>
                                <div class="col-12">
                                    <div class="alert alert-info text-center">
                                        <i class="fas fa-info-circle"></i> Aucun produit disponible dans ce magasin.
                                    </div>
                                </div>
                                <?php else: ?>
                                    <?php foreach ($products as $product): ?>
                                    <div class="col-md-6 col-lg-4 mb-3 product-item" 
                                         data-id="<?php echo $product['id_produit']; ?>"
                                         data-name="<?php echo htmlspecialchars($product['nom_produit']); ?>"
                                         data-price="<?php echo $product['prix_vente_ttc']; ?>"
                                         data-stock="<?php echo $product['stock_actuel']; ?>"
                                         data-barcode="<?php echo htmlspecialchars($product['code_barre']); ?>">
                                        <div class="card h-100 <?php echo $product['stock_actuel'] <= 0 ? 'border-danger' : ''; ?>">
                                            <div class="card-body text-center">
                                                <i class="fas fa-box fa-2x mb-2 text-primary"></i>
                                                <h6 class="card-title"><?php echo htmlspecialchars($product['nom_produit']); ?></h6>
                                                <p class="card-text">
                                                    <strong><?php echo number_format($product['prix_vente_ttc'], 0, ',', ' '); ?> <?php echo htmlspecialchars($currentDevise); ?></strong><br>
                                                    <small class="<?php echo $product['stock_actuel'] <= 5 ? 'text-danger' : 'text-muted'; ?>">
                                                        Stock: <?php echo $product['stock_actuel']; ?>
                                                    </small><br>
                                                    <small class="text-muted">
                                                        Code: <?php echo htmlspecialchars($product['code_barre']); ?>
                                                    </small>
                                                </p>
                                                <?php if ($product['stock_actuel'] > 0): ?>
                                                <button class="btn btn-sm btn-primary add-to-cart-btn">
                                                    <i class="fas fa-plus"></i> Ajouter
                                                </button>
                                                <?php else: ?>
                                                <button class="btn btn-sm btn-secondary" disabled>
                                                    <i class="fas fa-times"></i> Rupture
                                                </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Modal Paiement -->
        <div class="modal fade" id="paymentModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title"><i class="fas fa-credit-card"></i> Paiement de la vente</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label><i class="fas fa-user"></i> Client (optionnel)</label>
                                    <div class="input-group">
                                        <input type="text" id="clientSearchInput" class="form-control" placeholder="Rechercher par nom, téléphone ou email">
                                        <button class="btn btn-outline-primary" onclick="searchClient()"><i class="fas fa-search"></i></button>
                                        <button class="btn btn-outline-success" onclick="showNewClientForm()"><i class="fas fa-plus"></i></button>
                                    </div>
                                    <select id="clientId" class="form-control mt-2" size="5">
                                        <option value="">Client anonyme (vente au comptoir)</option>
                                        <?php foreach ($clients as $client): ?>
                                        <option value="<?php echo $client['id_client']; ?>">
                                            <?php echo htmlspecialchars($client['code_client'] . ' - ' . $client['nom'] . ' ' . ($client['prenom'] ?? '') . ' - ' . $client['telephone']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label><i class="fas fa-dollar-sign"></i> Devise</label>
                                    <select id="deviseSelector" class="form-control" onchange="switchDevise()">
                                        <?php foreach ($devises as $d): ?>
                                        <option value="<?php echo $d['code']; ?>" <?php echo $currentDevise == $d['code'] ? 'selected' : ''; ?>>
                                            <?php echo $d['code']; ?> (1 <?php echo $d['code']; ?> = <?php echo number_format($d['taux_htg'], 2); ?> HTG)
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label><i class="fas fa-money-bill"></i> Mode de paiement</label>
                                    <select id="paymentMode" class="form-control" onchange="updatePaymentFields()">
                                        <option value="cash">💰 Espèces</option>
                                        <option value="card">💳 Carte bancaire</option>
                                        <option value="mobile">📱 Mobile Money</option>
                                    </select>
                                </div>
                                <div class="mb-3" id="cashAmountDiv">
                                    <label>Montant reçu</label>
                                    <input type="number" id="cashAmount" class="form-control" placeholder="Montant reçu du client" step="1">
                                    <small id="changeAmount" class="text-success"></small>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3 text-center">
                            <label><strong>Total à payer</strong></label>
                            <h2 id="modalTotal" class="text-primary">0 <?php echo htmlspecialchars($currentDevise); ?></h2>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="button" class="btn btn-success" onclick="processPayment()">Confirmer le paiement</button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Modal Nouveau Client -->
        <div class="modal fade" id="newClientModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title"><i class="fas fa-user-plus"></i> Nouveau client</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="newClientForm">
                            <div class="mb-3">
                                <label>Nom *</label>
                                <input type="text" name="nom" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label>Prénom</label>
                                <input type="text" name="prenom" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label>Téléphone</label>
                                <input type="tel" name="telephone" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label>Email</label>
                                <input type="email" name="email" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label>Adresse</label>
                                <textarea name="adresse" class="form-control"></textarea>
                            </div>
                            <div class="mb-3">
                                <label>Type de client</label>
                                <select name="type_client" class="form-control">
                                    <option value="particulier">Particulier</option>
                                    <option value="professionnel">Professionnel</option>
                                    <option value="entreprise">Entreprise</option>
                                </select>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="button" class="btn btn-primary" onclick="createNewClient()">Enregistrer</button>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
            .product-item {
                transition: transform 0.2s;
                cursor: pointer;
            }
            .product-item:hover {
                transform: translateY(-5px);
            }
            .cart-item {
                border-bottom: 1px solid #eee;
                padding: 10px 0;
            }
            .cart-item:last-child {
                border-bottom: none;
            }
            .quantity-input {
                width: 60px;
                text-align: center;
            }
            #clientId option {
                padding: 8px;
                border-bottom: 1px solid #eee;
            }
            .barcode-highlight {
                background-color: #ffff99 !important;
                transition: background-color 0.3s;
            }
        </style>
        
        <script>
            let cart = <?php echo json_encode($cart); ?>;
            let currentDevise = '<?php echo $currentDevise; ?>';
            let currentClientId = <?php echo $_SESSION['current_client']['id_client'] ?? 'null'; ?>;
            
            // Initialiser l'affichage
            updateCartDisplay();
            
            // Debug - Afficher les produits chargés
            document.addEventListener('DOMContentLoaded', function() {
                const products = document.querySelectorAll('.product-item');
                console.log('📦 Nombre de produits trouvés:', products.length);
                products.forEach((product, index) => {
                    console.log(`Produit ${index + 1}:`, {
                        id: product.dataset.id,
                        name: product.dataset.name,
                        barcode: product.dataset.barcode,
                        price: product.dataset.price,
                        stock: product.dataset.stock
                    });
                });
                
                // Focus sur le scanner
                const scannerInput = document.getElementById('barcodeInput');
                if (scannerInput) {
                    scannerInput.focus();
                }
            });
            
            // Gestion des clics sur les produits
            document.querySelectorAll('.product-item').forEach(card => {
                card.addEventListener('click', (e) => {
                    // Éviter si on clique sur le bouton
                    if (e.target.classList.contains('add-to-cart-btn') || e.target.closest('.add-to-cart-btn')) {
                        return;
                    }
                    const id = parseInt(card.dataset.id);
                    const name = card.dataset.name;
                    const price = parseFloat(card.dataset.price);
                    const stock = parseInt(card.dataset.stock);
                    const barcode = card.dataset.barcode;
                    
                    console.log('Produit cliqué:', {id, name, price, stock, barcode});
                    addToCart(id, name, price, stock, barcode);
                });
            });
            
            // Gestion des boutons Ajouter
            document.querySelectorAll('.add-to-cart-btn').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const card = btn.closest('.product-item');
                    const id = parseInt(card.dataset.id);
                    const name = card.dataset.name;
                    const price = parseFloat(card.dataset.price);
                    const stock = parseInt(card.dataset.stock);
                    const barcode = card.dataset.barcode;
                    
                    console.log('Bouton ajouter cliqué:', {id, name, price, stock, barcode});
                    addToCart(id, name, price, stock, barcode);
                });
            });
            
            function addToCart(id, name, price, stock, barcode) {
                const existingItem = cart.find(item => item.id === id);
                
                if (existingItem) {
                    if (existingItem.quantity < stock) {
                        existingItem.quantity++;
                        existingItem.total = existingItem.quantity * existingItem.price;
                        console.log('Quantité augmentée pour:', name, 'Nouvelle quantité:', existingItem.quantity);
                    } else {
                        alert('Stock insuffisant ! Stock disponible: ' + stock);
                        return;
                    }
                } else {
                    if (stock > 0) {
                        cart.push({
                            id: id,
                            name: name,
                            price: price,
                            quantity: 1,
                            total: price,
                            barcode: barcode,
                            stock: stock
                        });
                        console.log('Produit ajouté au panier:', name);
                    } else {
                        alert('Produit en rupture de stock !');
                        return;
                    }
                }
                
                updateCartDisplay();
                saveCart();
                playBeep();
            }
            
            function updateCartDisplay() {
                const cartContainer = document.getElementById('cartItems');
                const checkoutBtn = document.getElementById('checkoutBtn');
                let subtotal = 0;
                
                if (cart.length === 0) {
                    cartContainer.innerHTML = '<p class="text-muted text-center">Aucun article dans le panier</p>';
                    document.getElementById('subtotal').innerHTML = '0 ' + currentDevise;
                    document.getElementById('tax').innerHTML = '0 ' + currentDevise;
                    document.getElementById('totalAmount').innerHTML = '0 ' + currentDevise;
                    if (checkoutBtn) checkoutBtn.disabled = true;
                    return;
                }
                
                let html = '';
                for (let i = 0; i < cart.length; i++) {
                    const item = cart[i];
                    subtotal += item.price * item.quantity;
                    html += `
                        <div class="cart-item">
                            <div class="row align-items-center">
                                <div class="col-5">
                                    <strong>${escapeHtml(item.name)}</strong>
                                </div>
                                <div class="col-3">
                                    <input type="number" class="form-control quantity-input" value="${item.quantity}" 
                                           min="1" max="${item.stock}" onchange="updateQuantity(${i}, this.value)">
                                </div>
                                <div class="col-3">
                                    ${formatPrice(item.price * item.quantity)} ${currentDevise}
                                </div>
                                <div class="col-1">
                                    <button class="btn btn-sm btn-danger" onclick="removeFromCart(${i})">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    `;
                }
                
                const tax = subtotal * 0.18;
                const total = subtotal + tax;
                
                cartContainer.innerHTML = html;
                document.getElementById('subtotal').innerHTML = formatPrice(subtotal) + ' ' + currentDevise;
                document.getElementById('tax').innerHTML = formatPrice(tax) + ' ' + currentDevise;
                document.getElementById('totalAmount').innerHTML = formatPrice(total) + ' ' + currentDevise;
                if (checkoutBtn) checkoutBtn.disabled = false;
            }
            
            function updateQuantity(index, quantity) {
                quantity = parseInt(quantity);
                if (quantity > 0 && quantity <= cart[index].stock) {
                    cart[index].quantity = quantity;
                    cart[index].total = cart[index].price * quantity;
                    updateCartDisplay();
                    saveCart();
                } else if (quantity > cart[index].stock) {
                    alert('Stock insuffisant ! Stock disponible: ' + cart[index].stock);
                    updateCartDisplay();
                } else {
                    removeFromCart(index);
                }
            }
            
            function removeFromCart(index) {
                cart.splice(index, 1);
                updateCartDisplay();
                saveCart();
            }
            
            function formatPrice(price) {
                return Math.round(price).toLocaleString('fr-FR');
            }
            
            function escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }
            
            function playBeep() {
                const input = document.getElementById('barcodeInput');
                if (input) {
                    input.style.backgroundColor = '#e8f0fe';
                    setTimeout(() => input.style.backgroundColor = '', 200);
                }
            }
            
            function saveCart() {
                fetch('<?php echo \BASE_PATH; ?>/index.php?action=save_cart', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({cart: cart})
                }).catch(e => console.log('Erreur sauvegarde panier:', e));
            }
            
            function clearCart() {
                if (confirm('Vider le panier ?')) {
                    cart = [];
                    updateCartDisplay();
                    saveCart();
                }
            }
            
            // ==================== SCANNER CODE-BARRES ====================
            let barcodeBuffer = '';
            let lastTime = 0;
            const barcodeInput = document.getElementById('barcodeInput');
            
            if (barcodeInput) {
                barcodeInput.addEventListener('keypress', function(e) {
                    const now = Date.now();
                    if (now - lastTime > 100) barcodeBuffer = '';
                    lastTime = now;
                    
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        const barcode = barcodeBuffer.trim();
                        barcodeBuffer = '';
                        this.value = '';
                        
                        console.log('🔍 Code-barres scanné:', barcode);
                        
                        if (barcode === '') {
                            console.log('Code-barres vide');
                            return;
                        }
                        
                        const productCards = document.querySelectorAll('.product-item');
                        console.log('📋 Nombre de produits disponibles:', productCards.length);
                        
                        let foundProduct = null;
                        for (let card of productCards) {
                            const productBarcode = card.dataset.barcode;
                            if (productBarcode === barcode) {
                                foundProduct = card;
                                break;
                            }
                        }
                        
                        if (foundProduct) {
                            console.log('✅ Produit trouvé:', foundProduct.dataset.name);
                            foundProduct.classList.add('barcode-highlight');
                            setTimeout(() => foundProduct.classList.remove('barcode-highlight'), 500);
                            foundProduct.click();
                        } else {
                            console.log('❌ Aucun produit trouvé avec le code-barres:', barcode);
                            const availableBarcodes = Array.from(productCards).map(card => card.dataset.barcode);
                            console.log('📋 Codes-barres disponibles:', availableBarcodes);
                            alert('Produit non trouvé avec le code-barres: "' + barcode + '"\n\nVérifiez que le code-barres scanné correspond exactement à celui en base de données.');
                        }
                    } else {
                        barcodeBuffer += e.key;
                        this.value = barcodeBuffer;
                    }
                });
            }
            
            // ==================== CHANGEMENT DE MAGASIN ====================
            function switchMagasin() {
                const magasinId = document.getElementById('magasinSelector').value;
                if (!magasinId) {
                    alert('Veuillez sélectionner un magasin');
                    return;
                }
                
                const btn = event.target.closest('button');
                const originalText = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Chargement...';
                btn.disabled = true;
                
                fetch('<?php echo \BASE_PATH; ?>/index.php?action=switch_magasin', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({magasin_id: magasinId})
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Erreur lors du changement de magasin');
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('Erreur lors du changement de magasin');
                })
                .finally(() => {
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                });
            }
            
            function checkout() {
                if (cart.length === 0) {
                    alert('Votre panier est vide');
                    return;
                }
                
                const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
                const total = subtotal + (subtotal * 0.18);
                document.getElementById('modalTotal').innerHTML = formatPrice(total) + ' ' + currentDevise;
                document.getElementById('cashAmount').value = total;
                updatePaymentFields();
                
                new bootstrap.Modal(document.getElementById('paymentModal')).show();
            }
            
            function updatePaymentFields() {
                const mode = document.getElementById('paymentMode').value;
                const cashDiv = document.getElementById('cashAmountDiv');
                
                if (mode === 'cash') {
                    cashDiv.style.display = 'block';
                } else {
                    cashDiv.style.display = 'none';
                }
            }
            
            function processPayment() {
                if (cart.length === 0) {
                    alert('Panier vide !');
                    return;
                }
                
                const mode = document.getElementById('paymentMode').value;
                const clientId = document.getElementById('clientId').value;
                const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
                const total = subtotal + (subtotal * 0.18);
                let cashAmount = 0;
                let change = 0;
                
                if (mode === 'cash') {
                    cashAmount = parseFloat(document.getElementById('cashAmount').value);
                    if (isNaN(cashAmount) || cashAmount < total) {
                        alert('Montant insuffisant ! Total: ' + formatPrice(total) + ' ' + currentDevise);
                        return;
                    }
                    change = cashAmount - total;
                }
                
                if (!confirm('Confirmer le paiement de ' + formatPrice(total) + ' ' + currentDevise + ' ?')) return;
                
                const payBtn = document.querySelector('#paymentModal .btn-success');
                const originalText = payBtn.textContent;
                payBtn.textContent = '⏳ Traitement...';
                payBtn.disabled = true;
                
                const data = {
                    cart: cart,
                    total: total,
                    received: cashAmount,
                    change: change,
                    payment_method: mode,
                    type_vente: 'caisse',
                    client_id: clientId || null,
                    devise: currentDevise
                };
                
                fetch('<?php echo \BASE_PATH; ?>/index.php?action=process_sale', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(data)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        printTicket(data);
                        openCashDrawer();
                        
                        let message = `✅ Vente effectuée !\nFacture: ${data.invoice_number}\nTotal: ${formatPrice(total)} ${currentDevise}`;
                        if (mode === 'cash') {
                            message += `\nMontant reçu: ${formatPrice(cashAmount)} ${currentDevise}\nMonnaie: ${formatPrice(change)} ${currentDevise}`;
                        }
                        alert(message);
                        
                        cart = [];
                        updateCartDisplay();
                        saveCart();
                        
                        bootstrap.Modal.getInstance(document.getElementById('paymentModal')).hide();
                        location.reload();
                    } else {
                        alert('❌ Erreur: ' + (data.message || 'Veuillez réessayer'));
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('❌ Erreur lors du traitement: ' + error);
                })
                .finally(() => {
                    payBtn.textContent = originalText;
                    payBtn.disabled = false;
                });
            }
            
            function printTicket(data) {
                let ticket = '='.repeat(32) + '\n';
                ticket += '     TOTAL FAMILY\n';
                ticket += '  Multi-Services Auto\n';
                ticket += '='.repeat(32) + '\n';
                ticket += 'Facture: ' + data.invoice_number + '\n';
                ticket += 'Date: ' + new Date().toLocaleString('fr-FR') + '\n';
                ticket += 'Caissier: <?php echo addslashes($userName); ?>\n';
                if (<?php echo $id_magasin ? 'true' : 'false'; ?>) {
                    ticket += 'Magasin: <?php echo addslashes($currentMagasin['nom_magasin'] ?? ''); ?>\n';
                }
                if (data.client_id) {
                    ticket += 'Client: ID ' + data.client_id + '\n';
                }
                ticket += 'Devise: ' + data.devise + '\n';
                ticket += '-'.repeat(32) + '\n\n';
                
                data.cart.forEach(item => {
                    let name = item.name.substring(0, 20).padEnd(20);
                    let qty = item.quantity.toString().padStart(3);
                    let price = formatPrice(item.price).padStart(8);
                    let total = formatPrice(item.price * item.quantity).padStart(10);
                    ticket += `${name} ${qty} x ${price} = ${total}\n`;
                });
                
                ticket += '\n' + '-'.repeat(32) + '\n';
                ticket += `TOTAL HT: ${formatPrice(data.total / 1.18)} ${data.devise}\n`;
                ticket += `TVA (18%): ${formatPrice(data.total - (data.total / 1.18))} ${data.devise}\n`;
                ticket += `TOTAL TTC: ${formatPrice(data.total)} ${data.devise}\n`;
                if (data.received > 0) {
                    ticket += `REÇU: ${formatPrice(data.received)} ${data.devise}\n`;
                    ticket += `MONNAIE: ${formatPrice(data.change)} ${data.devise}\n`;
                }
                ticket += '='.repeat(32) + '\n';
                ticket += '     MERCI DE VOTRE VISITE\n';
                ticket += '     À bientôt !\n';
                ticket += '='.repeat(32) + '\n';
                
                const printWindow = window.open('', '_blank');
                printWindow.document.write('<pre style="font-family: monospace; font-size: 12px;">' + ticket + '</pre>');
                printWindow.document.close();
                printWindow.print();
            }
            
            function openCashDrawer() {
                fetch('http://localhost:9100/open', {
                    method: 'POST',
                    mode: 'no-cors'
                }).catch(e => console.log('Erreur ouverture tiroir (USB):', e));
                
                fetch('http://127.0.0.1:3000/open-drawer', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({action: 'open'})
                }).catch(e => console.log('Erreur ouverture tiroir (serveur):', e));
                
                console.log('🔔 OUVERTURE DU TIROIR-CAISSE');
            }
            
            function searchProducts() {
                const searchTerm = document.getElementById('searchProduct').value.toLowerCase();
                const products = document.querySelectorAll('.product-item');
                
                products.forEach(product => {
                    const name = product.dataset.name.toLowerCase();
                    if (name.includes(searchTerm)) {
                        product.style.display = '';
                    } else {
                        product.style.display = 'none';
                    }
                });
            }
            
            function searchClient() {
                const term = document.getElementById('clientSearchInput').value;
                if (!term) {
                    alert('Entrez un nom, téléphone ou email');
                    return;
                }
                
                fetch('<?php echo \BASE_PATH; ?>/index.php?action=search_client&term=' + encodeURIComponent(term))
                .then(r => r.json())
                .then(clients => {
                    const select = document.getElementById('clientId');
                    select.innerHTML = '<option value="">Client anonyme (vente au comptoir)</option>';
                    
                    if (clients.length === 0) {
                        alert('Aucun client trouvé');
                    } else {
                        clients.forEach(client => {
                            const option = document.createElement('option');
                            option.value = client.id_client;
                            option.textContent = `${client.code_client} - ${client.nom} ${client.prenom || ''} - ${client.telephone || client.email || ''}`;
                            select.appendChild(option);
                        });
                        if (clients.length === 1) {
                            select.value = clients[0].id_client;
                        }
                    }
                })
                .catch(error => console.error('Erreur recherche client:', error));
            }
            
            function showNewClientForm() {
                new bootstrap.Modal(document.getElementById('newClientModal')).show();
            }
            
            function createNewClient() {
                const form = document.getElementById('newClientForm');
                const formData = new FormData(form);
                const data = {};
                formData.forEach((value, key) => data[key] = value);
                
                if (!data.nom) {
                    alert('Le nom est requis');
                    return;
                }
                
                fetch('<?php echo \BASE_PATH; ?>/index.php?action=create_client', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(data)
                })
                .then(r => r.json())
                .then(result => {
                    if (result.success) {
                        alert('Client créé avec succès! Code: ' + result.code);
                        bootstrap.Modal.getInstance(document.getElementById('newClientModal')).hide();
                        document.getElementById('clientSearchInput').value = result.code;
                        searchClient();
                        form.reset();
                    } else {
                        alert('Erreur lors de la création du client');
                    }
                })
                .catch(error => console.error('Erreur création client:', error));
            }
            
            function switchDevise() {
                const devise = document.getElementById('deviseSelector').value;
                fetch('<?php echo \BASE_PATH; ?>/index.php?action=switch_devise', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({devise: devise})
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        currentDevise = devise;
                        location.reload();
                    }
                });
            }
            
            const cashAmountInput = document.getElementById('cashAmount');
            if (cashAmountInput) {
                cashAmountInput.addEventListener('input', function() {
                    const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
                    const total = subtotal + (subtotal * 0.18);
                    const received = parseFloat(this.value) || 0;
                    if (received > 0) {
                        const change = received - total;
                        document.getElementById('changeAmount').innerHTML = change >= 0 ? 'Monnaie à rendre: ' + formatPrice(change) + ' ' + currentDevise : 'Montant insuffisant';
                    } else {
                        document.getElementById('changeAmount').innerHTML = '';
                    }
                });
            }
        </script>
        
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        
        <?php
        $footerPath = __DIR__ . '/../views/layouts/footer.php';
        if (file_exists($footerPath)) {
            require_once $footerPath;
        } else {
            echo '<div class="footer"><p>&copy; ' . date('Y') . ' Total Family Multi-Services</p></div>';
            echo '</body></html>';
        }
    }
    
    public function getProductsApi()
    {
        $this->requireLogin();
        
        $id_magasin = $this->currentMagasin ? $this->currentMagasin['id_magasin'] : null;
        
        $products = $this->db->fetchAll(
            "SELECT id_produit, code_barre, nom_produit, prix_vente_ttc, stock_actuel 
             FROM produits WHERE est_actif = 1 AND id_magasin = ? 
             ORDER BY nom_produit",
            [$id_magasin]
        );
        header('Content-Type: application/json');
        echo json_encode($products);
        exit;
    }
    
    public function saveCart()
    {
        $this->requireLogin();
        $data = json_decode(file_get_contents('php://input'), true);
        if (isset($data['cart'])) {
            $_SESSION['cart'] = $data['cart'];
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false]);
        }
        exit;
    }
    
    public function processSale()
    {
        $this->requireLogin();
        
        $data = json_decode(file_get_contents('php://input'), true);
        $cart = $data['cart'] ?? [];
        $total = $data['total'] ?? 0;
        $received = $data['received'] ?? 0;
        $change = $data['change'] ?? 0;
        $paymentMethod = $data['payment_method'] ?? 'cash';
        $typeVente = $data['type_vente'] ?? 'caisse';
        $clientId = $data['client_id'] ?? null;
        $deviseCode = $data['devise'] ?? 'HTG';
        $id_magasin = $this->currentMagasin ? $this->currentMagasin['id_magasin'] : null;
        
        if (empty($cart)) {
            echo json_encode(['success' => false, 'message' => 'Panier vide']);
            exit;
        }
        
        // Récupérer le taux de change
        $devise = $this->db->fetchOne(
            "SELECT taux_htg FROM devises WHERE code = ? AND est_actif = 1",
            [$deviseCode]
        );
        $taux = $devise ? $devise['taux_htg'] : 1;
        
        // Convertir le total en Gourdes pour l'enregistrement
        $totalHTG = $deviseCode === 'HTG' ? $total : $total * $taux;
        
        $invoiceNumber = 'FACT-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        try {
            $this->db->beginTransaction();
            
            $totalHT = $totalHTG / 1.18;
            $totalTVA = $totalHTG - $totalHT;
            
            $this->db->query(
                "INSERT INTO ventes (numero_facture, id_user, id_client, id_magasin, type_vente, devise, taux_devise, montant_total_ht, montant_tva, montant_total_ttc, montant_final, montant_recu, monnaie_rendue, mode_paiement, statut, date_vente) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())",
                [
                    $invoiceNumber, 
                    $_SESSION['user_id'], 
                    $clientId, 
                    $id_magasin,
                    $typeVente,
                    $deviseCode,
                    $taux,
                    $totalHT, 
                    $totalTVA, 
                    $totalHTG, 
                    $totalHTG, 
                    $received, 
                    $change, 
                    $paymentMethod,
                    'complete'
                ]
            );
            
            $saleId = $this->db->lastInsertId();
            
            foreach ($cart as $item) {
                $prixHT = ($item['price'] * $taux) / 1.18;
                $this->db->query(
                    "INSERT INTO details_vente (id_vente, id_produit, code_barre_scanne, quantite, prix_unitaire_ht, tva) 
                     VALUES (?, ?, ?, ?, ?, ?)",
                    [$saleId, $item['id'], $item['barcode'] ?? '', $item['quantity'], $prixHT, 18]
                );
                $this->db->query(
                    "UPDATE produits SET stock_actuel = stock_actuel - ? WHERE id_produit = ? AND id_magasin = ?",
                    [$item['quantity'], $item['id'], $id_magasin]
                );
            }
            
            $this->db->commit();
            $_SESSION['cart'] = [];
            
            echo json_encode([
                'success' => true,
                'invoice_number' => $invoiceNumber,
                'sale_id' => $saleId,
                'cart' => $cart,
                'total' => $total,
                'received' => $received,
                'change' => $change,
                'devise' => $deviseCode,
                'taux' => $taux,
                'client_id' => $clientId
            ]);
            
        } catch (\Exception $e) {
            $this->db->rollBack();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
    
    public function switchMagasin()
    {
        $this->requireLogin();
        
        $data = json_decode(file_get_contents('php://input'), true);
        $magasinId = $data['magasin_id'] ?? null;
        
        if ($magasinId) {
            $_SESSION['current_magasin_id'] = $magasinId;
            
            // Recharger le magasin courant
            $magasinModel = new Magasin();
            $this->currentMagasin = $magasinModel->getMagasinById($magasinId);
            
            // Vider le panier lors du changement de magasin
            $_SESSION['cart'] = [];
            
            echo json_encode(['success' => true, 'magasin' => $this->currentMagasin]);
        } else {
            echo json_encode(['success' => false]);
        }
        exit;
    }
    
    public function updateTaux()
    {
        $this->requireLogin();
        $data = json_decode(file_get_contents('php://input'), true);
        $code = $data['code'] ?? '';
        $taux = floatval($data['taux'] ?? 0);
        
        if ($code && $taux > 0) {
            $this->deviseModel->updateTaux($code, $taux);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false]);
        }
        exit;
    }
    
    public function switchDevise()
    {
        $this->requireLogin();
        $data = json_decode(file_get_contents('php://input'), true);
        $devise = $data['devise'] ?? 'HTG';
        
        $check = $this->db->fetchOne(
            "SELECT code FROM devises WHERE code = ? AND est_actif = 1",
            [$devise]
        );
        
        if ($check) {
            $_SESSION['current_devise'] = $devise;
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false]);
        }
        exit;
    }
    
    public function searchClient()
    {
        $this->requireLogin();
        $term = $_GET['term'] ?? '';
        $clients = $this->clientModel->search($term);
        echo json_encode($clients);
        exit;
    }
    
    public function createClient()
    {
        $this->requireLogin();
        $data = json_decode(file_get_contents('php://input'), true);
        
        $code = $this->clientModel->generateCode();
        
        $this->db->insert('clients', [
            'code_client' => $code,
            'nom' => $data['nom'],
            'prenom' => $data['prenom'] ?? null,
            'telephone' => $data['telephone'] ?? null,
            'email' => $data['email'] ?? null,
            'adresse' => $data['adresse'] ?? null,
            'type_client' => $data['type_client'] ?? 'particulier',
            'est_actif' => 1
        ]);
        
        echo json_encode(['success' => true, 'code' => $code]);
        exit;
    }
    
    private function renderLegacyHeader($userName, $currentMagasin)
    {
        ?>
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Point de vente - Total Family</title>
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
                .header-left {
                    display: flex;
                    align-items: center;
                    gap: 20px;
                    flex-wrap: wrap;
                }
                .logo-container {
                    display: flex;
                    align-items: center;
                    gap: 15px;
                }
                .logo {
                    height: 50px;
                    width: auto;
                    border-radius: 10px;
                    background: rgba(255,255,255,0.1);
                    padding: 5px;
                }
                .company-name {
                    font-size: 22px;
                    font-weight: bold;
                    margin: 0;
                }
                .company-name small {
                    font-size: 12px;
                    opacity: 0.9;
                    display: block;
                }
                .user-info { display: flex; align-items: center; gap: 15px; flex-wrap: wrap; }
                .btn-link {
                    background: rgba(255,255,255,0.2);
                    padding: 8px 15px;
                    border-radius: 5px;
                    text-decoration: none;
                    color: white;
                    transition: background 0.3s;
                    display: inline-flex;
                    align-items: center;
                    gap: 5px;
                }
                .btn-link:hover {
                    background: rgba(255,255,255,0.3);
                    color: white;
                }
                .container-fluid { max-width: 1400px; margin: 0 auto; padding: 20px; }
                .card {
                    background: white;
                    border-radius: 10px;
                    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
                    margin-bottom: 20px;
                }
                .card-header {
                    padding: 15px 20px;
                    border-bottom: 1px solid #eee;
                    font-weight: bold;
                }
                .card-body { padding: 20px; }
                .footer { text-align: center; padding: 20px; color: #666; margin-top: 40px; border-top: 1px solid #e0e0e0; }
            </style>
        </head>
        <body>
            <div class="header">
                <div class="header-left">
                    <div class="logo-container">
                        <img src="/auto-parts-management/public/assets/images/logo_total_family.png" alt="Logo" class="logo" onerror="this.style.display='none'">
                        <div class="company-name">
                            Total Family Multi-Services
                            <small>Votre partenaire auto de confiance</small>
                        </div>
                    </div>
                </div>
                <div class="user-info">
                    <span>👤 <?php echo htmlspecialchars($userName); ?></span>
                    <a href="<?php echo \BASE_PATH; ?>/index.php?action=dashboard" class="btn-link">
                        <i class="fas fa-tachometer-alt"></i> Tableau de bord
                    </a>
                    <a href="<?php echo \BASE_PATH; ?>/index.php?action=products" class="btn-link">
                        <i class="fas fa-boxes"></i> Produits
                    </a>
                    <a href="<?php echo \BASE_PATH; ?>/index.php?action=logout" class="btn-link">
                        <i class="fas fa-sign-out-alt"></i> Déconnexion
                    </a>
                </div>
            </div>
        <?php
    }
}