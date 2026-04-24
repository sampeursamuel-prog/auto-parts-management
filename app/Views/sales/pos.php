<?php
$title = 'Point de vente';
include dirname(__DIR__) . '/layouts/header.php';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Point de vente - Auto-Parts</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --primary-dark: #3a56d4;
            --secondary: #7209b7;
            --success: #06d6a0;
            --danger: #ef476f;
            --warning: #ffd166;
            --dark: #2b2d42;
            --light: #f8f9fa;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        /* Header */
        .pos-header {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
            padding: 15px 0;
        }
        
        /* Panneau principal */
        .pos-container {
            display: flex;
            min-height: calc(100vh - 80px);
            padding: 20px;
            gap: 20px;
        }
        
        /* Panneau produits */
        .products-panel {
            flex: 2;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        
        .scanner-section {
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
            padding: 20px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .scanner-input {
            width: 100%;
            padding: 15px 20px;
            font-size: 18px;
            border: 2px solid var(--primary);
            border-radius: 50px;
            outline: none;
            transition: all 0.3s;
            font-family: monospace;
            letter-spacing: 1px;
        }
        
        .scanner-input:focus {
            box-shadow: 0 0 0 3px rgba(67,97,238,0.2);
            transform: scale(1.01);
        }
        
        .products-grid {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 15px;
        }
        
        .product-card {
            background: white;
            border-radius: 15px;
            padding: 15px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            border: 1px solid #e0e0e0;
            position: relative;
            overflow: hidden;
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            border-color: var(--primary);
        }
        
        .product-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            transform: scaleX(0);
            transition: transform 0.3s;
        }
        
        .product-card:hover::before {
            transform: scaleX(1);
        }
        
        .product-name {
            font-weight: 600;
            font-size: 14px;
            margin: 10px 0 5px;
            color: var(--dark);
        }
        
        .product-price {
            font-size: 18px;
            font-weight: 700;
            color: var(--primary);
        }
        
        .product-stock {
            font-size: 11px;
            color: #6c757d;
            margin-top: 5px;
        }
        
        /* Panneau panier */
        .cart-panel {
            flex: 1.2;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        
        .cart-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .cart-header h3 {
            margin: 0;
            font-size: 1.3rem;
        }
        
        .clear-cart {
            background: rgba(255,255,255,0.2);
            border: none;
            padding: 8px 15px;
            border-radius: 25px;
            color: white;
            transition: all 0.3s;
        }
        
        .clear-cart:hover {
            background: rgba(255,255,255,0.3);
            transform: scale(1.05);
        }
        
        .cart-items {
            flex: 1;
            overflow-y: auto;
            padding: 15px;
        }
        
        .cart-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px;
            border-bottom: 1px solid #e0e0e0;
            transition: background 0.2s;
        }
        
        .cart-item:hover {
            background: #f8f9fa;
        }
        
        .cart-item-info {
            flex: 2;
        }
        
        .cart-item-name {
            font-weight: 600;
            font-size: 14px;
        }
        
        .cart-item-price {
            font-size: 12px;
            color: #6c757d;
        }
        
        .cart-item-quantity {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .cart-item-quantity button {
            width: 28px;
            height: 28px;
            border: none;
            border-radius: 8px;
            background: #e9ecef;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .cart-item-quantity button:hover {
            background: var(--primary);
            color: white;
        }
        
        .cart-item-total {
            font-weight: 700;
            min-width: 80px;
            text-align: right;
            color: var(--primary);
        }
        
        .cart-summary {
            background: #f8f9fa;
            padding: 20px;
            border-top: 2px solid #e0e0e0;
        }
        
        .summary-line {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
        }
        
        .summary-line.total {
            font-size: 1.2rem;
            font-weight: 700;
            border-top: 2px solid #dee2e6;
            margin-top: 10px;
            padding-top: 15px;
            color: var(--primary);
        }
        
        .payment-section {
            margin-top: 15px;
        }
        
        .payment-section select,
        .payment-section input {
            width: 100%;
            padding: 12px;
            margin: 8px 0;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            transition: all 0.3s;
        }
        
        .payment-section select:focus,
        .payment-section input:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(67,97,238,0.1);
        }
        
        .btn-pay {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, var(--success), #0fa76a);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            margin-top: 15px;
            transition: all 0.3s;
        }
        
        .btn-pay:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(6,214,160,0.3);
        }
        
        .change-display {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            padding: 12px;
            border-radius: 12px;
            text-align: center;
            margin: 10px 0;
        }
        
        .change-display span {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--success);
        }
        
        .empty-cart {
            text-align: center;
            padding: 50px;
            color: #adb5bd;
        }
        
        .badge-warning {
            background: var(--warning);
            color: var(--dark);
            padding: 4px 8px;
            border-radius: 20px;
            font-size: 10px;
            margin-left: 8px;
        }
        
        @media (max-width: 768px) {
            .pos-container {
                flex-direction: column;
            }
            
            .products-panel, .cart-panel {
                flex: auto;
            }
        }
    </style>
</head>
<body>
    <div class="pos-header">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">
                        <i class="fas fa-cash-register"></i> Point de vente
                    </h1>
                    <?php if ($currentMagasin): ?>
                        <small class="text-muted">🏪 <?php echo htmlspecialchars($currentMagasin['nom_magasin']); ?></small>
                    <?php endif; ?>
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <!-- Sélecteur devise -->
                    <div class="dropdown">
                        <button class="btn btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="fas fa-coins"></i> 
                            <?php echo $currentDeviseData['symbole'] ?? 'G'; ?> <?php echo $currentDevise; ?>
                        </button>
                        <ul class="dropdown-menu">
                            <?php foreach ($devises as $d): ?>
                            <li>
                                <a class="dropdown-item" href="#" onclick="switchDevise('<?php echo $d['code']; ?>')">
                                    <?php echo $d['symbole']; ?> <?php echo $d['code']; ?> - <?php echo $d['nom']; ?>
                                    <small class="text-muted">(1 <?php echo $d['code']; ?> = <?php echo number_format($d['taux_htg'], 2); ?> G)</small>
                                </a>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    
                    <!-- Sélecteur type vente -->
                    <div class="btn-group">
                        <button type="button" class="btn btn-outline-secondary" id="btnCaisse" onclick="setTypeVente('caisse')">
                            <i class="fas fa-cash-register"></i> Caisse
                        </button>
                        <button type="button" class="btn btn-outline-secondary" id="btnDetail" onclick="setTypeVente('detail')">
                            <i class="fas fa-user"></i> Détail
                        </button>
                    </div>
                    
                    <a href="<?php echo \BASE_PATH; ?>/index.php?action=dashboard" class="btn btn-link">
                        <i class="fas fa-arrow-left"></i> Retour
                    </a>
                    <a href="<?php echo \BASE_PATH; ?>/index.php?action=logout" class="btn btn-link text-danger">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="pos-container">
        <div class="products-panel">
            <div class="scanner-section">
                <div class="input-group">
                    <span class="input-group-text bg-white border-0"><i class="fas fa-qrcode fa-lg text-primary"></i></span>
                    <input type="text" id="barcodeInput" class="scanner-input" placeholder="Scannez un code-barres..." autofocus>
                </div>
            </div>
            <div class="products-grid" id="productsGrid">
                <?php foreach ($products as $p): ?>
                <div class="product-card" onclick="addToCart(<?php echo $p['id_produit']; ?>)">
                    <div class="product-icon">
                        <i class="fas fa-car fa-2x text-primary"></i>
                    </div>
                    <div class="product-name"><?php echo htmlspecialchars($p['nom_produit']); ?></div>
                    <div class="product-price" data-price="<?php echo $p['prix_vente_ttc']; ?>">
                        <?php echo number_format($p['prix_vente_ttc'], 0, ',', ' '); ?> G
                    </div>
                    <div class="product-stock">
                        <i class="fas fa-boxes"></i> Stock: <?php echo $p['stock_actuel']; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="cart-panel">
            <div class="cart-header">
                <h3><i class="fas fa-shopping-cart"></i> Panier</h3>
                <button class="clear-cart" onclick="clearCart()">
                    <i class="fas fa-trash-alt"></i> Vider
                </button>
            </div>
            <div class="cart-items" id="cartItems">
                <?php if (empty($cart)): ?>
                <div class="empty-cart">
                    <i class="fas fa-shopping-basket fa-3x text-muted mb-3"></i>
                    <p>Panier vide</p>
                    <small>Scannez des produits pour commencer</small>
                </div>
                <?php endif; ?>
            </div>
            <div class="cart-summary">
                <div class="summary-line">
                    <span>Sous-total HT:</span>
                    <span id="subtotal">0 G</span>
                </div>
                <div class="summary-line">
                    <span>TVA (18%):</span>
                    <span id="tax">0 G</span>
                </div>
                <div class="summary-line total">
                    <span>TOTAL TTC:</span>
                    <span id="total">0 G</span>
                </div>
                
                <!-- Zone client pour vente au détail -->
                <div id="clientSection" style="display: none;">
                    <div class="mb-2">
                        <label class="small text-muted">Client</label>
                        <select id="clientSelect" class="form-select" onchange="selectClient(this.value)">
                            <option value="">-- Sélectionner un client --</option>
                            <?php foreach ($clients as $c): ?>
                            <option value="<?php echo $c['id_client']; ?>"><?php echo htmlspecialchars($c['code_client'] . ' - ' . $c['nom'] . ' ' . ($c['prenom'] ?? '')); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-2">
                        <button class="btn btn-sm btn-outline-primary w-100" onclick="openNewClientModal()">
                            <i class="fas fa-user-plus"></i> Nouveau client
                        </button>
                    </div>
                </div>
                
                <div class="payment-section">
                    <select id="paymentMethod">
                        <option value="cash">💰 Espèces</option>
                        <option value="card">💳 Carte bancaire</option>
                        <option value="mobile">📱 Mobile Money</option>
                    </select>
                    <input type="number" id="amountReceived" placeholder="Montant reçu" step="100">
                    <div id="changeDisplay" class="change-display" style="display: none;">
                        💵 Monnaie à rendre: <span id="change">0</span> <span id="changeDevise">G</span>
                    </div>
                    <button class="btn-pay" onclick="checkout()">
                        <i class="fas fa-check-circle"></i> PAYER
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal Nouveau Client -->
    <div class="modal fade" id="newClientModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-plus"></i> Nouveau client</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label>Type</label>
                        <select id="clientType" class="form-select">
                            <option value="particulier">Particulier</option>
                            <option value="entreprise">Entreprise</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Nom *</label>
                        <input type="text" id="clientNom" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Prénom</label>
                        <input type="text" id="clientPrenom" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label>Téléphone</label>
                        <input type="tel" id="clientTel" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label>Email</label>
                        <input type="email" id="clientEmail" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label>Adresse</label>
                        <textarea id="clientAdresse" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-primary" onclick="createClient()">Créer</button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let cart = <?php echo json_encode($cart); ?>;
        let products = <?php echo json_encode($products); ?>;
        let currentDevise = '<?php echo $currentDevise; ?>';
        let deviseTaux = <?php echo $currentDeviseData['taux_htg']; ?>;
        let typeVente = 'caisse';
        let selectedClient = null;
        
        const deviseSymbole = {
            'HTG': 'G',
            'USD': '$'
        };
        
        function formatPrice(price) {
            let converted = price;
            if (currentDevise !== 'HTG') {
                converted = price / deviseTaux;
            }
            return Math.round(converted).toLocaleString('fr-FR');
        }
        
        function formatDevise(price) {
            let converted = price;
            if (currentDevise !== 'HTG') {
                converted = price / deviseTaux;
            }
            return Math.round(converted).toLocaleString('fr-FR') + ' ' + (deviseSymbole[currentDevise] || 'G');
        }
        
        function switchDevise(code) {
            fetch('<?php echo \BASE_PATH; ?>/index.php?action=switch_devise', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({devise: code})
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            });
        }
        
        function setTypeVente(type) {
            typeVente = type;
            document.getElementById('btnCaisse').classList.remove('btn-primary');
            document.getElementById('btnDetail').classList.remove('btn-primary');
            document.getElementById('btnCaisse').classList.add('btn-outline-secondary');
            document.getElementById('btnDetail').classList.add('btn-outline-secondary');
            
            if (type === 'caisse') {
                document.getElementById('btnCaisse').classList.remove('btn-outline-secondary');
                document.getElementById('btnCaisse').classList.add('btn-primary');
                document.getElementById('clientSection').style.display = 'none';
            } else {
                document.getElementById('btnDetail').classList.remove('btn-outline-secondary');
                document.getElementById('btnDetail').classList.add('btn-primary');
                document.getElementById('clientSection').style.display = 'block';
            }
        }
        
        function selectClient(clientId) {
            selectedClient = clientId;
        }
        
        function openNewClientModal() {
            new bootstrap.Modal(document.getElementById('newClientModal')).show();
        }
        
        function createClient() {
            const data = {
                nom: document.getElementById('clientNom').value,
                prenom: document.getElementById('clientPrenom').value,
                telephone: document.getElementById('clientTel').value,
                email: document.getElementById('clientEmail').value,
                adresse: document.getElementById('clientAdresse').value,
                type_client: document.getElementById('clientType').value
            };
            
            if (!data.nom) {
                alert('Veuillez saisir le nom du client');
                return;
            }
            
            fetch('<?php echo \BASE_PATH; ?>/index.php?action=create_client', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Client créé avec succès! Code: ' + data.code);
                    location.reload();
                }
            });
        }
        
        function addToCart(productId) {
            const product = products.find(p => p.id_produit == productId);
            if (!product) return;
            
            const existing = cart.find(item => item.id == productId);
            if (existing) {
                if (existing.quantity + 1 <= existing.stock) {
                    existing.quantity++;
                } else {
                    alert('Stock insuffisant !');
                }
            } else {
                cart.push({
                    id: product.id_produit,
                    name: product.nom_produit,
                    price: product.prix_vente_ttc,
                    quantity: 1,
                    stock: product.stock_actuel,
                    barcode: product.code_barre
                });
            }
            
            updateCartDisplay();
            saveCart();
            playBeep();
        }
        
        function updateCartDisplay() {
            const container = document.getElementById('cartItems');
            if (cart.length === 0) {
                container.innerHTML = '<div class="empty-cart"><i class="fas fa-shopping-basket fa-3x text-muted mb-3"></i><p>Panier vide</p><small>Scannez des produits pour commencer</small></div>';
                updateTotals();
                return;
            }
            
            let html = '';
            cart.forEach((item, idx) => {
                html += `
                    <div class="cart-item">
                        <div class="cart-item-info">
                            <div class="cart-item-name">${escapeHtml(item.name)}</div>
                            <div class="cart-item-price">${formatDevise(item.price)}</div>
                        </div>
                        <div class="cart-item-quantity">
                            <button onclick="updateQty(${idx}, -1)">-</button>
                            <span>${item.quantity}</span>
                            <button onclick="updateQty(${idx}, 1)">+</button>
                        </div>
                        <div class="cart-item-total">
                            ${formatDevise(item.price * item.quantity)}
                        </div>
                    </div>
                `;
            });
            container.innerHTML = html;
            updateTotals();
        }
        
        function updateQty(index, delta) {
            if (!cart[index]) return;
            const newQty = cart[index].quantity + delta;
            if (newQty <= 0) {
                cart.splice(index, 1);
            } else if (newQty <= cart[index].stock) {
                cart[index].quantity = newQty;
            } else {
                alert('Stock insuffisant !');
            }
            updateCartDisplay();
            saveCart();
        }
        
        function updateTotals() {
            let subtotal = cart.reduce((s, i) => s + (i.price * i.quantity), 0);
            let tax = subtotal * 0.18;
            let total = subtotal + tax;
            
            document.getElementById('subtotal').innerHTML = formatDevise(subtotal);
            document.getElementById('tax').innerHTML = formatDevise(tax);
            document.getElementById('total').innerHTML = formatDevise(total);
            
            const received = parseFloat(document.getElementById('amountReceived').value) || 0;
            if (received > 0) {
                let change = received - (currentDevise === 'HTG' ? total : total / deviseTaux);
                document.getElementById('change').innerHTML = Math.max(0, Math.round(change)).toLocaleString('fr-FR');
                document.getElementById('changeDisplay').style.display = 'block';
            } else {
                document.getElementById('changeDisplay').style.display = 'none';
            }
        }
        
        function playBeep() {
            const input = document.getElementById('barcodeInput');
            input.style.backgroundColor = '#e8f0fe';
            setTimeout(() => input.style.backgroundColor = '', 200);
        }
        
        function saveCart() {
            fetch('<?php echo \BASE_PATH; ?>/index.php?action=save_cart', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({cart: cart})
            }).catch(e => console.log('Erreur:', e));
        }
        
        function clearCart() {
            if (confirm('Vider le panier ?')) {
                cart = [];
                updateCartDisplay();
                saveCart();
            }
        }
        
        let barcodeBuffer = '';
        let lastTime = 0;
        const barcodeInput = document.getElementById('barcodeInput');
        barcodeInput.addEventListener('keypress', function(e) {
            const now = Date.now();
            if (now - lastTime > 100) barcodeBuffer = '';
            lastTime = now;
            
            if (e.key === 'Enter') {
                e.preventDefault();
                const barcode = barcodeBuffer;
                barcodeBuffer = '';
                this.value = '';
                
                const product = products.find(p => p.code_barre === barcode);
                if (product) addToCart(product.id_produit);
                else alert('Produit non trouvé: ' + barcode);
            } else {
                barcodeBuffer += e.key;
                this.value = barcodeBuffer;
            }
        });
        
        document.getElementById('amountReceived').addEventListener('input', updateTotals);
        
        function checkout() {
            if (cart.length === 0) {
                alert('Panier vide !');
                return;
            }
            
            const total = cart.reduce((s, i) => s + (i.price * i.quantity), 0);
            const totalTTC = total + (total * 0.18);
            const received = parseFloat(document.getElementById('amountReceived').value) || 0;
            
            let totalDisplay = totalTTC;
            if (currentDevise !== 'HTG') {
                totalDisplay = totalTTC / deviseTaux;
            }
            
            if (received < totalDisplay) {
                alert('Montant insuffisant ! Total: ' + Math.round(totalDisplay).toLocaleString('fr-FR') + ' ' + (deviseSymbole[currentDevise] || 'G'));
                return;
            }
            
            const change = received - totalDisplay;
            const paymentMethod = document.getElementById('paymentMethod').value;
            
            if (!confirm('Confirmer le paiement de ' + Math.round(totalDisplay).toLocaleString('fr-FR') + ' ' + (deviseSymbole[currentDevise] || 'G') + ' ?')) return;
            
            const payBtn = document.querySelector('.btn-pay');
            const originalText = payBtn.textContent;
            payBtn.textContent = '⏳ Traitement...';
            payBtn.disabled = true;
            
            fetch('<?php echo \BASE_PATH; ?>/index.php?action=process_sale', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    cart: cart,
                    total: totalTTC,
                    received: received,
                    change: change,
                    payment_method: paymentMethod,
                    type_vente: typeVente,
                    client_id: selectedClient,
                    devise: currentDevise
                })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    printTicket(data);
                    openCashDrawer();
                    alert('✅ Vente effectuée !\nFacture: ' + data.invoice_number + '\nMonnaie: ' + Math.round(change).toLocaleString('fr-FR') + ' ' + (deviseSymbole[currentDevise] || 'G'));
                    cart = [];
                    updateCartDisplay();
                    saveCart();
                    document.getElementById('amountReceived').value = '';
                    document.getElementById('changeDisplay').style.display = 'none';
                    location.reload();
                } else {
                    alert('❌ Erreur: ' + (data.message || 'Veuillez réessayer'));
                }
            })
            .catch(e => alert('❌ Erreur: ' + e.message))
            .finally(() => {
                payBtn.textContent = originalText;
                payBtn.disabled = false;
            });
        }
        
        function printTicket(data) {
            let ticket = '='.repeat(32) + '\n';
            ticket += '     AUTO-PARTS MANAGEMENT\n';
            ticket += '='.repeat(32) + '\n';
            ticket += 'Facture: ' + data.invoice_number + '\n';
            ticket += 'Date: ' + new Date().toLocaleString('fr-FR') + '\n';
            ticket += 'Type: ' + (typeVente === 'caisse' ? 'Caisse' : 'Détail') + '\n';
            ticket += 'Devise: ' + currentDevise + '\n';
            ticket += '-'.repeat(32) + '\n\n';
            
            data.cart.forEach(item => {
                let name = item.name.substring(0, 20).padEnd(20);
                let qty = item.quantity.toString().padStart(3);
                let price = formatDevise(item.price);
                let total = formatDevise(item.price * item.quantity);
                ticket += `${name} ${qty} x ${price} = ${total}\n`;
            });
            
            ticket += '\n' + '-'.repeat(32) + '\n';
            ticket += `TOTAL: ${formatDevise(data.total)} ${currentDevise}\n`;
            ticket += `REÇU: ${Math.round(data.received).toLocaleString('fr-FR')} ${currentDevise}\n`;
            ticket += `MONNAIE: ${Math.round(data.change).toLocaleString('fr-FR')} ${currentDevise}\n`;
            ticket += '='.repeat(32) + '\n';
            ticket += '     MERCI DE VOTRE VISITE\n';
            ticket += '='.repeat(32) + '\n';
            
            const printWindow = window.open('', '_blank');
            printWindow.document.write('<pre style="font-family: monospace; font-size: 12px;">' + ticket + '</pre>');
            printWindow.document.close();
            printWindow.print();
        }
        
        function openCashDrawer() {
            console.log('🔔 OUVERTURE DU TIROIR-CAISSE');
            fetch('http://localhost:9100/open', {method: 'POST', mode: 'no-cors'}).catch(e => {});
            const payBtn = document.querySelector('.btn-pay');
            if (payBtn) {
                payBtn.style.transform = 'scale(0.98)';
                setTimeout(() => payBtn.style.transform = '', 300);
            }
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        updateCartDisplay();
        
        <?php if (isset($flash) && $flash): ?>
        alert('<?php echo addslashes($flash['message']); ?>');
        <?php endif; ?>
    </script>
</body>
</html>