<?php
$title = 'Historique des mouvements de stock';
include dirname(__DIR__) . '/layouts/header.php';
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2><i class="fas fa-history"></i> Historique des mouvements de stock</h2>
                <div>
                    <a href="<?php echo \BASE_PATH; ?>/index.php?action=stock" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Retour au stock
                    </a>
                    <button onclick="window.print()" class="btn btn-primary">
                        <i class="fas fa-print"></i> Imprimer
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">📋 Liste des mouvements</h5>
                </div>
                <div class="card-body">
                    <!-- Filtres -->
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <input type="text" id="searchInput" class="form-control" placeholder="🔍 Rechercher un produit...">
                        </div>
                        <div class="col-md-3">
                            <select id="typeFilter" class="form-select">
                                <option value="">Tous les types</option>
                                <option value="entree">Entrée</option>
                                <option value="sortie_vente">Sortie vente</option>
                                <option value="retour_client">Retour client</option>
                                <option value="retour_fournisseur">Retour fournisseur</option>
                                <option value="inventaire">Inventaire</option>
                                <option value="ajustement">Ajustement</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <input type="date" id="dateStart" class="form-control" placeholder="Date début">
                        </div>
                        <div class="col-md-3">
                            <input type="date" id="dateEnd" class="form-control" placeholder="Date fin">
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-hover" id="movementsTable">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Produit</th>
                                    <th>Code-barres</th>
                                    <th>Type</th>
                                    <th>Quantité</th>
                                    <th>Stock avant</th>
                                    <th>Stock après</th>
                                    <th>Utilisateur</th>
                                    <th>Raison</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($movements)): ?>
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-5">
                                        <i class="fas fa-database fa-3x mb-3"></i><br>
                                        Aucun mouvement de stock enregistré
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($movements as $m): ?>
                                <tr class="movement-row" data-type="<?php echo $m['type_mouvement']; ?>" data-product="<?php echo strtolower($m['nom_produit']); ?>">
                                    <td class="text-nowrap">
                                        <?php echo date('d/m/Y H:i', strtotime($m['date_mouvement'])); ?>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($m['nom_produit']); ?></strong>
                                    </td>
                                    <td>
                                        <code><?php echo htmlspecialchars($m['code_barre']); ?></code>
                                    </td>
                                    <td>
                                        <?php
                                        $typeClass = [
                                            'entree' => 'success',
                                            'sortie_vente' => 'danger',
                                            'retour_client' => 'warning',
                                            'retour_fournisseur' => 'info',
                                            'inventaire' => 'primary',
                                            'ajustement' => 'secondary'
                                        ];
                                        $typeIcon = [
                                            'entree' => 'fa-arrow-down',
                                            'sortie_vente' => 'fa-arrow-up',
                                            'retour_client' => 'fa-undo-alt',
                                            'retour_fournisseur' => 'fa-truck',
                                            'inventaire' => 'fa-clipboard-list',
                                            'ajustement' => 'fa-sliders-h'
                                        ];
                                        $typeLabel = [
                                            'entree' => '➕ Entrée',
                                            'sortie_vente' => '➖ Sortie vente',
                                            'retour_client' => '🔄 Retour client',
                                            'retour_fournisseur' => '📦 Retour fournisseur',
                                            'inventaire' => '📊 Inventaire',
                                            'ajustement' => '⚙️ Ajustement'
                                        ];
                                        ?>
                                        <span class="badge bg-<?php echo $typeClass[$m['type_mouvement']] ?? 'secondary'; ?>">
                                            <i class="fas <?php echo $typeIcon[$m['type_mouvement']] ?? 'fa-exchange-alt'; ?>"></i>
                                            <?php echo $typeLabel[$m['type_mouvement']] ?? $m['type_mouvement']; ?>
                                        </span>
                                    </td>
                                    <td class="fw-bold">
                                        <?php echo $m['quantite']; ?>
                                    </td>
                                    <td>
                                        <?php echo $m['stock_avant']; ?>
                                    </td>
                                    <td>
                                        <?php echo $m['stock_apres']; ?>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($m['user_nom'] . ' ' . ($m['user_prenom'] ?? '')); ?>
                                    </td>
                                    <td>
                                        <small class="text-muted"><?php echo htmlspecialchars($m['raison'] ?? '-'); ?></small>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    @media print {
        .btn, .header, .card-header .btn, .row.mb-3 {
            display: none !important;
        }
        .card {
            border: none !important;
            box-shadow: none !important;
        }
        .table {
            font-size: 10px;
        }
    }
    
    .badge {
        padding: 5px 10px;
        border-radius: 20px;
        font-weight: 500;
    }
    
    .table-hover tbody tr:hover {
        background-color: #f8f9fa;
        cursor: pointer;
    }
    
    .movement-row {
        transition: all 0.2s;
    }
</style>

<script>
    // Filtre par recherche
    document.getElementById('searchInput').addEventListener('keyup', function() {
        filterTable();
    });
    
    // Filtre par type
    document.getElementById('typeFilter').addEventListener('change', function() {
        filterTable();
    });
    
    // Filtre par date
    document.getElementById('dateStart').addEventListener('change', function() {
        filterTable();
    });
    
    document.getElementById('dateEnd').addEventListener('change', function() {
        filterTable();
    });
    
    function filterTable() {
        const search = document.getElementById('searchInput').value.toLowerCase();
        const type = document.getElementById('typeFilter').value;
        const dateStart = document.getElementById('dateStart').value;
        const dateEnd = document.getElementById('dateEnd').value;
        
        const rows = document.querySelectorAll('.movement-row');
        
        rows.forEach(row => {
            let show = true;
            
            // Filtre recherche
            if (search) {
                const product = row.cells[1]?.textContent.toLowerCase() || '';
                const barcode = row.cells[2]?.textContent.toLowerCase() || '';
                if (!product.includes(search) && !barcode.includes(search)) {
                    show = false;
                }
            }
            
            // Filtre type
            if (type && show) {
                const rowType = row.getAttribute('data-type');
                if (rowType !== type) {
                    show = false;
                }
            }
            
            // Filtre date
            if ((dateStart || dateEnd) && show) {
                const dateStr = row.cells[0]?.textContent || '';
                const parts = dateStr.split('/');
                if (parts.length === 3) {
                    const rowDate = new Date(parts[2], parts[1]-1, parts[0]);
                    
                    if (dateStart) {
                        const startDate = new Date(dateStart);
                        if (rowDate < startDate) show = false;
                    }
                    
                    if (dateEnd && show) {
                        const endDate = new Date(dateEnd);
                        endDate.setHours(23, 59, 59);
                        if (rowDate > endDate) show = false;
                    }
                }
            }
            
            row.style.display = show ? '' : 'none';
        });
        
        // Afficher message si aucun résultat
        const visibleRows = document.querySelectorAll('.movement-row:not([style*="display: none"])').length;
        const tbody = document.querySelector('#movementsTable tbody');
        const noResultMsg = document.getElementById('noResultMsg');
        
        if (visibleRows === 0 && tbody) {
            if (!noResultMsg) {
                const tr = document.createElement('tr');
                tr.id = 'noResultMsg';
                tr.innerHTML = '<td colspan="9" class="text-center text-muted py-5"><i class="fas fa-search fa-3x mb-3"></i><br>Aucun mouvement ne correspond aux critères</td>';
                tbody.appendChild(tr);
            }
        } else if (noResultMsg) {
            noResultMsg.remove();
        }
    }
    
    // Export CSV
    function exportCSV() {
        const rows = document.querySelectorAll('.movement-row:not([style*="display: none"])');
        if (rows.length === 0) {
            alert('Aucune donnée à exporter');
            return;
        }
        
        let csv = "Date,Produit,Code-barres,Type,Quantité,Stock avant,Stock après,Utilisateur,Raison\n";
        
        rows.forEach(row => {
            const cols = row.querySelectorAll('td');
            const rowData = [];
            cols.forEach(col => {
                let text = col.textContent.trim().replace(/,/g, ';');
                rowData.push(text);
            });
            csv += rowData.join(',') + '\n';
        });
        
        const blob = new Blob(["\uFEFF" + csv], {type: 'text/csv;charset=utf-8;'});
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);
        link.setAttribute('href', url);
        link.setAttribute('download', 'mouvements_stock_' + new Date().toISOString().slice(0,10) + '.csv');
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
    
    // Ajouter bouton export
    document.addEventListener('DOMContentLoaded', function() {
        const cardHeader = document.querySelector('.card-header');
        if (cardHeader) {
            const exportBtn = document.createElement('button');
            exportBtn.className = 'btn btn-sm btn-success float-end';
            exportBtn.innerHTML = '<i class="fas fa-download"></i> Exporter CSV';
            exportBtn.onclick = exportCSV;
            cardHeader.appendChild(exportBtn);
        }
    });
</script>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>