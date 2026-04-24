<?php
// app/views/layouts/header_unified.php
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title ?? 'Total Family Multi-Services'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        .header-left {
            display: flex;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
        }
        .logo-container {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .logo {
            height: 50px;
            width: auto;
            border-radius: 10px;
            background: rgba(255,255,255,0.1);
            padding: 5px;
        }
        .company-name {
            font-size: 22px;
            font-weight: bold;
            margin: 0;
        }
        .company-name small {
            font-size: 12px;
            opacity: 0.9;
            display: block;
        }
        .user-info { display: flex; align-items: center; gap: 15px; flex-wrap: wrap; }
        .btn-link {
            background: rgba(255,255,255,0.2);
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            color: white;
            transition: background 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .btn-link:hover {
            background: rgba(255,255,255,0.3);
            color: white;
        }
        .magasin-selector {
            background: rgba(255,255,255,0.2);
            padding: 5px 15px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
        .container-fluid { max-width: 1400px; margin: 0 auto; padding: 20px; }
        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .card-header {
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
            font-weight: bold;
        }
        .card-body { padding: 20px; }
        .footer { text-align: center; padding: 20px; color: #666; margin-top: 40px; border-top: 1px solid #e0e0e0; }
        @media (max-width: 768px) {
            .header { flex-direction: column; text-align: center; }
            .header-left, .user-info, .logo-container { justify-content: center; }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-left">
            <div class="logo-container">
                <img src="/auto-parts-management/public/assets/images/logo_total_family.png" alt="Logo" class="logo" onerror="this.style.display='none'">
                <div class="company-name">
                    Total Family Multi-Services
                    <small>Votre partenaire auto de confiance</small>
                </div>
            </div>
            <?php if (isset($userMagasins) && !empty($userMagasins)): ?>
            <div class="magasin-selector">
                <label>🏪 Magasin:</label>
                <select id="magasinSelect" onchange="switchMagasin()">
                    <?php foreach ($userMagasins as $magasin): ?>
                    <option value="<?php echo $magasin['id_magasin']; ?>" 
                        <?php echo ($currentMagasin && $currentMagasin['id_magasin'] == $magasin['id_magasin']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($magasin['nom_magasin']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
        </div>
        <div class="user-info">
            <span>👤 <?php echo htmlspecialchars($userName ?? 'Utilisateur'); ?></span>
            
            <!-- Dashboard -->
            <a href="<?php echo \BASE_PATH; ?>/index.php?action=dashboard" class="btn-link">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            
            <!-- Produits - Essayez les deux possibilités -->
            <a href="<?php echo \BASE_PATH; ?>/index.php?action=products" class="btn-link">
                <i class="fas fa-boxes"></i> Produits
            </a>
            
            <!-- Point de vente -->
            <a href="<?php echo \BASE_PATH; ?>/index.php?action=pointdevente" class="btn-link">
                <i class="fas fa-shopping-cart"></i> Point de vente
            </a>
            
            <!-- Stock -->
            <a href="<?php echo \BASE_PATH; ?>/index.php?action=stock" class="btn-link">
                <i class="fas fa-warehouse"></i> Stock
            </a>
            
            <!-- Inventaire -->
            <a href="<?php echo \BASE_PATH; ?>/index.php?action=inventory" class="btn-link">
                <i class="fas fa-clipboard-list"></i> Inventaire
            </a>
            
            <!-- Cotations -->
            <a href="<?php echo \BASE_PATH; ?>/index.php?action=cotations" class="btn-link">
                <i class="fas fa-file-alt"></i> Cotations
            </a>
            
            <!-- Clients -->
            <a href="<?php echo \BASE_PATH; ?>/index.php?action=clients" class="btn-link">
                <i class="fas fa-users"></i> Clients
            </a>
            
            <!-- Utilisateurs -->
            <a href="<?php echo \BASE_PATH; ?>/index.php?action=users" class="btn-link">
                <i class="fas fa-users-cog"></i> Utilisateurs
            </a>
            
            <!-- Paramètres -->
            <a href="<?php echo \BASE_PATH; ?>/index.php?action=settings" class="btn-link">
                <i class="fas fa-cog"></i> Paramètres
            </a>
            
            <!-- Déconnexion -->
            <a href="<?php echo \BASE_PATH; ?>/index.php?action=logout" class="btn-link">
                <i class="fas fa-sign-out-alt"></i> Déconnexion
            </a>
        </div>
    </div>

    <script>
    function switchMagasin() {
        var magasinId = document.getElementById('magasinSelect').value;
        var url = new URL(window.location.href);
        url.searchParams.set('magasin_id', magasinId);
        window.location.href = url.toString();
    }
    </script>