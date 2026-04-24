<?php
use App\Helpers\Auth;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title ?? 'Auto-Parts Management'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            overflow-x: hidden;
        }
        
        /* Header Styles - Même couleur que le sidebar */
        .header {
            background: linear-gradient(135deg, #2c3e50 0%, #1a252f 100%);
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .header-left {
            display: flex;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
        }
        
        /* Menu Toggle Button */
        .menu-toggle {
            background: rgba(255,255,255,0.1);
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            padding: 8px 12px;
            border-radius: 8px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .menu-toggle:hover {
            background: rgba(255,255,255,0.2);
            transform: scale(1.05);
        }
        
        /* Company Name - Sans logo */
        .company-name {
            font-size: 20px;
            font-weight: bold;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .company-name i {
            font-size: 28px;
            color: #667eea;
        }
        
        .company-name small {
            font-size: 11px;
            opacity: 0.8;
            display: block;
            font-weight: normal;
        }
        
        .magasin-selector {
            background: rgba(255,255,255,0.1);
            padding: 5px 15px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
        
        .magasin-selector label {
            font-size: 14px;
            opacity: 0.9;
            margin: 0;
        }
        
        #magasinSelect {
            padding: 6px 12px;
            border-radius: 5px;
            border: none;
            background: #34495e;
            color: white;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
        }
        
        #magasinSelect option {
            background: #2c3e50;
            color: white;
        }
        
        #magasinSelect:focus {
            outline: none;
            box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.5);
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .user-name {
            display: flex;
            align-items: center;
            gap: 8px;
            background: rgba(255,255,255,0.1);
            padding: 8px 15px;
            border-radius: 8px;
            transition: background 0.3s;
        }
        
        .user-name:hover {
            background: rgba(255,255,255,0.2);
        }
        
        .notification-btn {
            background: rgba(255,255,255,0.1);
            border: none;
            color: white;
            padding: 8px 12px;
            border-radius: 8px;
            cursor: pointer;
            position: relative;
            transition: all 0.3s;
        }
        
        .notification-btn:hover {
            background: rgba(255,255,255,0.2);
            transform: scale(1.05);
        }
        
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #ef4444;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 10px;
            font-weight: bold;
        }
        
        .pos-btn-header {
            background: #667eea;
            color: white;
            padding: 8px 20px;
            border-radius: 8px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .pos-btn-header:hover {
            background: #5a67d8;
            color: white;
            transform: translateY(-2px);
        }
        
        @media (max-width: 768px) {
            .header {
                padding: 12px 20px;
            }
            .company-name {
                font-size: 16px;
            }
            .company-name i {
                font-size: 20px;
            }
            .company-name small {
                font-size: 9px;
            }
            .magasin-selector {
                display: none;
            }
            .user-name span {
                display: none;
            }
            .pos-btn-header span {
                display: none;
            }
            .pos-btn-header {
                padding: 8px 12px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-left">
            <button class="menu-toggle" id="menuToggle">
                <i class="fas fa-bars"></i>
            </button>
            
            <div class="company-name">
                <i class="fas fa-car"></i>
                <div>
                    Total Family Multi-Services
                    <small>Votre partenaire auto de confiance</small>
                </div>
            </div>
            
            <?php if (isset($_SESSION['user_id']) && isset($userMagasins) && !empty($userMagasins)): ?>
            <div class="magasin-selector">
                <label><i class="fas fa-store"></i> Magasin:</label>
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
            <a href="<?php echo \BASE_PATH; ?>/index.php?action=pos" class="pos-btn-header">
                <i class="fas fa-shopping-cart"></i>
                <span>Vente rapide</span>
            </a>
            
            <div class="user-name">
                <i class="fas fa-user-circle"></i>
                <span><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Utilisateur'); ?></span>
            </div>
            
            <button class="notification-btn" id="notificationBtn">
                <i class="fas fa-bell"></i>
                <span class="notification-badge" id="notificationBadge">0</span>
            </button>
        </div>
    </div>