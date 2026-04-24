<?php
$title = 'Retours client';
include dirname(__DIR__) . '/layouts/header.php';

$db = App\Config\Database::getInstance();
$magasinModel = new App\Models\Magasin();
$currentMagasin = $magasinModel->getCurrentMagasin();

$whereMagasin = $currentMagasin ? " AND id_magasin = " . $currentMagasin['id_magasin'] : "";

// Récupérer les retours existants
$returns = $db->fetchAll(
    "SELECT r.*, 
            v.numero_facture,
            CONCAT(c.nom, ' ', c.prenom) as client_nom,
            CONCAT(u.nom, ' ', u.prenom) as caissier_nom
     FROM mouvements_stock r
     JOIN ventes v ON r.reference_id = v.id_vente
     LEFT JOIN clients c ON v.id_client = c.id_client
     LEFT JOIN users u ON r.id_user = u.id_user
     WHERE r.type_mouvement = 'retour_client'" . $whereMagasin . "
     ORDER BY r.date_mouvement DESC
     LIMIT 100"
);
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h2 class="fw-bold mb-1">
                        <i class="fas fa-undo-alt" style="color: #667eea;"></i> Retours client
                    </h2>
                    <p class="text-muted">Gérez les retours de produits par les clients</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="<?php echo \BASE_PATH; ?>/index.php?action=sales_history" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Retour
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Formulaire de retour -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="fas fa-exchange-alt"></i> Nouveau retour</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?php echo \BASE_PATH; ?>/index.php?action=sales_process_return">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-bold">Numéro de facture</label>
                                <input type="text" name="invoice_number" id="invoiceNumber" class="form-control" 
                                       placeholder="FACT-20241201-0001" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-bold">Code-barres produit</label>
                                <input type="text" name="barcode" id="barcodeInput" class="form-control" 
                                       placeholder="Scannez le code-barres" autofocus>
                            </div>
                            <div class="col-md-2 mb-3">
                                <label class="form-label fw-bold">Quantité</label>
                                <input type="number" name="quantity" id="quantity" class="form-control" value="1" min="1">
                            </div>
                            <div class="col-md-2 mb-3">
                                <label class="form-label fw-bold">&nbsp;</label>
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-undo-alt"></i> Effectuer le retour
                                </button>
                            </div>
                        </div>
                        
                        <div id="saleInfo" class="alert alert-info mt-2" style="display: none;">
                            <strong>Vente trouvée :</strong> <span id="saleDetails"></span>
                        </div>
                        <div id="productInfo" class="alert alert-success mt-2" style="display: none;">
                            <strong>Produit trouvé :</strong> <span id="productDetails"></span>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Liste des retours -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-list"></i> Historique des retours</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Facture</th>
                                    <th>Client</th>
                                    <th>Produit</th>
                                    <th>Quantité</th>
                                    <th>Raison</th>
                                    <th>Caissier</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($returns)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <i class="fas fa-undo-alt fa-3x mb-3"></i><br>
                                        Aucun retour enregistré
                                    </div>
                                 </div>
                                <?php else: ?>
                                <?php foreach ($returns as $return): ?>
                                <tr>
                                    <td class="text-nowrap"><?php echo date('d/m/Y H:i', strtotime($return['date_mouvement'])); ?></div>
                                    <td><strong><?php echo htmlspecialchars($return['numero_facture']); ?></strong></div>
                                    <td><?php echo htmlspecialchars($return['client_nom'] ?? 'Client non renseigné'); ?></div>
                                    <td><?php echo htmlspecialchars($return['nom_produit'] ?? '-'); ?></div>
                                    <td class="text-center"><?php echo $return['quantite']; ?></div>
                                    <td><?php echo htmlspecialchars($return['raison'] ?? '-'); ?></div>
                                    <td><?php echo htmlspecialchars($return['caissier_nom']); ?></div>
                                 </div>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                         </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let barcodeBuffer = '';
let lastTime = 0;
const barcodeInput = document.getElementById('barcodeInput');

barcodeInput.addEventListener('keypress', function(e) {
    const now = Date.now();
    if (now - lastTime > 100) barcodeBuffer = '';
    lastTime = now;
    
    if (e.key === 'Enter') {
        e.preventDefault();
        checkProduct(barcodeBuffer);
        barcodeBuffer = '';
        this.value = '';
    } else {
        barcodeBuffer += e.key;
        this.value = barcodeBuffer;
    }
});

function checkProduct(barcode) {
    fetch('<?php echo \BASE_PATH; ?>/index.php?action=product_get_by_barcode&barcode=' + encodeURIComponent(barcode))
        .then(response => response.json())
        .then(data => {
            if (data && data.id_produit) {
                document.getElementById('productInfo').style.display = 'block';
                document.getElementById('productDetails').innerHTML = 
                    `${data.nom_produit} (Stock: ${data.stock_actuel})`;
            } else {
                alert('Produit non trouvé');
            }
        });
}

document.getElementById('invoiceNumber').addEventListener('blur', function() {
    let invoice = this.value;
    if (invoice) {
        fetch('<?php echo \BASE_PATH; ?>/index.php?action=get_sale_by_invoice&invoice=' + encodeURIComponent(invoice))
            .then(response => response.json())
            .then(data => {
                if (data && data.id_vente) {
                    document.getElementById('saleInfo').style.display = 'block';
                    document.getElementById('saleDetails').innerHTML = 
                        `Facture ${data.numero_facture} du ${new Date(data.date_vente).toLocaleDateString()} - Client: ${data.client_nom || 'Non renseigné'}`;
                } else {
                    document.getElementById('saleInfo').style.display = 'none';
                    alert('Facture non trouvée');
                }
            });
    }
});
</script>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>