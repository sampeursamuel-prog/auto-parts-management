<?php
$title = 'Entrée de stock';
include dirname(__DIR__) . '/layouts/header.php';
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2>📥 Nouvelle entrée de stock</h2>
                <a href="<?php echo \BASE_PATH; ?>/index.php?action=stock" class="btn btn-secondary">
                    ← Retour
                </a>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">📷 Scanner les produits</h5>
                </div>
                <div class="card-body">
                    <div class="input-group mb-3">
                        <input type="text" id="barcodeInput" class="form-control form-control-lg" placeholder="Scannez un code-barres..." autofocus>
                        <button class="btn btn-primary" onclick="searchProduct()">Rechercher</button>
                    </div>
                    
                    <div id="productInfo" style="display: none;" class="alert alert-info">
                        <div class="row">
                            <div class="col-md-8">
                                <strong id="productName"></strong><br>
                                <span id="productBarcode"></span>
                                <span id="productPrice"></span>
                                <span id="productLocation"></span>
                            </div>
                            <div class="col-md-4">
                                <input type="number" id="quantity" class="form-control" placeholder="Quantité" min="1" value="1">
                                <input type="hidden" id="productId">
                                <button class="btn btn-success mt-2 w-100" onclick="addToList()">Ajouter</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">🛒 Produits à réceptionner</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table" id="itemsTable">
                            <thead>
                                <tr>
                                    <th>Code-barres</th>
                                    <th>Produit</th>
                                    <th>Quantité</th>
                                    <th>Prix unitaire</th>
                                    <th>Total</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="itemsList">
                                <tr>
                                    <td colspan="6" class="text-center text-muted">Aucun produit scanné</td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr class="fw-bold">
                                    <td colspan="4" class="text-end">Total :</td>
                                    <td id="totalAmount">0 FCFA</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">📄 Informations fournisseur</h5>
                </div>
                <div class="card-body">
                    <form method="POST" id="entryForm">
                        <input type="hidden" name="items" id="itemsJson">
                        <div class="mb-3">
                            <label>Fournisseur</label>
                            <select name="fournisseur" class="form-select">
                                <option value="">-- Sélectionner --</option>
                                <?php foreach ($suppliers as $s): ?>
                                <option value="<?php echo $s['id_fournisseur']; ?>"><?php echo htmlspecialchars($s['nom_fournisseur']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label>N° Facture</label>
                            <input type="text" name="numero_facture" class="form-control" placeholder="Facultatif">
                        </div>
                        <div class="mb-3">
                            <label>Date facture</label>
                            <input type="date" name="date_facture" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label>Notes</label>
                            <textarea name="notes" class="form-control" rows="3" placeholder="Informations complémentaires..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-success w-100" onclick="prepareSubmit()">
                            ✅ Valider l'entrée
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let items = [];

function searchProduct() {
    let barcode = document.getElementById('barcodeInput').value.trim();
    if (!barcode) {
        alert('Veuillez scanner un code-barres');
        return;
    }
    
    fetch('<?php echo \BASE_PATH; ?>/index.php?action=stock_scan&barcode=' + encodeURIComponent(barcode))
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('productId').value = data.product.id_produit;
                document.getElementById('productName').innerHTML = data.product.nom_produit;
                document.getElementById('productBarcode').innerHTML = `<code>${data.product.code_barre}</code>`;
                document.getElementById('productPrice').innerHTML = `<br>💰 Prix achat: ${new Intl.NumberFormat('fr-FR').format(data.product.prix_achat_ht)} FCFA`;
                document.getElementById('productLocation').innerHTML = data.product.emplacement ? `<br>📍 ${data.product.emplacement}` : '';
                document.getElementById('productInfo').style.display = 'block';
                document.getElementById('quantity').focus();
            } else {
                alert('Produit non trouvé : ' + data.message);
                document.getElementById('barcodeInput').value = '';
                document.getElementById('barcodeInput').focus();
            }
        });
}

function addToList() {
    let id = document.getElementById('productId').value;
    let name = document.getElementById('productName').innerHTML;
    let barcode = document.getElementById('productBarcode').innerHTML.replace('<code>', '').replace('</code>', '');
    let price = parseFloat(document.querySelector('#productPrice').innerHTML.match(/\d+/g)?.join('') || 0);
    let quantity = parseInt(document.getElementById('quantity').value);
    
    if (!id || quantity <= 0) {
        alert('Quantité invalide');
        return;
    }
    
    let existing = items.find(item => item.id == id);
    if (existing) {
        existing.quantity += quantity;
    } else {
        items.push({
            id: id,
            code_barre: barcode,
            nom_produit: name,
            quantite: quantity,
            prix_unitaire: price
        });
    }
    
    updateItemsList();
    
    // Réinitialiser
    document.getElementById('barcodeInput').value = '';
    document.getElementById('productInfo').style.display = 'none';
    document.getElementById('barcodeInput').focus();
}

function updateItemsList() {
    let tbody = document.getElementById('itemsList');
    let total = 0;
    
    if (items.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">Aucun produit scanné</td></tr>';
        document.getElementById('totalAmount').innerHTML = '0 FCFA';
        return;
    }
    
    tbody.innerHTML = '';
    items.forEach((item, index) => {
        let subtotal = item.quantite * item.prix_unitaire;
        total += subtotal;
        
        tbody.innerHTML += `
            <tr>
                <td><code>${item.code_barre}</code></td>
                <td>${item.nom_produit}</td>
                <td>
                    <input type="number" value="${item.quantite}" min="1" class="form-control form-control-sm" style="width: 80px;" 
                           onchange="updateQuantity(${index}, this.value)">
                </td>
                <td>${new Intl.NumberFormat('fr-FR').format(item.prix_unitaire)} FCFA</td>
                <td>${new Intl.NumberFormat('fr-FR').format(subtotal)} FCFA</td>
                <td><button class="btn btn-sm btn-danger" onclick="removeItem(${index})">✖</button></td>
            </tr>
        `;
    });
    
    document.getElementById('totalAmount').innerHTML = new Intl.NumberFormat('fr-FR').format(total) + ' FCFA';
}

function updateQuantity(index, value) {
    let newQty = parseInt(value);
    if (newQty > 0) {
        items[index].quantite = newQty;
        updateItemsList();
    }
}

function removeItem(index) {
    items.splice(index, 1);
    updateItemsList();
}

function prepareSubmit() {
    document.getElementById('itemsJson').value = JSON.stringify(items);
    if (items.length === 0) {
        alert('Ajoutez au moins un produit');
        event.preventDefault();
        return false;
    }
    return true;
}

document.getElementById('barcodeInput').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        searchProduct();
    }
});
</script>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>