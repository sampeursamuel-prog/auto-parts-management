<?php
$title = 'Facture - ' . ($vente['numero_facture'] ?? '');
include dirname(__DIR__) . '/layouts/header.php';

$company = [
    'name' => 'Auto-Parts Management',
    'address' => '123 Avenue Principale, Port-au-Prince',
    'phone' => '+509 1234 5678',
    'email' => 'contact@autoparts.com'
];
?>

<style>
    @media print {
        .no-print { display: none; }
        .invoice-container { margin: 0; padding: 0; }
        .print-btn { display: none; }
    }
    .invoice-container {
        max-width: 800px;
        margin: 0 auto;
        background: white;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    .invoice-header {
        text-align: center;
        margin-bottom: 30px;
        padding-bottom: 20px;
        border-bottom: 2px solid #667eea;
    }
    .invoice-header h1 {
        color: #667eea;
        margin-bottom: 5px;
    }
    .invoice-info {
        margin-bottom: 30px;
    }
    .invoice-info table {
        width: 100%;
    }
    .invoice-info td {
        padding: 5px;
    }
    .invoice-items table {
        width: 100%;
        border-collapse: collapse;
    }
    .invoice-items th, .invoice-items td {
        border: 1px solid #ddd;
        padding: 10px;
        text-align: left;
    }
    .invoice-items th {
        background: #f8f9fa;
    }
    .invoice-total {
        margin-top: 20px;
        text-align: right;
    }
    .invoice-total table {
        width: 300px;
        margin-left: auto;
    }
    .invoice-footer {
        margin-top: 30px;
        text-align: center;
        font-size: 12px;
        color: #666;
        border-top: 1px solid #ddd;
        padding-top: 20px;
    }
    .print-btn {
        text-align: center;
        margin-bottom: 20px;
    }
</style>

<div class="container-fluid">
    <div class="print-btn no-print">
        <button onclick="window.print()" class="btn btn-primary">
            <i class="fas fa-print"></i> Imprimer la facture
        </button>
        <a href="<?php echo \BASE_PATH; ?>/index.php?action=sales_history" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Retour
        </a>
    </div>
    
    <div class="invoice-container">
        <div class="invoice-header">
            <h1><?php echo $company['name']; ?></h1>
            <p><?php echo $company['address']; ?><br>
            Tél: <?php echo $company['phone']; ?> | Email: <?php echo $company['email']; ?></p>
        </div>
        
        <div class="invoice-info">
            <table>
                <tr>
                    <td width="50%">
                        <strong>Facture N°:</strong> <?php echo htmlspecialchars($vente['numero_facture']); ?><br>
                        <strong>Date:</strong> <?php echo date('d/m/Y H:i', strtotime($vente['date_vente'])); ?><br>
                        <strong>Caissier:</strong> <?php echo htmlspecialchars($vente['caissier']); ?>
                    </td>
                    <td width="50%" style="text-align: right;">
                        <?php if ($vente['client_nom']): ?>
                            <strong>Client:</strong> <?php echo htmlspecialchars($vente['client_nom']); ?><br>
                            <strong>Tél:</strong> <?php echo htmlspecialchars($vente['client_tel']); ?><br>
                            <strong>Email:</strong> <?php echo htmlspecialchars($vente['client_email']); ?>
                        <?php else: ?>
                            <strong>Client:</strong> Client non renseigné
                        <?php endif; ?>
                    </td>
                 </div>
            </div>
        </div>
        
        <div class="invoice-items">
            <table>
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Produit</th>
                        <th>Qté</th>
                        <th>Prix unitaire</th>
                        <th>Remise</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($details as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['code_barre_scanne'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($item['nom_produit']); ?></td>
                        <td style="text-align: center;"><?php echo $item['quantite']; ?></td>
                        <td style="text-align: right;"><?php echo number_format($item['prix_unitaire_ht'], 0, ',', ' '); ?> G</td>
                        <td style="text-align: center;"><?php echo $item['remise_ligne'] > 0 ? $item['remise_ligne'] . '%' : '-'; ?></td>
                        <td style="text-align: right;"><?php echo number_format($item['sous_total_ht'], 0, ',', ' '); ?> G</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="invoice-total">
            <table>
                <tr>
                    <td width="70%"><strong>Sous-total HT:</strong></td>
                    <td width="30%" style="text-align: right;"><?php echo number_format($vente['montant_total_ht'], 0, ',', ' '); ?> G</td>
                 </tr>
                 <tr>
                    <td><strong>TVA (18%):</strong></td>
                    <td style="text-align: right;"><?php echo number_format($vente['montant_tva'], 0, ',', ' '); ?> G</td>
                 </tr>
                 <tr>
                    <td><strong>Total TTC:</strong></td>
                    <td style="text-align: right;"><strong><?php echo number_format($vente['montant_total_ttc'], 0, ',', ' '); ?> G</strong></td>
                 </tr>
                 <?php if ($vente['montant_remise'] > 0): ?>
                 <tr>
                    <td><strong>Remise:</strong></td>
                    <td style="text-align: right;">- <?php echo number_format($vente['montant_remise'], 0, ',', ' '); ?> G</td>
                 </tr>
                 <tr style="border-top: 2px solid #ddd;">
                    <td><strong>Net à payer:</strong></td>
                    <td style="text-align: right;"><strong><?php echo number_format($vente['montant_final'], 0, ',', ' '); ?> G</strong></td>
                 </tr>
                 <?php endif; ?>
                 <tr>
                    <td><strong>Montant reçu:</strong></td>
                    <td style="text-align: right;"><?php echo number_format($vente['montant_recu'], 0, ',', ' '); ?> G</td>
                 </tr>
                 <tr>
                    <td><strong>Monnaie rendue:</strong></td>
                    <td style="text-align: right;"><?php echo number_format($vente['monnaie_rendue'], 0, ',', ' '); ?> G</td>
                 </tr>
             </table>
        </div>
        
        <div class="invoice-footer">
            <p>Merci de votre visite !</p>
            <p>Auto-Parts Management System - Document généré le <?php echo date('d/m/Y H:i:s'); ?></p>
        </div>
    </div>
</div>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>