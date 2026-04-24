<?php
$title = 'Nouvelle cotation';
include dirname(__DIR__) . '/layouts/header.php';
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2><i class="fas fa-file-alt"></i> Nouvelle cotation / Proforma</h2>
                <a href="<?php echo \BASE_PATH; ?>/index.php?action=cotations" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Retour
                </a>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-file-invoice"></i> Informations générales</h5>
                </div>
                <div class="card-body">
                    <form id="cotationForm">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Client</label>
                                <select name="id_client" id="clientSelect" class="form-select">
                                    <option value="">-- Sélectionner un client --</option>
                                    <?php foreach ($clients as $client): ?>
                                    <option value="<?php echo $client['id_client']; ?>">
                                        <?php echo htmlspecialchars($client['code_client'] . ' - ' . $client['nom'] . ' ' . ($client['prenom'] ?? '')); ?>
                                        (<?php echo htmlspecialchars($client['telephone']); ?>)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">
                                    <a href="<?php echo \BASE_PATH; ?>/index.php?action=client_create" target="_blank">+ Nouveau client</a>
                                </small>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label fw-bold">Date de validité</label>
                                <input type="date" name="date_validite" id="dateValidite" class="form-control" value="<?php echo date('Y-m-d', strtotime('+30 days')); ?>">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label fw-bold">Remise globale (%)</label>
                                <input type="number" name="remise_globale" id="remiseGlobale" class="form-control" step="0.01" value="0">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" id="notes" class="form-control" rows="2" placeholder="Conditions générales, délai de livraison, etc."></textarea>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-boxes"></i> Articles</h5>
                </div>
                <div class="card-body">
                    <!-- Scanner -->
                    <div class="row mb-3">
                        <div class="col-md-8">
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="fas fa-qrcode text-primary"></i></span>
                                <input type="text" id="barcodeInput" class="form-control" placeholder="Scannez un code-barres...">
                                <button class="btn btn-primary" onclick="searchProduct()">Rechercher</button>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <select id="productSelect" class="form-select">
                                <option value="">-- Ou sélectionner un produit --</option>
                                <?php foreach ($products as $product): ?>
                                <option value="<?php echo $product['id_produit']; ?>" 
                                        data-name="<?php echo htmlspecialchars($product['nom_produit']); ?>"
                                        data-price="<?php echo $product['prix_vente_ht']; ?>"
                                        data-barcode="<?php echo $product['code_barre']; ?>">
                                    <?php echo htmlspecialchars($product['nom_produit']); ?> - <?php echo number_format($product['prix_vente_ht'], 0, ',', ' '); ?> G
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Formulaire d'ajout -->
                    <div id="addProductPanel" class="row mb-4" style="display: none;">
                        <div class="col-md-12">
                            <div class="card border-primary">
                                <div class="card-body">
                                    <h6>Ajouter un article</h6>
                                    <div class="row">
                                        <div class="col-md-4 mb-2">
                                            <input type="text" id="productName" class="form-control" placeholder="Produit" readonly>
                                            <input type="hidden" id="productId">
                                            <input type="hidden" id="productBarcode">
                                        </div>
                                        <div class="col-md-2 mb-2">
                                            <input type="number" id="productPrice" class="form-control" placeholder="Prix HT" step="1">
                                        </div>
                                        <div class="col-md-2 mb-2">
                                            <input type="number" id="productQuantity" class="form-control" placeholder="Quantité" value="1" min="1">
                                        </div>
                                        <div class="col-md-2 mb-2">
                                            <input type="number" id="productDiscount" class="form-control" placeholder="Remise %" step="0.01" value="0">
                                        </div>
                                        <div class="col-md-2 mb-2">
                                            <button class="btn btn-primary w-100" onclick="addItem()">Ajouter</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tableau des articles -->
                    <div class="table-responsive">
                        <table class="table table-bordered" id="itemsTable">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Produit</th>
                                    <th>Quantité</th>
                                    <th>Prix HT</th>
                                    <th>Remise</th>
                                    <th>Sous-total HT</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="itemsBody">
                                <tr>
                                    <td colspan="7" class="text-center text-muted">Aucun article</td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr class="table-light">
                                    <td colspan="5" class="text-end"><strong>Total HT:</strong></td>
                                    <td><strong id="totalHT">0 G</strong></td>
                                    <td></td>
                                </tr>
                                <tr class="table-light">
                                    <td colspan="5" class="text-end"><strong>TVA (18%):</strong></td>
                                    <td><strong id="totalTVA">0 G</strong></td>
                                    <td></td>
                                </tr>
                                <tr class="table-light">
                                    <td colspan="5" class="text-end"><strong>Total TTC:</strong></td>
                                    <td><strong id="totalTTC">0 G</strong></td>
                                    <td></td>
                                </tr>
                                <tr class="table-warning">
                                    <td colspan="5" class="text-end"><strong>Remise globale:</strong></td>
                                    <td><strong id="globalDiscount">0 G</strong></td>
                                    <td></td>
                                </tr>
                                <tr class="table-success">
                                    <td colspan="5" class="text-end"><strong>Net à payer:</strong></td>
                                    <td><strong id="netToPay" style="font-size: 1.2em;">0 G</strong></td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    
                    <div class="text-end mt-3">
                        <button type="button" class="btn btn-success btn-lg" onclick="saveCotation()">
                            <i class="fas fa-save"></i> Enregistrer la cotation
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let items = [];

// Scanner code-barres
let barcodeBuffer = '';
let lastTime = 0;
const barcodeInput = document.getElementById('barcodeInput');
barcodeInput.addEventListener('keypress', function(e) {
    const now = Date.now();
    if (now - lastTime > 100) barcodeBuffer = '';
    lastTime = now;
    
    if (e.key === 'Enter') {
        e.preventDefault();
        searchProductByBarcode(barcodeBuffer);
        barcodeBuffer = '';
        this.value = '';
    } else {
        barcodeBuffer += e.key;
        this.value = barcodeBuffer;
    }
});

function searchProduct() {
    let barcode = document.getElementById('barcodeInput').value.trim();
    if (barcode) {
        searchProductByBarcode(barcode);
    } else {
        let productId = document.getElementById('productSelect').value;
        if (productId) {
            let option = document.getElementById('productSelect').options[document.getElementById('productSelect').selectedIndex];
            showAddPanel(
                productId,
                option.dataset.name,
                option.dataset.price,
                option.dataset.barcode
            );
        }
    }
}

function searchProductByBarcode(barcode) {
    fetch('<?php echo \BASE_PATH; ?>/index.php?action=product_get_by_barcode&barcode=' + encodeURIComponent(barcode))
        .then(response => response.json())
        .then(data => {
            if (data && data.id_produit) {
                showAddPanel(data.id_produit, data.nom_produit, data.prix_vente_ht, data.code_barre);
            } else {
                alert('Produit non trouvé');
            }
        });
}

function showAddPanel(id, name, price, barcode) {
    document.getElementById('productId').value = id;
    document.getElementById('productName').value = name;
    document.getElementById('productBarcode').value = barcode;
    document.getElementById('productPrice').value = price;
    document.getElementById('productQuantity').value = 1;
    document.getElementById('productDiscount').value = 0;
    document.getElementById('addProductPanel').style.display = 'block';
    document.getElementById('productQuantity').focus();
}

function addItem() {
    let id = document.getElementById('productId').value;
    let name = document.getElementById('productName').value;
    let barcode = document.getElementById('productBarcode').value;
    let price = parseFloat(document.getElementById('productPrice').value);
    let quantity = parseInt(document.getElementById('productQuantity').value);
    let discount = parseFloat(document.getElementById('productDiscount').value);
    
    if (!id || !name || price <= 0 || quantity <= 0) {
        alert('Veuillez remplir tous les champs');
        return;
    }
    
    let existing = items.find(item => item.id == id);
    if (existing) {
        existing.quantity += quantity;
    } else {
        items.push({
            id: id,
            name: name,
            barcode: barcode,
            price: price,
            quantity: quantity,
            discount: discount
        });
    }
    
    updateItemsList();
    clearAddPanel();
}

function clearAddPanel() {
    document.getElementById('addProductPanel').style.display = 'none';
    document.getElementById('productId').value = '';
    document.getElementById('productName').value = '';
    document.getElementById('productBarcode').value = '';
    document.getElementById('productPrice').value = '';
    document.getElementById('productQuantity').value = 1;
    document.getElementById('productDiscount').value = 0;
    document.getElementById('barcodeInput').value = '';
    document.getElementById('barcodeInput').focus();
}

function updateItemsList() {
    let tbody = document.getElementById('itemsBody');
    let totalHT = 0;
    
    if (items.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">Aucun article</td></tr>';
        updateTotals();
        return;
    }
    
    tbody.innerHTML = '';
    items.forEach((item, index) => {
        let sousTotal = item.quantity * item.price * (1 - item.discount / 100);
        totalHT += sousTotal;
        
        tbody.innerHTML += `
            <tr>
                <td>${item.barcode}</div>
                <td>${item.name}</div>
                <td style="text-align: center;">${item.quantity}</div>
                <td style="text-align: right;">${formatPrice(item.price)} G</div>
                <td style="text-align: center;">${item.discount}%</div>
                <td style="text-align: right;">${formatPrice(sousTotal)} G</div>
                <td style="text-align: center;">
                    <button class="btn btn-sm btn-danger" onclick="removeItem(${index})">✖</button>
                </td>
            </div>
        `;
    });
    
    updateTotals(totalHT);
}

function removeItem(index) {
    items.splice(index, 1);
    updateItemsList();
}

function updateTotals(totalHT = null) {
    if (totalHT === null) {
        totalHT = items.reduce((sum, item) => sum + (item.quantity * item.price * (1 - item.discount / 100)), 0);
    }
    
    let totalTVA = totalHT * 0.18;
    let totalTTC = totalHT + totalTVA;
    let remiseGlobale = parseFloat(document.getElementById('remiseGlobale').value) || 0;
    let discountAmount = totalTTC * (remiseGlobale / 100);
    let netToPay = totalTTC - discountAmount;
    
    document.getElementById('totalHT').innerHTML = formatPrice(totalHT) + ' G';
    document.getElementById('totalTVA').innerHTML = formatPrice(totalTVA) + ' G';
    document.getElementById('totalTTC').innerHTML = formatPrice(totalTTC) + ' G';
    document.getElementById('globalDiscount').innerHTML = '-' + formatPrice(discountAmount) + ' G';
    document.getElementById('netToPay').innerHTML = formatPrice(netToPay) + ' G';
}

function formatPrice(price) {
    return Math.round(price).toLocaleString('fr-FR');
}

document.getElementById('remiseGlobale').addEventListener('input', function() {
    updateTotals();
});

function saveCotation() {
    if (items.length === 0) {
        alert('Ajoutez au moins un article');
        return;
    }
    
    let data = {
        id_client: document.getElementById('clientSelect').value,
        date_validite: document.getElementById('dateValidite').value,
        remise_globale: document.getElementById('remiseGlobale').value,
        notes: document.getElementById('notes').value,
        items: items
    };
    
    fetch('<?php echo \BASE_PATH; ?>/index.php?action=cotation_store', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Cotation créée avec succès');
            window.location.href = '<?php echo \BASE_PATH; ?>/index.php?action=cotation_show&id=' + data.cotation_id;
        } else {
            alert('Erreur: ' + data.message);
        }
    });
}
</script>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>