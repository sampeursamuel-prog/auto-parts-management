<?php
// Détails d'une facture
?>
<style>
    .invoice-box {
        max-width: 800px;
        margin: 0 auto;
        padding: 30px;
        border: 1px solid #eee;
        box-shadow: 0 0 10px rgba(0,0,0,0.1);
        background: white;
    }
    .invoice-header {
        border-bottom: 2px solid #667eea;
        padding-bottom: 20px;
        margin-bottom: 20px;
    }
    .invoice-footer {
        border-top: 2px solid #667eea;
        padding-top: 20px;
        margin-top: 20px;
    }
    .status-paid {
        background: #10b981;
        color: white;
        padding: 5px 15px;
        border-radius: 20px;
        display: inline-block;
    }
    .status-cancelled {
        background: #ef4444;
        color: white;
        padding: 5px 15px;
        border-radius: 20px;
        display: inline-block;
    }
</style>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-file-invoice"></i> Détails de la facture</h2>
        <div>
            <a href="<?php echo \BASE_PATH; ?>/index.php?action=invoice_print&id=<?php echo $invoice['id_vente']; ?>" 
               class="btn btn-secondary" target="_blank">
                <i class="fas fa-print"></i> Imprimer
            </a>
            <a href="<?php echo \BASE_PATH; ?>/index.php?action=invoices" class="btn btn-primary">
                <i class="fas fa-arrow-left"></i> Retour
            </a>
        </div>
    </div>

    <div class="invoice-box">
        <div class="invoice-header">
            <div class="row">
                <div class="col-6">
                    <h3>Total Family Multi-Services</h3>
                    <p>
                        📍 Rue Principale, Port-au-Prince<br>
                        📞 +509 1234 5678<br>
                        📧 contact@totalfamily.ht
                    </p>
                </div>
                <div class="col-6 text-end">
                    <h4>FACTURE</h4>
                    <p>
                        <strong>N°:</strong> <?php echo htmlspecialchars($invoice['numero_facture']); ?><br>
                        <strong>Date:</strong> <?php echo date('d/m/Y H:i', strtotime($invoice['date_vente'])); ?><br>
                        <strong>Statut:</strong> 
                        <?php if ($invoice['statut'] == 'complete'): ?>
                            <span class="status-paid">Payée</span>
                        <?php else: ?>
                            <span class="status-cancelled">Annulée</span>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-6">
                <strong>👤 Client:</strong><br>
                <?php if ($invoice['client_nom']): ?>
                    <?php echo htmlspecialchars($invoice['client_nom'] . ' ' . ($invoice['client_prenom'] ?? '')); ?><br>
                    <?php if ($invoice['client_telephone']): ?>
                        📞 <?php echo htmlspecialchars($invoice['client_telephone']); ?><br>
                    <?php endif; ?>
                    <?php if ($invoice['client_email']): ?>
                        ✉️ <?php echo htmlspecialchars($invoice['client_email']); ?><br>
                    <?php endif; ?>
                    <?php if ($invoice['client_adresse']): ?>
                        📍 <?php echo htmlspecialchars($invoice['client_adresse']); ?>
                    <?php endif; ?>
                <?php else: ?>
                    Client comptoir
                <?php endif; ?>
            </div>
            <div class="col-6 text-end">
                <strong>👤 Caissier:</strong><br>
                <?php echo htmlspecialchars($invoice['caissier']); ?>
            </div>
        </div>

        <table class="table table-bordered">
            <thead class="table-light">
                <tr>
                    <th>Produit</th>
                    <th>Code-barres</th>
                    <th>Quantité</th>
                    <th>Prix unitaire</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['nom_produit']); ?></td>
                    <td><code><?php echo htmlspecialchars($item['code_barre']); ?></code></td>
                    <td class="text-center"><?php echo $item['quantite']; ?></td>
                    <td class="text-end"><?php echo number_format($item['prix_unitaire_ttc'] ?? ($item['prix_unitaire_ht'] * 1.18), 0, ',', ' '); ?> G</td>
                    <td class="text-end"><?php echo number_format(($item['prix_unitaire_ttc'] ?? ($item['prix_unitaire_ht'] * 1.18)) * $item['quantite'], 0, ',', ' '); ?> G</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot class="table-light">
                <tr>
                    <th colspan="4" class="text-end">Sous-total HT:</th>
                    <th class="text-end"><?php echo number_format($invoice['montant_total_ht'], 0, ',', ' '); ?> G</th>
                </tr>
                <tr>
                    <th colspan="4" class="text-end">TVA (18%):</th>
                    <th class="text-end"><?php echo number_format($invoice['montant_tva'], 0, ',', ' '); ?> G</th>
                </tr>
                <tr>
                    <th colspan="4" class="text-end">TOTAL TTC:</th>
                    <th class="text-end fw-bold text-success"><?php echo number_format($invoice['montant_total_ttc'], 0, ',', ' '); ?> G</th>
                </tr>
                <?php if ($invoice['montant_recu'] > 0): ?>
                <tr>
                    <th colspan="4" class="text-end">Montant reçu:</th>
                    <th class="text-end"><?php echo number_format($invoice['montant_recu'], 0, ',', ' '); ?> G</th>
                </tr>
                <tr>
                    <th colspan="4" class="text-end">Monnaie rendue:</th>
                    <th class="text-end"><?php echo number_format($invoice['monnaie_rendue'], 0, ',', ' '); ?> G</th>
                </tr>
                <?php endif; ?>
            </tfoot>
        </table>

        <div class="invoice-footer text-center">
            <p>✅ Merci de votre visite !</p>
            <small>Cet article ne peut être échangé sans ticket</small>
        </div>
    </div>
</div>