<?php
$title = 'Scanner - Auto-Parts';
include dirname(__DIR__) . '/layouts/header.php';
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2><i class="fas fa-qrcode"></i> Scanner code-barres</h2>
                <a href="<?php echo \BASE_PATH; ?>/index.php?action=dashboard" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Retour
                </a>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-qrcode"></i> Scanner un code-barres</h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <i class="fas fa-camera fa-4x text-primary"></i>
                        <h4 class="mt-2">Prêt à scanner</h4>
                        <p class="text-muted">Scannez un code-barres avec votre lecteur</p>
                    </div>
                    
                    <div class="input-group mb-4">
                        <span class="input-group-text bg-white"><i class="fas fa-qrcode text-primary"></i></span>
                        <input type="text" id="barcodeInput" class="form-control form-control-lg" placeholder="Scannez un code-barres..." autofocus>
                        <button class="btn btn-primary" onclick="processScan()">Rechercher</button>
                    </div>
                    
                    <div id="resultPanel" class="card mt-3" style="display: none;">
                        <div class="card-header">
                            <h5 class="mb-0">Résultat du scan</h5>
                        </div>
                        <div class="card-body" id="resultContent">
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-cog"></i> Configuration du scanner</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?php echo \BASE_PATH; ?>/index.php?action=scanner_configure">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label>Type de scanner</label>
                                <select name="scanner_type" class="form-select">
                                    <option value="usb">USB (Clavier)</option>
                                    <option value="network">Réseau</option>
                                    <option value="serial">Série (COM)</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Port / Adresse</label>
                                <input type="text" name="scanner_port" class="form-control" placeholder="/dev/ttyUSB0 ou COM3">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">Sauvegarder</button>
                        <button type="button" class="btn btn-secondary" onclick="testScanner()">Tester le scanner</button>
                    </form>
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
        processScan();
    } else {
        barcodeBuffer += e.key;
        this.value = barcodeBuffer;
    }
});

function processScan() {
    let barcode = document.getElementById('barcodeInput').value.trim();
    if (!barcode) {
        alert('Veuillez scanner un code-barres');
        return;
    }
    
    fetch('<?php echo \BASE_PATH; ?>/index.php?action=scanner_process', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'barcode=' + encodeURIComponent(barcode) + '&context=sale'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displayResult(data);
        } else {
            alert('Erreur: ' + data.message);
        }
        document.getElementById('barcodeInput').value = '';
        barcodeBuffer = '';
        document.getElementById('barcodeInput').focus();
    });
}

function displayResult(data) {
    const panel = document.getElementById('resultPanel');
    const content = document.getElementById('resultContent');
    
    if (data.type === 'product') {
        content.innerHTML = `
            <div class="alert alert-success">
                <strong>✅ Produit trouvé !</strong>
            </div>
            <table class="table">
                <tr><th>Code-barres</th><td>${data.data.barcode}</td></tr>
                <tr><th>Produit</th><td>${data.data.name}</td></tr>
                <tr><th>Prix</th><td>${new Intl.NumberFormat('fr-FR').format(data.data.price)} HTG</td></tr>
                <tr><th>Stock</th><td>${data.data.stock}</td></tr>
                ${data.data.description ? `<tr><th>Description</th><td>${data.data.description}</td></tr>` : ''}
            </table>
            <div class="text-end">
                <a href="${BASE_PATH}/index.php?action=pos" class="btn btn-primary">Ajouter au panier</a>
            </div>
        `;
    } else {
        content.innerHTML = `<div class="alert alert-info">${JSON.stringify(data)}</div>`;
    }
    
    panel.style.display = 'block';
}

function testScanner() {
    fetch('<?php echo \BASE_PATH; ?>/index.php?action=scanner_test')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Scanner prêt à l\'emploi !');
            } else {
                alert('Erreur: ' + data.message);
            }
        });
}
</script>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>