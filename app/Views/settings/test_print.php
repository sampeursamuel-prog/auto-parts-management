<?php
// Vue test d'impression
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test d'impression - Auto-Parts</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Courier New', monospace;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .print-container {
            background: white;
            border-radius: 20px;
            padding: 30px;
            max-width: 600px;
            width: 100%;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
        }
        .ticket {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            white-space: pre;
            overflow-x: auto;
        }
        .btn-print {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 10px;
            font-size: 16px;
            cursor: pointer;
            transition: transform 0.3s;
            margin: 10px 5px;
        }
        .btn-print:hover {
            transform: translateY(-2px);
        }
        .btn-back {
            background: #6c757d;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            display: inline-block;
            margin-top: 15px;
        }
        .alert {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        h3 {
            color: #333;
            margin-bottom: 20px;
            text-align: center;
        }
        .info {
            background: #e7f3ff;
            padding: 10px;
            border-radius: 8px;
            margin: 10px 0;
            font-size: 12px;
            color: #0066cc;
        }
    </style>
</head>
<body>
    <div class="print-container">
        <h3>🖨️ Test d'impression</h3>
        
        <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-warning">
            <strong>⚠️ Attention :</strong> <?php echo htmlspecialchars($errorMessage); ?><br>
            <small>L'impression sera effectuée via le navigateur.</small>
        </div>
        <?php endif; ?>
        
        <div class="info">
            <strong>ℹ️ Information :</strong><br>
            Type d'imprimante: <?php echo ucfirst($printerType ?: 'Non configuré'); ?><br>
            Adresse IP: <?php echo $printerIp ?: 'Non configurée'; ?><br>
            Port: <?php echo $printerPort ?: 'Non configuré'; ?>
        </div>
        
        <div class="ticket">
            <?php echo htmlspecialchars($testTicket); ?>
        </div>
        
        <div style="text-align: center;">
            <button class="btn-print" onclick="window.print()">
                <i class="fas fa-print"></i> 🖨️ Imprimer
            </button>
            <br>
            <a href="<?php echo \BASE_PATH; ?>/index.php?action=settings" class="btn-back">
                ← Retour aux paramètres
            </a>
        </div>
    </div>
    
    <script>
        setTimeout(function() {
            window.print();
        }, 500);
    </script>
</body>
</html>