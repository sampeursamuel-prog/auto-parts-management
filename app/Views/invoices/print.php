<?php
// Version imprimable de la facture
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Facture <?php echo htmlspecialchars($invoice['numero_facture']); ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Courier New', monospace;
            font-size: 12px;
            padding: 20px;
        }
        .invoice {
            max-width: 300px;
            margin: 0 auto;
        }
        .header {
            text-align: center;
            border-bottom: 1px dashed #000;
            padding-bottom: 10px;
            margin-bottom: 10px;
        }
        .header h2 {
            font-size: 14px;
        }
        .info {
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px dotted #ccc;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        th, td {
            text-align: left;
            padding: 5px 0;
        }
        th {
            border-bottom: 1px dashed #000;
        }
        .text-right {
            text-align: right;
        }
        .total {
            border-top: 1px dashed #000;
            margin-top: 10px;
            padding-top: 10px;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px dashed #000;
        }
        @media print {
            body { padding: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="invoice">
        <div class="header">
            <h2>TOTAL FAMILY MULTI-SERVICES</h2>
            <p>Rue Principale, Port-au-Prince<br>
            Tel: +509 1234 5678</p>
        </div>

        <div class="info">
            <strong>FACTURE N°:</strong> <?php echo htmlspecialchars($invoice['numero_facture']); ?><br>
            <strong>Date:</strong> <?php echo date('d/m/Y H:i', strtotime($invoice['date_vente'])); ?><br>
            <strong>Caissier:</strong> <?php echo htmlspecialchars($invoice['caissier']); ?><br>
            <?php if ($invoice['client_nom']): ?>
            <strong>Client:</strong> <?php echo htmlspecialchars($invoice['client_nom'] . ' ' . ($invoice['client_prenom'] ?? '')); ?><br>
            <?php endif; ?>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Article</th>
                    <th>Qté</th>
                    <th class="text-right">Prix</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                <tr>
                    <td><?php echo htmlspecialchars(substr($item['nom_produit'], 0, 20)); ?></td>
                    <td class="text-right"><?php echo $item['quantite']; ?></td>
                    <td class="text-right"><?php echo number_format($item['prix_unitaire_ttc'] ?? ($item['prix_unitaire_ht'] * 1.18), 0, ',', ' '); ?></td>
                    <td class="text-right"><?php echo number_format(($item['prix_unitaire_ttc'] ?? ($item['prix_unitaire_ht'] * 1.18)) * $item['quantite'], 0, ',', ' '); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="total">
                    <td colspan="3" class="text-right"><strong>TOTAL TTC:</strong></td>
                    <td class="text-right"><strong><?php echo number_format($invoice['montant_total_ttc'], 0, ',', ' '); ?> G</strong></td>
                </tr>
                <?php if ($invoice['montant_recu'] > 0): ?>
                <tr>
                    <td colspan="3" class="text-right">Reçu:</td>
                    <td class="text-right"><?php echo number_format($invoice['montant_recu'], 0, ',', ' '); ?> G</td>
                </tr>
                <tr>
                    <td colspan="3" class="text-right">Monnaie:</td>
                    <td class="text-right"><?php echo number_format($invoice['monnaie_rendue'], 0, ',', ' '); ?> G</td>
                </tr>
                <?php endif; ?>
            </tfoot>
        </table>

        <div class="footer">
            <p>✅ MERCI DE VOTRE VISITE !</p>
            <p style="font-size: 10px;">Cet article ne peut être échangé sans ticket</p>
        </div>
    </div>

    <div class="no-print" style="text-align: center; margin-top: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px;">🖨️ Imprimer</button>
        <button onclick="window.close()" style="padding: 10px 20px;">❌ Fermer</button>
    </div>

    <script>
        window.onload = function() {
            window.print();
        }
    </script>
</body>
</html>