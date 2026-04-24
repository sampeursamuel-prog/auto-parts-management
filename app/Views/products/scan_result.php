<?php
$title = 'Résultat du scan';
include dirname(__DIR__) . '/layouts/header.php';
?>

<div style="text-align: center; padding: 40px;">
    <?php if ($found): ?>
        <div style="color: #10b981; font-size: 48px;">✅</div>
        <h1>Produit trouvé !</h1>
        <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; margin: 20px auto; max-width: 500px; text-align: left;">
            <p><strong>📦 Produit :</strong> <?php echo htmlspecialchars($product['nom_produit']); ?></p>
            <p><strong>🔢 Code-barres :</strong> <code><?php echo htmlspecialchars($product['code_barre']); ?></code></p>
            <p><strong>💰 Prix de vente :</strong> <?php echo number_format($product['prix_vente'], 0, ',', ' '); ?> FCFA</p>
            <p><strong>📊 Stock actuel :</strong> <?php echo $product['stock_actuel']; ?></p>
            <?php if ($product['emplacement']): ?>
            <p><strong>📍 Emplacement :</strong> <?php echo htmlspecialchars($product['emplacement']); ?></p>
            <?php endif; ?>
        </div>
        <p>Redirection dans 3 secondes...</p>
        <a href="<?php echo BASE_PATH; ?>/products" class="btn">← Retour à la liste</a>
        <script>setTimeout(function(){ window.location.href = '<?php echo BASE_PATH; ?>/products'; }, 3000);</script>
    <?php else: ?>
        <div style="color: #ef4444; font-size: 48px;">❌</div>
        <h1>Produit non trouvé</h1>
        <p>Le code-barres <strong><?php echo htmlspecialchars($barcode); ?></strong> n'existe pas.</p>
        <div style="margin-top: 30px;">
            <a href="<?php echo BASE_PATH; ?>/products" class="btn">← Retour à la liste</a>
        </div>
    <?php endif; ?>
</div>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>