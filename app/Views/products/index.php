<?php
// Vue des produits
?>
<style>
    .stock-low { color: #f59e0b; font-weight: bold; }
    .stock-out { color: #ef4444; font-weight: bold; }
    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        justify-content: center;
        align-items: center;
        z-index: 1000;
    }
    .modal.active {
        display: flex;
    }
    .modal-content {
        background: white;
        padding: 30px;
        border-radius: 10px;
        width: 90%;
        max-width: 550px;
        max-height: 90vh;
        overflow-y: auto;
        position: relative;
    }
    .close {
        position: absolute;
        top: 15px;
        right: 20px;
        font-size: 24px;
        cursor: pointer;
    }
</style>

<div class="container-fluid">
    <?php $flash = \App\Helpers\Session::getFlash(); ?>
    <?php if ($flash): ?>
        <div class="alert alert-<?php echo $flash['type']; ?>">
            <?php echo $flash['message']; ?>
        </div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">📦 Liste des produits</h5>
                <div>
                    <button class="btn btn-primary btn-sm" onclick="openAddModal()">
                        <i class="fas fa-plus"></i> Nouveau produit
                    </button>
                    <button class="btn btn-success btn-sm" onclick="openScanner()">
                        <i class="fas fa-qrcode"></i> Scanner
                    </button>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-6">
                    <input type="text" id="searchInput" class="form-control" placeholder="🔍 Rechercher par nom, code-barres ou catégorie..." onkeyup="searchProducts()">
                </div>
            </div>
            
            <div id="scannerPanel" class="row mb-3" style="display: none;">
                <div class="col-md-8">
                    <input type="text" id="barcodeInput" class="form-control" placeholder="Scannez un code-barres..." autofocus>
                </div>
                <div class="col-md-2">
                    <button onclick="searchByBarcode()" class="btn btn-primary w-100">Rechercher</button>
                </div>
                <div class="col-md-2">
                    <button onclick="closeScanner()" class="btn btn-danger w-100">Fermer</button>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Code-barres</th>
                            <th>Produit</th>
                            <th>Catégorie</th>
                            <th>Prix achat HT</th>
                            <th>Prix vente HT</th>
                            <th>Prix vente TTC</th>
                            <th>Stock</th>
                            <th>Emplacement</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($products)): ?>
                        <tr>
                            <td colspan="9" class="text-center" style="padding: 40px;">
                                📦 Aucun produit pour le moment<br>
                                <button class="btn btn-primary btn-sm mt-2" onclick="openAddModal()">Ajouter votre premier produit</button>
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($products as $product): ?>
                        <tr class="product-row">
                            <td><code><?php echo htmlspecialchars($product['code_barre']); ?></code></td>
                            <td>
                                <strong><?php echo htmlspecialchars($product['nom_produit']); ?></strong><br>
                                <small><?php echo htmlspecialchars(substr($product['description'] ?? '', 0, 50)); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($product['nom_categorie'] ?? '-'); ?></td>
                            <td><?php echo number_format($product['prix_achat_ht'], 0, ',', ' '); ?> G</td>
                            <td><?php echo number_format($product['prix_vente_ht'], 0, ',', ' '); ?> G</td>
                            <td><strong><?php echo number_format($product['prix_vente_ttc'], 0, ',', ' '); ?> G</strong></td>
                            <td class="<?php echo $product['stock_actuel'] <= $product['stock_minimum'] ? ($product['stock_actuel'] == 0 ? 'stock-out' : 'stock-low') : ''; ?>">
                                <?php echo $product['stock_actuel']; ?> / <?php echo $product['stock_minimum']; ?>
                                <?php if ($product['stock_actuel'] <= $product['stock_minimum']): ?>
                                    <span class="badge bg-warning text-dark">
                                        <?php echo $product['stock_actuel'] == 0 ? 'RUPTURE' : 'STOCK BAS'; ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($product['emplacement'] ?? '-'); ?></td>
                            <td>
                                <button onclick="editProduct(<?php echo $product['id_produit']; ?>)" class="btn btn-primary btn-sm">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button onclick="deleteProduct(<?php echo $product['id_produit']; ?>)" class="btn btn-danger btn-sm">
                                    <i class="fas fa-trash"></i>
                                </button>
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

<!-- Modal Ajouter/Modifier produit -->
<div id="productModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h2 id="modalTitle">Ajouter un produit</h2>
        <form id="productForm" method="POST" action="<?php echo \BASE_PATH; ?>/index.php?action=product_add">
            <input type="hidden" name="id_produit" id="productId">
            <div class="form-group mb-2">
                <label>Code-barres</label>
                <input type="text" name="code_barre" id="codeBarre" class="form-control" placeholder="Laisser vide pour génération automatique">
            </div>
            <div class="form-group mb-2">
                <label>Nom du produit *</label>
                <input type="text" name="nom_produit" id="nomProduit" class="form-control" required>
            </div>
            <div class="form-group mb-2">
                <label>Description</label>
                <textarea name="description" id="description" class="form-control" rows="3"></textarea>
            </div>
            <div class="form-group mb-2">
                <label>Catégorie</label>
                <select name="id_categorie" id="idCategorie" class="form-control">
                    <option value="">-- Sélectionner --</option>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['id_categorie']; ?>"><?php echo htmlspecialchars($cat['nom_categorie']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group mb-2">
                        <label>Prix d'achat HT (G) *</label>
                        <input type="number" name="prix_achat_ht" id="prixAchatHt" class="form-control" step="1" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group mb-2">
                        <label>Prix de vente HT (G) *</label>
                        <input type="number" name="prix_vente_ht" id="prixVenteHt" class="form-control" step="1" required>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group mb-2">
                        <label>TVA (%)</label>
                        <input type="number" name="tva" id="tva" class="form-control" step="0.01" value="18">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group mb-2">
                        <label>Unité de mesure</label>
                        <input type="text" name="unite_mesure" id="uniteMesure" class="form-control" value="pièce">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group mb-2">
                        <label>Stock initial</label>
                        <input type="number" name="stock_actuel" id="stockActuel" class="form-control" value="0">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group mb-2">
                        <label>Stock minimum</label>
                        <input type="number" name="stock_minimum" id="stockMinimum" class="form-control" value="5">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group mb-2">
                        <label>Stock sécurité</label>
                        <input type="number" name="stock_securite" id="stockSecurite" class="form-control" value="10">
                    </div>
                </div>
            </div>
            <div class="form-group mb-2">
                <label>Emplacement</label>
                <input type="text" name="emplacement" id="emplacement" class="form-control" placeholder="Ex: RACK-01-A">
            </div>
            <button type="submit" class="btn btn-primary w-100">💾 Enregistrer</button>
        </form>
    </div>
</div>

<script>
function searchProducts() {
    let input = document.getElementById('searchInput').value.toLowerCase();
    let rows = document.querySelectorAll('.product-row');
    rows.forEach(row => {
        let text = row.textContent.toLowerCase();
        row.style.display = text.includes(input) ? '' : 'none';
    });
}

function searchByBarcode() {
    let barcode = document.getElementById('barcodeInput').value.trim();
    if (barcode) {
        window.location.href = '<?php echo \BASE_PATH; ?>/index.php?action=product_scan&barcode=' + encodeURIComponent(barcode);
    } else {
        alert('Veuillez saisir un code-barres');
    }
}

function openScanner() {
    document.getElementById('scannerPanel').style.display = 'flex';
    document.getElementById('barcodeInput').focus();
}

function closeScanner() {
    document.getElementById('scannerPanel').style.display = 'none';
    document.getElementById('barcodeInput').value = '';
}

function openAddModal() {
    document.getElementById('modalTitle').innerText = 'Ajouter un produit';
    document.getElementById('productForm').reset();
    document.getElementById('productId').value = '';
    document.getElementById('productForm').action = '<?php echo \BASE_PATH; ?>/index.php?action=product_add';
    document.getElementById('productModal').classList.add('active');
}

function closeModal() {
    document.getElementById('productModal').classList.remove('active');
}

function editProduct(id) {
    fetch('<?php echo \BASE_PATH; ?>/index.php?action=product_get&id=' + id)
        .then(response => response.json())
        .then(data => {
            document.getElementById('modalTitle').innerText = 'Modifier le produit';
            document.getElementById('productId').value = data.id_produit;
            document.getElementById('codeBarre').value = data.code_barre;
            document.getElementById('nomProduit').value = data.nom_produit;
            document.getElementById('description').value = data.description || '';
            document.getElementById('idCategorie').value = data.id_categorie || '';
            document.getElementById('prixAchatHt').value = data.prix_achat_ht;
            document.getElementById('prixVenteHt').value = data.prix_vente_ht;
            document.getElementById('tva').value = data.tva || 18;
            document.getElementById('stockActuel').value = data.stock_actuel;
            document.getElementById('stockMinimum').value = data.stock_minimum;
            document.getElementById('stockSecurite').value = data.stock_securite || 10;
            document.getElementById('uniteMesure').value = data.unite_mesure || 'pièce';
            document.getElementById('emplacement').value = data.emplacement || '';
            document.getElementById('productForm').action = '<?php echo \BASE_PATH; ?>/index.php?action=product_edit';
            document.getElementById('productModal').classList.add('active');
        });
}

function deleteProduct(id) {
    if (confirm('Êtes-vous sûr de vouloir supprimer ce produit ?')) {
        window.location.href = '<?php echo \BASE_PATH; ?>/index.php?action=product_delete&id=' + id;
    }
}

document.addEventListener('keydown', function(e) {
    if (e.ctrlKey && e.key === 's') {
        e.preventDefault();
        openScanner();
    }
});

window.onclick = function(event) {
    let modal = document.getElementById('productModal');
    if (event.target == modal) {
        closeModal();
    }
}
</script>