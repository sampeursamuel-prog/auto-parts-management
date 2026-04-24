<?php
// Définir une constante pour indiquer que le layout est chargé
define('LAYOUT_LOADED', true);

// Récupérer les données du contrôleur
$title = $title ?? 'Auto-Parts Management';
$content = $content ?? '';
$userMagasins = $userMagasins ?? [];
$currentMagasin = $currentMagasin ?? null;

// Inclure le header
include __DIR__ . '/header.php';

// Inclure le sidebar
include __DIR__ . '/sidebar.php';
?>

<style>
    /* Main content styles */
    .main-content {
        margin-left: 280px;
        margin-top: 80px;
        min-height: calc(100vh - 160px);
        transition: margin-left 0.3s ease;
        padding: 20px;
    }
    
    /* Pour mobile, pas de marge */
    @media (max-width: 768px) {
        .main-content {
            margin-left: 0;
        }
    }
    
    .container, .container-fluid {
        max-width: 1400px;
        margin: 0 auto;
        padding: 0;
    }
    
    /* Card styles */
    .card {
        background: white;
        border-radius: 10px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        margin-bottom: 20px;
        border: none;
    }
    
    .card-header {
        padding: 15px 20px;
        border-bottom: 1px solid #eee;
        font-weight: bold;
        background: white;
        border-radius: 10px 10px 0 0;
    }
    
    .card-body {
        padding: 20px;
    }
    
    /* Table styles */
    .table {
        width: 100%;
        background: white;
        border-collapse: collapse;
        border-radius: 10px;
        overflow: hidden;
    }
    
    .table th, .table td {
        padding: 12px;
        text-align: left;
        border-bottom: 1px solid #eee;
    }
    
    .table th {
        background: #f8f9fa;
        font-weight: 600;
    }
    
    /* Button styles */
    .btn {
        display: inline-block;
        padding: 10px 20px;
        background: #667eea;
        color: white;
        text-decoration: none;
        border-radius: 5px;
        border: none;
        cursor: pointer;
        transition: background 0.3s;
    }
    
    .btn:hover {
        background: #5a67d8;
        color: white;
    }
    
    .btn-danger {
        background: #ef4444;
    }
    
    .btn-danger:hover {
        background: #dc2626;
    }
    
    .btn-success {
        background: #10b981;
    }
    
    .btn-success:hover {
        background: #059669;
    }
    
    /* Alert styles */
    .alert {
        padding: 12px;
        border-radius: 5px;
        margin-bottom: 20px;
    }
    
    .alert-success {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    
    .alert-danger {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
    
    .alert-warning {
        background: #fff3cd;
        color: #856404;
        border: 1px solid #ffeeba;
    }
    
    .alert-info {
        background: #d1ecf1;
        color: #0c5460;
        border: 1px solid #bee5eb;
    }
    
    /* Stock status */
    .stock-low {
        color: #f59e0b;
        font-weight: bold;
    }
    
    .stock-out {
        color: #ef4444;
        font-weight: bold;
    }
    
    .stock-normal {
        color: #10b981;
    }
    
    @media (max-width: 768px) {
        .main-content {
            margin-top: 70px;
            padding: 15px;
        }
        .card-header {
            padding: 12px 15px;
        }
        .card-body {
            padding: 15px;
        }
        .table th, .table td {
            padding: 8px;
            font-size: 14px;
        }
    }
    
    @media (max-width: 480px) {
        .main-content {
            padding: 10px;
        }
        .btn {
            padding: 8px 16px;
            font-size: 14px;
        }
    }
</style>

<div class="main-content">
    <div class="container-fluid">
        <?php echo $content; ?>
    </div>
</div>

<?php
// Inclure le footer
include __DIR__ . '/footer.php';
?>