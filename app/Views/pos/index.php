<?php
// Vue POS - Point de Vente - Version avec Scanner SA9007
?>
<style>
    :root {
        --primary: #667eea;
        --primary-dark: #5a67d8;
        --success: #10b981;
        --danger: #ef4444;
        --warning: #f59e0b;
        --dark: #2c3e50;
        --gray: #6c757d;
        --light: #f8f9fa;
    }
    
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }
    
    body {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
        background: #f0f2f5;
        overflow-x: hidden;
    }
    
    .pos-container {
        display: flex;
        gap: 25px;
        padding: 20px;
        min-height: calc(100vh - 100px);
        max-width: 1600px;
        margin: 0 auto;
    }
    
    /* Panel des produits */
    .products-panel {
        flex: 2;
        background: white;
        border-radius: 20px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }
    
    .products-header {
        padding: 20px;
        background: white;
        border-bottom: 1px solid #e9ecef;
    }
    
    .search-bar {
        position: relative;
    }
    
    .search-bar input {
        width: 100%;
        padding: 14px 20px 14px 50px;
        font-size: 16px;
        border: 2px solid #e9ecef;
        border-radius: 50px;
        transition: all 0.3s;
        background: var(--light);
    }
    
    .search-bar input:focus {
        outline: none;
        border-color: var(--primary);
        background: white;
    }
    
    .search-bar i {
        position: absolute;
        left: 20px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--gray);
    }
    
    /* Scanner status bar */
    .scanner-status {
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
        color: white;
        padding: 10px 20px;
        border-radius: 10px;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 10px;
    }
    
    .scanner-status i {
        font-size: 20px;
        animation: pulse 1.5s infinite;
    }
    
    @keyframes pulse {
        0% { opacity: 0.5; }
        50% { opacity: 1; }
        100% { opacity: 0.5; }
    }
    
    .scanner-input-area {
        background: white;
        border-radius: 10px;
        padding: 10px;
        margin-bottom: 15px;
        border: 2px dashed var(--primary);
    }
    
    .scanner-input-area input {
        width: 100%;
        padding: 12px;
        border: 1px solid #e9ecef;
        border-radius: 8px;
        font-size: 16px;
        text-align: center;
        letter-spacing: 1px;
    }
    
    .scanner-input-area input:focus {
        outline: none;
        border-color: var(--primary);
    }
    
    .last-scanned {
        font-size: 12px;
        color: var(--gray);
        margin-top: 5px;
        text-align: center;
    }
    
    .products-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: 20px;
        padding: 20px;
        max-height: calc(100vh - 400px);
        overflow-y: auto;
    }
    
    .product-card {
        background: white;
        border-radius: 15px;
        overflow: hidden;
        cursor: pointer;
        transition: all 0.3s;
        border: 1px solid #e9ecef;
        position: relative;
    }
    
    .product-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        border-color: var(--primary);
    }
    
    .product-card:active {
        transform: scale(0.98);
    }
    
    .product-image {
        height: 120px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
    }
    
    .product-image i {
        font-size: 48px;
        color: white;
        opacity: 0.8;
    }
    
    .product-stock {
        position: absolute;
        top: 10px;
        right: 10px;
        background: rgba(0,0,0,0.7);
        color: white;
        padding: 4px 8px;
        border-radius: 20px;
        font-size: 11px;
    }
    
    .product-stock.low {
        background: var(--warning);
    }
    
    .product-info {
        padding: 12px;
        text-align: center;
    }
    
    .product-name {
        font-weight: 600;
        font-size: 14px;
        margin-bottom: 5px;
        color: var(--dark);
        word-break: break-word;
    }
    
    .product-price {
        font-size: 18px;
        font-weight: bold;
        color: var(--primary);
    }
    
    /* Notification toast */
    .toast-notification {
        position: fixed;
        bottom: 20px;
        right: 20px;
        background: var(--success);
        color: white;
        padding: 15px 25px;
        border-radius: 10px;
        z-index: 9999;
        animation: slideIn 0.3s ease-out;
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    }
    
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    /* Cart panel */
    .cart-panel {
        flex: 1;
        background: white;
        border-radius: 20px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        display: flex;
        flex-direction: column;
        overflow: hidden;
        position: sticky;
        top: 20px;
        height: fit-content;
        max-height: calc(100vh - 40px);
    }
    
    .cart-header {
        padding: 20px;
        background: linear-gradient(135deg, var(--dark) 0%, #1a252f 100%);
        color: white;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
    }
    
    .cart-header h3 {
        margin: 0;
        font-size: 18px;
    }
    
    .clear-cart {
        background: rgba(255,255,255,0.2);
        border: none;
        color: white;
        padding: 8px 15px;
        border-radius: 30px;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .clear-cart:active {
        transform: scale(0.95);
    }
    
    .clear-cart:hover {
        background: var(--danger);
    }
    
    .client-section {
        padding: 15px 20px;
        background: var(--light);
        border-bottom: 1px solid #e9ecef;
    }
    
    .client-search-group {
        display: flex;
        gap: 10px;
        margin-bottom: 10px;
        flex-wrap: wrap;
    }
    
    .client-search-group input {
        flex: 1;
        padding: 10px 15px;
        border: 1px solid #e9ecef;
        border-radius: 30px;
        font-size: 14px;
        min-width: 120px;
    }
    
    .client-search-group button {
        padding: 10px 15px;
        border: none;
        border-radius: 30px;
        background: var(--primary);
        color: white;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .client-search-group button:active {
        transform: scale(0.95);
    }
    
    .client-info {
        font-size: 13px;
        color: var(--gray);
        padding: 8px 12px;
        background: white;
        border-radius: 30px;
        word-break: break-word;
    }
    
    .devise-section {
        padding: 12px 20px;
        background: white;
        border-bottom: 1px solid #e9ecef;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
    }
    
    .devise-section select {
        padding: 8px 15px;
        border: 1px solid #e9ecef;
        border-radius: 30px;
        font-size: 13px;
    }
    
    .cart-items {
        flex: 1;
        overflow-y: auto;
        padding: 10px;
        max-height: 300px;
    }
    
    .cart-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px;
        border-bottom: 1px solid #e9ecef;
        flex-wrap: wrap;
    }
    
    .cart-item-info {
        flex: 1;
        min-width: 120px;
    }
    
    .cart-item-name {
        font-weight: 600;
        font-size: 14px;
        word-break: break-word;
    }
    
    .cart-item-price {
        font-size: 12px;
        color: var(--gray);
    }
    
    .cart-item-quantity {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .cart-item-quantity button {
        width: 32px;
        height: 32px;
        border: none;
        border-radius: 8px;
        background: var(--light);
        cursor: pointer;
        font-weight: bold;
        transition: all 0.3s;
    }
    
    .cart-item-quantity button:active {
        transform: scale(0.9);
    }
    
    .cart-item-quantity button:hover {
        background: var(--primary);
        color: white;
    }
    
    .cart-item-quantity span {
        min-width: 30px;
        text-align: center;
        font-weight: 600;
    }
    
    .cart-item-total {
        font-weight: bold;
        font-size: 14px;
        color: var(--primary);
        min-width: 100px;
        text-align: right;
    }
    
    .cart-summary {
        padding: 20px;
        background: var(--light);
        border-top: 1px solid #e9ecef;
    }
    
    .summary-line {
        display: flex;
        justify-content: space-between;
        padding: 8px 0;
        font-size: 14px;
        flex-wrap: wrap;
        gap: 10px;
    }
    
    .summary-line.total {
        font-size: 20px;
        font-weight: bold;
        color: var(--primary);
        border-top: 2px solid #e9ecef;
        margin-top: 10px;
        padding-top: 15px;
    }
    
    .payment-section select,
    .payment-section input {
        width: 100%;
        padding: 12px 15px;
        margin: 8px 0;
        border: 1px solid #e9ecef;
        border-radius: 12px;
        font-size: 16px;
    }
    
    .change-display {
        background: #d4edda;
        padding: 12px;
        border-radius: 12px;
        text-align: center;
        margin: 10px 0;
    }
    
    .btn-pay {
        width: 100%;
        padding: 14px;
        background: linear-gradient(135deg, var(--success) 0%, #059669 100%);
        color: white;
        border: none;
        border-radius: 12px;
        font-size: 16px;
        font-weight: bold;
        cursor: pointer;
        margin-top: 15px;
        transition: all 0.3s;
    }
    
    .btn-pay:active {
        transform: scale(0.98);
    }
    
    .btn-pay:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
    
    .empty-cart {
        text-align: center;
        padding: 50px 20px;
        color: var(--gray);
    }
    
    /* Modal */
    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        z-index: 2000;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }
    
    .modal.active {
        display: flex;
    }
    
    .modal-content {
        background: white;
        border-radius: 20px;
        width: 90%;
        max-width: 500px;
        max-height: 80vh;
        overflow-y: auto;
    }
    
    .modal-header {
        padding: 20px;
        background: linear-gradient(135deg, var(--dark) 0%, #1a252f 100%);
        color: white;
        border-radius: 20px 20px 0 0;
    }
    
    .modal-body {
        padding: 20px;
    }
    
    .modal-body input,
    .modal-body textarea {
        width: 100%;
        padding: 12px;
        margin: 8px 0;
        border: 1px solid #e9ecef;
        border-radius: 10px;
        font-size: 16px;
    }
    
    .modal-footer {
        padding: 20px;
        display: flex;
        gap: 10px;
        border-top: 1px solid #e9ecef;
        flex-wrap: wrap;
    }
    
    .modal-footer button {
        flex: 1;
        min-width: 120px;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .pos-container {
            flex-direction: column;
            padding: 10px;
        }
        
        .cart-panel {
            position: static;
        }
        
        .products-grid {
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            max-height: 400px;
        }
        
        .scanner-status {
            font-size: 12px;
        }
    }
</style>

<div class="pos-container">
    <div class="products-panel">
        <div class="products-header">
            <!-- Scanner status -->
            <div class="scanner-status">
                <div>
                    <i class="fas fa-qrcode"></i> Scanner SA9007 actif
                </div>
                <div>
                    <i class="fas fa-check-circle"></i> Prêt à scanner
                </div>
            </div>
            
            <!-- Zone de scan (cachée mais active) -->
            <div class="scanner-input-area">
                <input type="text" 
                       id="barcodeScanner" 
                       placeholder="🎯 Scannez un code-barres ici..." 
                       autofocus>
                <div class="last-scanned" id="lastScanned">
                    <i class="fas fa-info-circle"></i> Dernier scan: aucun
                </div>
            </div>
            
            <div class="search-bar">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="🔍 Rechercher un produit...">
            </div>
        </div>
        
        <?php if ($noProductsMessage): ?>
        <div class="alert alert-warning m-3"><?php echo $noProductsMessage; ?></div>
        <?php endif; ?>
        
        <div class="products-grid" id="productsGrid">
            <?php foreach ($products as $p): ?>
            <div class="product-card" 
                 data-id="<?php echo $p['id_produit']; ?>" 
                 data-barcode="<?php echo htmlspecialchars($p['code_barre'] ?? ''); ?>"
                 data-name="<?php echo htmlspecialchars($p['nom_produit']); ?>" 
                 data-price="<?php echo $p['prix_vente_ttc']; ?>" 
                 data-stock="<?php echo $p['stock_actuel']; ?>">
                <div class="product-image">
                    <i class="fas fa-box"></i>
                    <div class="product-stock <?php echo $p['stock_actuel'] <= 5 ? 'low' : ''; ?>">
                        📦 <?php echo $p['stock_actuel']; ?>
                    </div>
                </div>
                <div class="product-info">
                    <div class="product-name"><?php echo htmlspecialchars($p['nom_produit']); ?></div>
                    <div class="product-price"><?php echo number_format($p['prix_vente_ttc'], 0, ',', ' '); ?> G</div>
                    <?php if (!empty($p['code_barre'])): ?>
                    <div class="product-barcode" style="font-size: 10px; color: #999; margin-top: 5px;">
                        <i class="fas fa-barcode"></i> <?php echo htmlspecialchars($p['code_barre']); ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <div class="cart-panel">
        <div class="cart-header">
            <h3><i class="fas fa-shopping-cart"></i> Panier</h3>
            <button class="clear-cart" onclick="clearCart()"><i class="fas fa-trash"></i> Vider</button>
        </div>
        
        <div class="client-section">
            <div class="client-search-group">
                <input type="text" id="clientSearch" placeholder="Client (nom/tel)">
                <button onclick="searchClient()"><i class="fas fa-search"></i> Chercher</button>
                <button onclick="showNewClientModal()"><i class="fas fa-plus"></i> Nouveau</button>
            </div>
            <div id="clientInfo" class="client-info">
                <i class="fas fa-user"></i> Vente au comptoir
            </div>
        </div>
        
        <div class="devise-section">
            <span><i class="fas fa-money-bill"></i> Devise:</span>
            <select id="deviseSelector" onchange="switchDevise()">
                <?php foreach ($devises as $d): ?>
                <option value="<?php echo $d['code']; ?>" <?php echo $currentDevise == $d['code'] ? 'selected' : ''; ?>>
                    <?php echo $d['code']; ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="cart-items" id="cartItems">
            <div class="empty-cart"><i class="fas fa-shopping-basket"></i><p>Panier vide</p></div>
        </div>
        
        <div class="cart-summary">
            <div class="summary-line"><span>Sous-total HT:</span><span id="subtotal">0 G</span></div>
            <div class="summary-line"><span>TVA (18%):</span><span id="tax">0 G</span></div>
            <div class="summary-line total"><span>TOTAL TTC:</span><span id="total">0 G</span></div>
            
            <div class="payment-section">
                <select id="paymentMethod">
                    <option value="cash">💰 Espèces</option>
                    <option value="card">💳 Carte</option>
                    <option value="mobile">📱 Mobile Money</option>
                </select>
                <input type="number" id="amountReceived" placeholder="Montant reçu">
                <div id="changeDisplay" class="change-display" style="display:none">
                    💵 Monnaie: <strong id="change">0</strong> G
                </div>
                <button class="btn-pay" onclick="checkout()">✅ PAYER</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Nouveau Client -->
<div id="newClientModal" class="modal">
    <div class="modal-content">
        <div class="modal-header"><h4><i class="fas fa-user-plus"></i> Nouveau client</h4></div>
        <div class="modal-body">
            <form id="newClientForm">
                <input type="text" name="nom" placeholder="Nom *" required>
                <input type="text" name="prenom" placeholder="Prénom">
                <input type="tel" name="telephone" placeholder="Téléphone">
                <input type="email" name="email" placeholder="Email">
                <textarea name="adresse" placeholder="Adresse" rows="3"></textarea>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn-pay" style="background:var(--success)" onclick="createNewClient()">Enregistrer</button>
            <button class="clear-cart" style="background:var(--danger)" onclick="closeNewClientModal()">Annuler</button>
        </div>
    </div>
</div>

<script>
let cart = [];
let currentClientId = null;
let currentClientName = null;
let currentDevise = '<?php echo $currentDevise; ?>';
let currentUserName = '<?php echo addslashes($userName); ?>';
let currentMagasinName = '<?php echo addslashes($currentMagasin['nom_magasin'] ?? ''); ?>';
let currentUserRole = '<?php echo $_SESSION['user_role'] ?? 'Caissier'; ?>';
let scanTimeout = null;
let lastScannedBarcode = '';

// Mapping des codes-barres vers les produits
const productBarcodeMap = new Map();
document.querySelectorAll('.product-card').forEach(card => {
    const barcode = card.dataset.barcode;
    if (barcode) {
        productBarcodeMap.set(barcode, {
            id: parseInt(card.dataset.id),
            name: card.dataset.name,
            price: parseFloat(card.dataset.price),
            stock: parseInt(card.dataset.stock),
            element: card
        });
    }
});

// Gestion du scanner
const scannerInput = document.getElementById('barcodeScanner');
const lastScannedDiv = document.getElementById('lastScanned');

// Fonction pour afficher une notification
function showNotification(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = 'toast-notification';
    toast.style.background = type === 'success' ? 'var(--success)' : 'var(--danger)';
    toast.innerHTML = `<i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i> ${message}`;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.remove();
    }, 2000);
}

// Fonction pour ajouter un produit par code-barres
function addProductByBarcode(barcode) {
    // Nettoyer le code-barres (enlever les espaces, retours chariot, etc.)
    barcode = barcode.trim();
    
    if (!barcode) return;
    
    const product = productBarcodeMap.get(barcode);
    
    if (!product) {
        showNotification(`❌ Produit non trouvé: ${barcode}`, 'error');
        lastScannedDiv.innerHTML = `<i class="fas fa-times-circle"></i> Dernier scan: ${barcode} (non trouvé)`;
        return;
    }
    
    // Vérifier le stock
    if (product.stock <= 0) {
        showNotification(`⚠️ Stock insuffisant pour ${product.name} !`, 'error');
        return;
    }
    
    // Ajouter au panier
    const existing = cart.find(item => item.id === product.id);
    if (existing) {
        if (existing.quantity + 1 <= product.stock) {
            existing.quantity++;
            showNotification(`➕ ${product.name} x${existing.quantity}`);
        } else {
            showNotification(`⚠️ Stock maximum atteint pour ${product.name} !`, 'error');
            return;
        }
    } else {
        cart.push({
            id: product.id, 
            name: product.name, 
            price: product.price, 
            quantity: 1, 
            stock: product.stock
        });
        showNotification(`✅ ${product.name} ajouté au panier`);
    }
    
    // Animer le produit scanné
    if (product.element) {
        product.element.style.transform = 'scale(0.95)';
        product.element.style.borderColor = 'var(--success)';
        setTimeout(() => {
            product.element.style.transform = '';
            setTimeout(() => {
                product.element.style.borderColor = '#e9ecef';
            }, 300);
        }, 200);
    }
    
    updateCartDisplay();
    saveCart();
    
    lastScannedDiv.innerHTML = `<i class="fas fa-check-circle"></i> Dernier scan: ${product.name} (${barcode})`;
}

// Écouter les scans du code-barres
scannerInput.addEventListener('focus', function() {
    this.select();
});

scannerInput.addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        const barcode = this.value;
        if (barcode) {
            addProductByBarcode(barcode);
            this.value = '';
        }
    }
});

// Pour les scanners qui envoient tout d'un coup (pas de Enter)
let barcodeBuffer = '';
let lastKeyTime = 0;

scannerInput.addEventListener('keydown', function(e) {
    const currentTime = new Date().getTime();
    
    // Si le délai entre les touches est > 50ms, c'est probablement une saisie manuelle
    if (currentTime - lastKeyTime > 50 && barcodeBuffer.length > 0) {
        barcodeBuffer = '';
    }
    lastKeyTime = currentTime;
});

scannerInput.addEventListener('keyup', function(e) {
    if (e.key === 'Enter') {
        // Déjà traité dans keypress
        return;
    }
    
    // Accumuler les caractères du code-barres
    if (e.key.length === 1 || e.key === 'Enter') {
        barcodeBuffer += e.key;
        
        // Simuler un timeout pour traiter le code-barres complet
        clearTimeout(scanTimeout);
        scanTimeout = setTimeout(() => {
            if (barcodeBuffer.length > 3) { // Éviter les faux positifs
                addProductByBarcode(barcodeBuffer);
                scannerInput.value = '';
            }
            barcodeBuffer = '';
        }, 50);
    }
});

// Garder le focus sur le champ scanner en permanence
function keepScannerFocus() {
    if (document.activeElement !== scannerInput && 
        !document.activeElement?.classList?.contains('modal') &&
        !document.getElementById('newClientModal')?.classList?.contains('active')) {
        scannerInput.focus();
    }
}

// Focus automatique
setInterval(keepScannerFocus, 1000);
scannerInput.focus();

// Empêcher la perte de focus au clic sur d'autres éléments
document.addEventListener('click', function(e) {
    if (!e.target.closest('.modal') && 
        e.target.id !== 'barcodeScanner' &&
        e.target.type !== 'text' &&
        e.target.type !== 'number' &&
        e.target.tagName !== 'INPUT' &&
        e.target.tagName !== 'TEXTAREA' &&
        e.target.tagName !== 'SELECT') {
        scannerInput.focus();
    }
});

// Initialisation
updateCartDisplay();

// Ajout manuel au panier (clic sur produit)
document.querySelectorAll('.product-card').forEach(card => {
    card.addEventListener('click', () => {
        const id = parseInt(card.dataset.id);
        const name = card.dataset.name;
        const price = parseFloat(card.dataset.price);
        const stock = parseInt(card.dataset.stock);
        
        if (stock <= 0) {
            showNotification('⚠️ Stock insuffisant pour ce produit !', 'error');
            return;
        }
        
        const existing = cart.find(item => item.id === id);
        if (existing) {
            if (existing.quantity + 1 <= stock) {
                existing.quantity++;
                showNotification(`➕ ${name} x${existing.quantity}`);
            } else {
                showNotification(`⚠️ Stock maximum atteint pour ${name} !`, 'error');
                return;
            }
        } else {
            cart.push({id, name, price, quantity: 1, stock});
            showNotification(`✅ ${name} ajouté au panier`);
        }
        
        updateCartDisplay();
        saveCart();
        
        card.style.transform = 'scale(0.98)';
        setTimeout(() => { card.style.transform = ''; }, 200);
    });
});

function updateCartDisplay() {
    const container = document.getElementById('cartItems');
    let subtotal = 0;
    
    if (cart.length === 0) {
        container.innerHTML = '<div class="empty-cart"><i class="fas fa-shopping-basket"></i><p>Panier vide</p></div>';
        document.getElementById('subtotal').innerHTML = '0 ' + currentDevise;
        document.getElementById('tax').innerHTML = '0 ' + currentDevise;
        document.getElementById('total').innerHTML = '0 ' + currentDevise;
        return;
    }
    
    let html = '';
    cart.forEach((item, idx) => {
        const itemTotal = item.price * item.quantity;
        subtotal += itemTotal;
        html += `
            <div class="cart-item">
                <div class="cart-item-info">
                    <div class="cart-item-name">${escapeHtml(item.name)}</div>
                    <div class="cart-item-price">${formatPrice(item.price)} ${currentDevise}</div>
                </div>
                <div class="cart-item-quantity">
                    <button onclick="updateQty(${idx}, -1)">-</button>
                    <span>${item.quantity}</span>
                    <button onclick="updateQty(${idx}, 1)">+</button>
                </div>
                <div class="cart-item-total">${formatPrice(itemTotal)} ${currentDevise}</div>
            </div>
        `;
    });
    container.innerHTML = html;
    
    const tax = subtotal * 0.18;
    const total = subtotal + tax;
    
    document.getElementById('subtotal').innerHTML = formatPrice(subtotal) + ' ' + currentDevise;
    document.getElementById('tax').innerHTML = formatPrice(tax) + ' ' + currentDevise;
    document.getElementById('total').innerHTML = formatPrice(total) + ' ' + currentDevise;
    
    const received = parseFloat(document.getElementById('amountReceived').value) || 0;
    if (received > 0) {
        const change = received - total;
        document.getElementById('change').innerHTML = formatPrice(Math.max(0, change));
        document.getElementById('changeDisplay').style.display = 'block';
    } else {
        document.getElementById('changeDisplay').style.display = 'none';
    }
}

function updateQty(index, delta) {
    if (!cart[index]) return;
    const newQty = cart[index].quantity + delta;
    if (newQty <= 0) {
        cart.splice(index, 1);
    } else if (newQty <= cart[index].stock) {
        cart[index].quantity = newQty;
    } else {
        showNotification('Stock insuffisant !', 'error');
        return;
    }
    updateCartDisplay();
    saveCart();
}

function clearCart() {
    if (confirm('Vider le panier ?')) {
        cart = [];
        updateCartDisplay();
        saveCart();
        showNotification('Panier vidé');
    }
}

function saveCart() {
    fetch('<?php echo \BASE_PATH; ?>/index.php?action=save_cart', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({cart: cart})
    }).catch(e => console.log('Erreur:', e));
}

function formatPrice(price) {
    return Math.round(price).toLocaleString('fr-FR');
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Recherche en temps réel
document.getElementById('searchInput').addEventListener('input', function() {
    const term = this.value.toLowerCase();
    document.querySelectorAll('.product-card').forEach(card => {
        const name = card.dataset.name.toLowerCase();
        card.style.display = name.includes(term) ? 'flex' : 'none';
    });
});

document.getElementById('amountReceived').addEventListener('input', updateCartDisplay);

function switchDevise() {
    const devise = document.getElementById('deviseSelector').value;
    fetch('<?php echo \BASE_PATH; ?>/index.php?action=switch_devise', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({devise: devise})
    }).then(() => location.reload());
}

function searchClient() {
    const term = document.getElementById('clientSearch').value;
    if (!term) { alert('Entrez un nom ou téléphone'); return; }
    fetch('<?php echo \BASE_PATH; ?>/index.php?action=search_client&term=' + encodeURIComponent(term))
        .then(r => r.json())
        .then(clients => {
            if (clients.length === 0) alert('Aucun client trouvé');
            else if (clients.length === 1) selectClient(clients[0]);
            else {
                let msg = 'Sélectionnez:\n';
                clients.forEach((c, i) => msg += `${i+1}. ${c.nom} ${c.prenom || ''} - ${c.telephone}\n`);
                const choice = prompt(msg);
                if (choice && clients[choice-1]) selectClient(clients[choice-1]);
            }
        });
}

function selectClient(client) {
    currentClientId = client.id_client;
    currentClientName = client.nom + ' ' + (client.prenom || '');
    document.getElementById('clientInfo').innerHTML = `<i class="fas fa-user-check"></i> ${currentClientName}`;
}

function showNewClientModal() { 
    document.getElementById('newClientModal').classList.add('active');
}
function closeNewClientModal() { 
    document.getElementById('newClientModal').classList.remove('active');
    scannerInput.focus();
}

function createNewClient() {
    const form = document.getElementById('newClientForm');
    const data = {};
    new FormData(form).forEach((v,k) => data[k]=v);
    if (!data.nom) { alert('Nom requis'); return; }
    fetch('<?php echo \BASE_PATH; ?>/index.php?action=create_client', {
        method: 'POST', headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
    }).then(r => r.json()).then(result => {
        if (result.success) {
            alert('Client créé: ' + result.code);
            closeNewClientModal();
            document.getElementById('clientSearch').value = result.code;
            searchClient();
        } else alert('Erreur');
    });
}

function printReceipt(saleData) {
    const date = new Date().toLocaleString('fr-FR');
    const invoiceNumber = saleData.invoice_number;
    const total = saleData.total;
    const received = saleData.received;
    const change = saleData.change;
    const paymentMethod = saleData.payment_method;
    
    const clientReceipt = `
        <div style="font-family: monospace; width: 300px; margin: 0 auto; padding: 20px;">
            <div style="text-align: center; border-bottom: 1px dashed #000; padding-bottom: 10px; margin-bottom: 10px;">
                <div style="font-size: 18px; font-weight: bold;">TOTAL FAMILY MULTI-SERVICES</div>
                <div style="font-size: 12px;">Votre partenaire auto de confiance</div>
                <div style="font-size: 10px;">📍 Rue Principale, Port-au-Prince, Haïti</div>
                <div style="font-size: 10px;">📞 +509 1234 5678 | 📧 contact@totalfamily.ht</div>
                <div style="font-size: 10px;">🏪 ${currentMagasinName}</div>
            </div>
            <div style="text-align: center; margin-bottom: 10px;">
                <strong>📄 FACTURE CLIENT</strong><br>
                N°: ${invoiceNumber}<br>
                ${date}
            </div>
            <div style="margin-bottom: 10px;">
                <strong>👤 CAISSIER:</strong> ${currentUserName} (${currentUserRole})<br>
                ${currentClientName ? `<strong>👥 CLIENT:</strong> ${currentClientName}<br>` : ''}
                <strong>💳 PAIEMENT:</strong> ${paymentMethod === 'cash' ? 'Espèces' : (paymentMethod === 'card' ? 'Carte' : 'Mobile Money')}
            </div>
            <div style="border-top: 1px dashed #000; border-bottom: 1px dashed #000; padding: 10px 0; margin: 10px 0;">
                <table style="width: 100%; font-size: 11px;">
                    <tr><th>Produit</th><th>Qté</th><th>Prix</th><th>Total</th></tr>
                    ${saleData.cart.map(item => `
                        <tr>
                            <td>${item.name.substring(0, 20)}</td>
                            <td style="text-align:center">${item.quantity}</td>
                            <td style="text-align:right">${formatPrice(item.price)}</td>
                            <td style="text-align:right">${formatPrice(item.price * item.quantity)}</td>
                        </tr>
                    `).join('')}
                </table>
            </div>
            <div style="margin: 10px 0;">
                <div style="display:flex; justify-content:space-between;"><span>SOUS-TOTAL HT:</span><span>${formatPrice(total / 1.18)} G</span></div>
                <div style="display:flex; justify-content:space-between;"><span>TVA (18%):</span><span>${formatPrice(total - (total / 1.18))} G</span></div>
                <div style="display:flex; justify-content:space-between; font-weight:bold;"><span>TOTAL TTC:</span><span>${formatPrice(total)} G</span></div>
                <div style="display:flex; justify-content:space-between;"><span>REÇU:</span><span>${formatPrice(received)} G</span></div>
                <div style="display:flex; justify-content:space-between;"><span>MONNAIE:</span><span>${formatPrice(change)} G</span></div>
            </div>
            <div style="text-align: center; border-top: 1px dashed #000; padding-top: 10px; margin-top: 10px;">
                <div>✅ MERCI DE VOTRE VISITE !</div>
            </div>
        </div>
    `;
    
    const printWindow = window.open('', '_blank', 'width=400,height=600');
    printWindow.document.write(`
        <html>
        <head><title>Impression - Auto-Parts</title></head>
        <body style="margin:0; padding:0;">${clientReceipt}</body>
        </html>
    `);
    printWindow.document.close();
    printWindow.print();
}

function checkout() {
    if (cart.length === 0) {
        alert('Panier vide !');
        return;
    }
    
    const total = cart.reduce((s, i) => s + (i.price * i.quantity), 0);
    const totalTTC = total + (total * 0.18);
    const received = parseFloat(document.getElementById('amountReceived').value) || 0;
    
    if (received < totalTTC) {
        alert('Montant insuffisant ! Total: ' + formatPrice(totalTTC) + ' ' + currentDevise);
        return;
    }
    
    const change = received - totalTTC;
    const paymentMethod = document.getElementById('paymentMethod').value;
    
    if (!confirm('Confirmer le paiement de ' + formatPrice(totalTTC) + ' ' + currentDevise + ' ?')) return;
    
    const payBtn = document.querySelector('.btn-pay');
    payBtn.disabled = true;
    payBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Traitement...';
    
    const data = {
        cart: cart,
        total: totalTTC,
        received: received,
        change: change,
        payment_method: paymentMethod,
        client_id: currentClientId,
        devise: currentDevise
    };
    
    fetch('/auto-parts-management/public/pos_payment.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            data.cart = cart;
            data.received = received;
            data.change = change;
            data.payment_method = paymentMethod;
            
            printReceipt(data);
            alert('✅ Vente effectuée !\nFacture: ' + data.invoice_number);
            
            cart = [];
            currentClientId = null;
            currentClientName = null;
            updateCartDisplay();
            document.getElementById('amountReceived').value = '';
            document.getElementById('changeDisplay').style.display = 'none';
            document.getElementById('clientInfo').innerHTML = '<i class="fas fa-user"></i> Vente au comptoir';
        } else {
            alert('❌ Erreur: ' + (data.message || 'Erreur inconnue'));
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('❌ Erreur lors du traitement: ' + error.message);
    })
    .finally(() => {
        payBtn.disabled = false;
        payBtn.innerHTML = '✅ PAYER';
        scannerInput.focus();
    });
}
</script>