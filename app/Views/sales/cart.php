<?php
$title = 'Panier - Point de vente';
include dirname(__DIR__) . '/layouts/header.php';

// Récupérer le panier depuis la session
$cart = $_SESSION['cart'] ?? [];
$total = 0;
foreach ($cart as $item) {
    $total += $item['price'] * $item['quantity'];
}
$totalTTC = $total + ($total * 0.18);
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h2 class="fw-bold mb-1">
                        <i class="fas fa-shopping-cart" style="color: #667eea;"></i> Mon panier
                    </h2>
                    <p class="text-muted">Révisez votre commande avant de finaliser</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="<?php echo \BASE_PATH; ?>/index.php?action=pos" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Ajouter des produits
                    </a>
                    <a href="<?php echo \BASE_PATH; ?>/index.php?action=dashboard" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Retour
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-list"></i> Articles dans le panier</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($cart)): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-shopping-cart fa-3x mb-3"></i>
                            <p>Votre panier est vide</p>
                            <a href="<?php echo \BASE_PATH; ?>/index.php?action=pos" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Ajouter des produits
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Produit</th>
                                        <th>Prix unitaire</th>
                                        <th>Quantité</th>
                                        <th>Remise</th>
                                        <th>Sous-total</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cart as $index => $item): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($item['name']); ?></strong><br>
                                            <small class="text-muted">Code: <?php echo htmlspecialchars($item['barcode'] ?? '-'); ?></small>
                                         </div>
                                         <div><?php echo number_format($item['price'], 0, ',', ' '); ?> G</div>
                                         <div style="min-width: 120px;">
                                            <div class="input-group">
                                                <button class="btn btn-sm btn-outline-secondary" onclick="updateQuantity(<?php echo $index; ?>, -1)">-</button>
                                                <input type="number" id="qty_<?php echo $index; ?>" value="<?php echo $item['quantity']; ?>" 
                                                       class="form-control form-control-sm text-center" style="width: 60px;" 
                                                       onchange="updateQuantity(<?php echo $index; ?>, 0, this.value)">
                                                <button class="btn btn-sm btn-outline-secondary" onclick="updateQuantity(<?php echo $index; ?>, 1)">+</button>
                                            </div>
                                        </div>
                                         <div>
                                            <input type="number" id="discount_<?php echo $index; ?>" value="<?php echo $item['discount'] ?? 0; ?>" 
                                                   class="form-control form-control-sm" style="width: 80px;" step="0.01"
                                                   onchange="updateDiscount(<?php echo $index; ?>, this.value)">
                                            <small class="text-muted">%</small>
                                        </div>
                                        <div class="fw-bold" id="subtotal_<?php echo $index; ?>">
                                            <?php 
                                            $discount = ($item['discount'] ?? 0);
                                            $subtotal = $item['price'] * $item['quantity'] * (1 - $discount / 100);
                                            echo number_format($subtotal, 0, ',', ' ') . ' G';
                                            ?>
                                        </div>
                                         <div>
                                            <button class="btn btn-sm btn-danger" onclick="removeItem(<?php echo $index; ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </tbody>
                             </div>
                        </div>
                        
                        <div class="text-end mt-3">
                            <button class="btn btn-danger" onclick="clearCart()">
                                <i class="fas fa-trash-alt"></i> Vider le panier
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-receipt"></i> Résumé de la commande</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Client</label>
                        <select id="clientSelect" class="form-select">
                            <option value="">-- Client non renseigné --</option>
                            <?php
                            $clients = (new App\Models\Client())->getAll();
                            foreach ($clients as $client): ?>
                            <option value="<?php echo $client['id_client']; ?>">
                                <?php echo htmlspecialchars($client['code_client'] . ' - ' . $client['nom'] . ' ' . ($client['prenom'] ?? '')); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Code promo</label>
                        <div class="input-group">
                            <input type="text" id="codePromo" class="form-control" placeholder="Code promo">
                            <button class="btn btn-outline-primary" onclick="applyPromo()">Appliquer</button>
                        </div>
                        <small id="promoMessage" class="text-muted"></small>
                    </div>
                    
                    <hr>
                    
                    <div class="d-flex justify-content-between mb-2">
                        <span>Sous-total HT:</span>
                        <span id="subtotalHT"><?php echo number_format($total, 0, ',', ' '); ?> G</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>TVA (18%):</span>
                        <span id="tvaAmount"><?php echo number_format($total * 0.18, 0, ',', ' '); ?> G</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Remise:</span>
                        <span id="discountAmount">0 G</span>
                    </div>
                    <div class="d-flex justify-content-between mb-3 fw-bold fs-5">
                        <span>TOTAL TTC:</span>
                        <span id="totalTTC" class="text-primary"><?php echo number_format($totalTTC, 0, ',', ' '); ?> G</span>
                    </div>
                    
                    <hr>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Mode de paiement</label>
                        <select id="paymentMethod" class="form-select">
                            <option value="cash">💰 Espèces</option>
                            <option value="card">💳 Carte bancaire</option>
                            <option value="mobile">📱 Mobile Money</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Montant reçu</label>
                        <input type="number" id="amountReceived" class="form-control" placeholder="Montant reçu" step="100">
                    </div>
                    
                    <div id="changeDisplay" class="alert alert-success text-center" style="display: none;">
                        💵 Monnaie à rendre: <strong id="changeAmount">0</strong> G
                    </div>
                    
                    <button class="btn btn-success btn-lg w-100" onclick="checkout()">
                        <i class="fas fa-check-circle"></i> Finaliser la commande
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let cart = <?php echo json_encode($cart); ?>;
let appliedPromo = null;

function updateCartDisplay() {
    location.reload();
}

function updateQuantity(index, delta, newValue = null) {
    if (newValue !== null) {
        cart[index].quantity = parseInt(newValue);
    } else {
        let newQty = cart[index].quantity + delta;
        if (newQty >= 1) {
            cart[index].quantity = newQty;
        }
    }
    saveCart();
    updateTotals();
    location.reload();
}

function updateDiscount(index, value) {
    cart[index].discount = parseFloat(value);
    saveCart();
    updateTotals();
    location.reload();
}

function removeItem(index) {
    if (confirm('Supprimer cet article ?')) {
        cart.splice(index, 1);
        saveCart();
        location.reload();
    }
}

function clearCart() {
    if (confirm('Vider tout le panier ?')) {
        cart = [];
        saveCart();
        location.reload();
    }
}

function saveCart() {
    fetch('<?php echo \BASE_PATH; ?>/index.php?action=save_cart', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({cart: cart})
    });
}

function updateTotals() {
    let subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity * (1 - (item.discount || 0) / 100)), 0);
    let tva = subtotal * 0.18;
    let total = subtotal + tva;
    let discountAmount = 0;
    
    if (appliedPromo) {
        if (appliedPromo.type === 'pourcentage') {
            discountAmount = total * (appliedPromo.valeur / 100);
            total = total - discountAmount;
        } else {
            discountAmount = appliedPromo.valeur;
            total = total - discountAmount;
        }
    }
    
    document.getElementById('subtotalHT').innerHTML = formatPrice(subtotal) + ' G';
    document.getElementById('tvaAmount').innerHTML = formatPrice(tva) + ' G';
    document.getElementById('discountAmount').innerHTML = '-' + formatPrice(discountAmount) + ' G';
    document.getElementById('totalTTC').innerHTML = formatPrice(total) + ' G';
    
    let received = parseFloat(document.getElementById('amountReceived').value) || 0;
    if (received > 0) {
        let change = received - total;
        document.getElementById('changeAmount').innerHTML = formatPrice(Math.max(0, change));
        document.getElementById('changeDisplay').style.display = 'block';
    } else {
        document.getElementById('changeDisplay').style.display = 'none';
    }
}

function applyPromo() {
    let code = document.getElementById('codePromo').value;
    if (!code) {
        alert('Veuillez saisir un code promo');
        return;
    }
    
    fetch('<?php echo \BASE_PATH; ?>/index.php?action=check_promo&code=' + encodeURIComponent(code))
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                appliedPromo = data.promo;
                document.getElementById('promoMessage').innerHTML = '<span class="text-success">✅ Code appliqué !</span>';
                updateTotals();
            } else {
                document.getElementById('promoMessage').innerHTML = '<span class="text-danger">❌ ' + data.message + '</span>';
            }
        });
}

function checkout() {
    if (cart.length === 0) {
        alert('Panier vide');
        return;
    }
    
    let total = parseFloat(document.getElementById('totalTTC').innerHTML.replace(' G', '').replace(/\s/g, ''));
    let received = parseFloat(document.getElementById('amountReceived').value) || 0;
    
    if (received < total) {
        alert('Montant insuffisant ! Total: ' + formatPrice(total) + ' G');
        return;
    }
    
    let data = {
        cart: cart,
        id_client: document.getElementById('clientSelect').value,
        payment_method: document.getElementById('paymentMethod').value,
        amount_received: received,
        promo_code: document.getElementById('codePromo').value,
        total: total
    };
    
    fetch('<?php echo \BASE_PATH; ?>/index.php?action=process_sale', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✅ Vente effectuée !\nFacture: ' + data.invoice_number);
            window.location.href = '<?php echo \BASE_PATH; ?>/index.php?action=sales_invoice&id=' + data.sale_id;
        } else {
            alert('❌ Erreur: ' + data.message);
        }
    });
}

function formatPrice(price) {
    return Math.round(price).toLocaleString('fr-FR');
}

document.getElementById('amountReceived').addEventListener('input', updateTotals);
</script>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>