<?php
// Vue comptage d'inventaire
?>
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">
                        <i class="fas fa-calculator"></i> 
                        Comptage - <?php echo htmlspecialchars($inventory['numero_inventaire']); ?>
                    </h4>
                </div>
                <div class="card-body">
                    <!-- Barre de progression -->
                    <div class="mb-4">
                        <div class="d-flex justify-content-between mb-1">
                            <span>Progression du comptage</span>
                            <span><?php echo $inventory['stats']['counted_products']; ?> / <?php echo $inventory['stats']['total_products']; ?> produits</span>
                        </div>
                        <div class="progress" style="height: 25px;">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                 style="width: <?php echo $inventory['stats']['progress']; ?>%">
                                <?php echo $inventory['stats']['progress']; ?>%
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tableau des produits -->
                    <div class="table-responsive">
                        <table class="table table-bordered" id="countTable">
                            <thead>
                                <tr>
                                    <th>Produit</th>
                                    <th>Code barre</th>
                                    <th>Emplacement</th>
                                    <th>Stock théorique</th>
                                    <th>Quantité comptée</th>
                                    <th>Statut</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($inventory['details'] as $detail): ?>
                                    <tr data-detail-id="<?php echo $detail['id_detail_inventaire']; ?>"
                                        data-product-name="<?php echo htmlspecialchars($detail['nom_produit']); ?>"
                                        class="<?php echo $detail['quantite_comptee'] > 0 ? 'table-success' : ''; ?>">
                                        <td><?php echo htmlspecialchars($detail['nom_produit']); ?></td>
                                        <td><?php echo htmlspecialchars($detail['code_barre']); ?></td>
                                        <td><?php echo htmlspecialchars($detail['emplacement']); ?></td>
                                        <td class="text-end"><?php echo number_format($detail['quantite_theorique']); ?></td>
                                        <td class="text-end quantite-display">
                                            <?php echo $detail['quantite_comptee'] > 0 ? number_format($detail['quantite_comptee']) : '-'; ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($detail['quantite_comptee'] > 0): ?>
                                                <span class="badge bg-success">Compté</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning">À compter</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <button class="btn btn-sm btn-primary btn-count" 
                                                    data-detail-id="<?php echo $detail['id_detail_inventaire']; ?>"
                                                    data-product-name="<?php echo htmlspecialchars($detail['nom_produit']); ?>"
                                                    data-current-qty="<?php echo $detail['quantite_comptee']; ?>">
                                                <i class="fas fa-edit"></i> Compter
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-3 d-flex justify-content-between">
                        <a href="<?php echo \BASE_PATH; ?>/index.php?action=inventory" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Retour
                        </a>
                        <a href="<?php echo \BASE_PATH; ?>/index.php?action=inventory_validate&id=<?php echo $inventory['id']; ?>" 
                           class="btn btn-success"
                           onclick="return confirm('Valider cet inventaire ? Les stocks seront mis à jour.')">
                            <i class="fas fa-check"></i> Valider l'inventaire
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de comptage -->
<div class="modal fade" id="countModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Comptage produit</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="detailId">
                <div class="mb-3">
                    <label class="form-label">Produit</label>
                    <input type="text" id="productName" class="form-control" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label">Quantité comptée</label>
                    <input type="number" id="quantity" class="form-control" min="0" step="1" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Notes (optionnel)</label>
                    <textarea id="notes" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" id="saveCountBtn">Enregistrer</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = new bootstrap.Modal(document.getElementById('countModal'));
    let currentRow = null;
    
    // Ouvrir le modal de comptage
    document.querySelectorAll('.btn-count').forEach(btn => {
        btn.addEventListener('click', function() {
            currentRow = this.closest('tr');
            document.getElementById('detailId').value = this.dataset.detailId;
            document.getElementById('productName').value = this.dataset.productName;
            document.getElementById('quantity').value = this.dataset.currentQty;
            document.getElementById('notes').value = '';
            modal.show();
        });
    });
    
    // Sauvegarder le comptage
    document.getElementById('saveCountBtn').addEventListener('click', async function() {
        const detailId = document.getElementById('detailId').value;
        const quantity = parseInt(document.getElementById('quantity').value);
        const notes = document.getElementById('notes').value;
        
        if (isNaN(quantity) || quantity < 0) {
            alert('Veuillez entrer une quantité valide');
            return;
        }
        
        try {
            const response = await fetch('<?php echo \BASE_PATH; ?>/index.php?action=inventory_save_count', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    detail_id: detailId,
                    quantity: quantity,
                    notes: notes
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                // Mettre à jour l'affichage
                if (currentRow) {
                    const qtyDisplay = currentRow.querySelector('.quantite-display');
                    qtyDisplay.textContent = quantity.toLocaleString();
                    
                    const statusBadge = currentRow.querySelector('td:nth-child(6) .badge');
                    if (quantity > 0) {
                        statusBadge.className = 'badge bg-success';
                        statusBadge.textContent = 'Compté';
                        currentRow.classList.add('table-success');
                    }
                    
                    // Mettre à jour le data attribute
                    const btn = currentRow.querySelector('.btn-count');
                    if (btn) {
                        btn.dataset.currentQty = quantity;
                    }
                }
                
                modal.hide();
                
                // Afficher une notification
                const notification = document.createElement('div');
                notification.className = 'alert alert-success alert-dismissible fade show position-fixed top-0 end-0 m-3';
                notification.style.zIndex = '9999';
                notification.innerHTML = `
                    Comptage enregistré avec succès !
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;
                document.body.appendChild(notification);
                setTimeout(() => notification.remove(), 3000);
            } else {
                alert('Erreur: ' + (result.message || 'Impossible d\'enregistrer le comptage'));
            }
        } catch (error) {
            console.error('Erreur:', error);
            alert('Erreur lors de l\'enregistrement');
        }
    });
});
</script>