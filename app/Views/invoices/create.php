<?php
// Vue création de facture
?>
<style>
    .product-row {
        cursor: pointer;
        transition: background 0.3s;
    }
    .product-row:hover {
        background: #f8f9fa;
    }
    .cart-item {
        padding: 10px;
        border-bottom: 1px solid #e9ecef;
    }
    .cart-item:last-child {
        border-bottom: none;
    }
    .total-amount {
        font-size: 24px;
        font-weight: bold;
        color: #667eea;
    }
</style>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-file-invoice"></i> Créer une facture</h2>
        <a href="<?php echo \BASE_PATH; ?>/index.php?action=invoices" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Retour
        </a>
    </div>

    <div class="row">
        <!-- Panneau des produits -->
        <div class="col-md-7">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-boxes"></i> Produits</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <input type="text" id="searchProduct" class="form-control" placeholder="🔍 Rechercher un produit...">
                    </div>
                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Code</th>
                                    <th>Produit</th>
                                    <th>Prix</th>
                                    <th>Stock</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="productsList">
                                <?php foreach ($products as $product): ?>
                                <tr class="product-row" data-id="<?php echo $product['id_produit']; ?>" data-name="<?php echo htmlspecialchars($product['nom_produit']); ?>" data-price="<?php echo $product['prix_vente_ttc']; ?>" data-stock="<?php echo $product['stock_actuel']; ?>">
                                    <td><code><?php echo htmlspecialchars($product['code_barre']); ?></code></td>
                                    <td><?php echo htmlspecialchars($product['nom_produit']); ?></td>
                                    <td><?php echo number_format($product['prix_vente_ttc'], 0, ',', ' '); ?> G</td>
                                    <td><?php echo $product['stock_actuel']; ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-primary add-to-cart">
                                            <i class="fas fa-plus"></i> Ajouter
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Panneau du panier -->
        <div class="col-md-5">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-shopping-cart"></i> Facture en cours</h5>
                </div>
                <div class="card-body">
                    <!-- Sélection client -->
                    <div class="mb-3">
                        <label class="form-label">Client</label>
                        <select id="clientId" class="form-select">
                            <option value="">-- Client comptoir --</option>
                            <?php foreach ($clients as $client): ?>
                            <option value="<?php echo $client['id_client']; ?>">
                                <?php echo htmlspecialchars($client['code_client'] . ' - ' . $client['nom'] . ' ' . ($client['prenom'] ?? '')); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Liste des produits dans le panier -->
                    <div id="cartItems" style="max-height: 300px; overflow-y: auto;">
                        <div class="text-center text-muted py-4">Panier vide</div>
                    </div>

                    <!-- Totaux -->
                    <div class="mt-3 pt-3 border-top">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Sous-total HT:</span>
                            <span id="subtotal">0 G</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>TVA (18%):</span>
                            <span id="tax">0 G</span>
                        </div>
                        <div class="d-flex justify-content-between mb-3">
                            <strong>TOTAL TTC:</strong>
                            <strong id="total" class="total-amount">0 G</strong>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Mode de paiement</label>
                            <select id="paymentMethod" class="form-select">
                                <option value="especes">💰 Espèces</option>
                                <option value="carte">💳 Carte bancaire</option>
                                <option value="mobile_money">📱 Mobile Money</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Montant reçu</label>
                            <input type="number" id="amountReceived" class="form-control" placeholder="0" step="1">
                        </div>

                        <div id="changeDisplay" class="alert alert-success" style="display: none;">
                            Monnaie à rendre: <strong id="change">0</strong> G
                        </div>

                        <button class="btn btn-success w-100" onclick="createInvoice()">
                            <i class="fas fa-save"></i> Créer la facture
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let cart = [];

// Ajout au panier
document.querySelectorAll('.add-to-cart').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.stopPropagation();
        let row = this.closest('.product-row');
        let id = parseInt(row.dataset.id);
        let name = row.dataset.name;
        let price = parseFloat(row.dataset.price);
        let stock = parseInt(row.dataset.stock);
        
        let existing = cart.find(item => item.id === id);
        if (existing) {
            if (existing.quantity + 1 <= stock) {
                existing.quantity++;
            } else {
                alert('Stock insuffisant !');
                return;
            }
        } else {
            cart.push({id, name, price, quantity: 1});
        }
        updateCart();
    });
});

// Recherche de produits
document.getElementById('searchProduct').addEventListener('input', function() {
    let term = this.value.toLowerCase();
    let rows = document.querySelectorAll('#productsList .product-row');
    rows.forEach(row => {
        let text = row.textContent.toLowerCase();
        row.style.display = text.includes(term) ? '' : 'none';
    });
});

function updateCart() {
    let container = document.getElementById('cartItems');
    let subtotal = 0;
    
    if (cart.length === 0) {
        container.innerHTML = '<div class="text-center text-muted py-4">Panier vide</div>';
        document.getElementById('subtotal').innerHTML = '0 G';
        document.getElementById('tax').innerHTML = '0 G';
        document.getElementById('total').innerHTML = '0 G';
        return;
    }
    
    let html = '';
    cart.forEach((item, idx) => {
        let itemTotal = item.price * item.quantity;
        subtotal += itemTotal;
        html += `
            <div class="cart-item d-flex justify-content-between align-items-center">
                <div class="flex-grow-1">
                    <strong>${item.name}</strong><br>
                    <small>${formatPrice(item.price)} G x ${item.quantity}</small>
                </div>
                <div class="text-end">
                    <div>${formatPrice(itemTotal)} G</div>
                    <div>
                        <button class="btn btn-sm btn-outline-secondary" onclick="updateQty(${idx}, -1)">-</button>
                        <button class="btn btn-sm btn-outline-secondary" onclick="updateQty(${idx}, 1)">+</button>
                        <button class="btn btn-sm btn-outline-danger" onclick="removeItem(${idx})">×</button>
                    </div>
                </div>
            </div>
        `;
    });
    container.innerHTML = html;
    
    let tax = subtotal * 0.18;
    let total = subtotal + tax;
    
    document.getElementById('subtotal').innerHTML = formatPrice(subtotal) + ' G';
    document.getElementById('tax').innerHTML = formatPrice(tax) + ' G';
    document.getElementById('total').innerHTML = formatPrice(total) + ' G';
    
    updateChange();
}

function updateQty(index, delta) {
    let newQty = cart[index].quantity + delta;
    if (newQty <= 0) {
        cart.splice(index, 1);
    } else {
        cart[index].quantity = newQty;
    }
    updateCart();
}

function removeItem(index) {
    cart.splice(index, 1);
    updateCart();
}

function updateChange() {
    let totalText = document.getElementById('total').innerHTML;
    let total = parseFloat(totalText.replace(' G', '').replace(/\s/g, '')) || 0;
    let received = parseFloat(document.getElementById('amountReceived').value) || 0;
    
    if (received >= total && total > 0) {
        let change = received - total;
        document.getElementById('change').innerHTML = formatPrice(change);
        document.getElementById('changeDisplay').style.display = 'block';
    } else {
        document.getElementById('changeDisplay').style.display = 'none';
    }
}

function formatPrice(price) {
    return Math.round(price).toLocaleString('fr-FR');
}

document.getElementById('amountReceived').addEventListener('input', updateChange);

function createInvoice() {
    if (cart.length === 0) {
        alert('Ajoutez au moins un produit');
        return;
    }
    
    let totalText = document.getElementById('total').innerHTML;
    let total = parseFloat(totalText.replace(' G', '').replace(/\s/g, '')) || 0;
    let received = parseFloat(document.getElementById('amountReceived').value) || 0;
    
    if (received < total) {
        alert('Montant insuffisant ! Total: ' + formatPrice(total) + ' G');
        return;
    }
    
    let form = document.createElement('form');
    form.method = 'POST';
    form.action = '<?php echo \BASE_PATH; ?>/index.php?action=invoice_store';
    
    let clientId = document.createElement('input');
    clientId.type = 'hidden';
    clientId.name = 'client_id';
    clientId.value = document.getElementById('clientId').value;
    form.appendChild(clientId);
    
    let productsJson = document.createElement('input');
    productsJson.type = 'hidden';
    productsJson.name = 'products_json';
    productsJson.value = JSON.stringify(cart);
    form.appendChild(productsJson);
    
    let paymentMethod = document.createElement('input');
    paymentMethod.type = 'hidden';
    paymentMethod.name = 'payment_method';
    paymentMethod.value = document.getElementById('paymentMethod').value;
    form.appendChild(paymentMethod);
    
    let amountReceived = document.createElement('input');
    amountReceived.type = 'hidden';
    amountReceived.name = 'amount_received';
    amountReceived.value = received;
    form.appendChild(amountReceived);
    
    document.body.appendChild(form);
    form.submit();
}
</script>