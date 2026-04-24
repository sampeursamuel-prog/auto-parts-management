<?php
$title = 'Tableau de bord - Superviseur';
include dirname(__DIR__) . '/layouts/header.php';
?>

<style>
    .stats-card {
        background: white;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    .stats-card h3 {
        color: #666;
        font-size: 14px;
        margin-bottom: 10px;
        text-transform: uppercase;
    }
    .stats-card .value {
        font-size: 32px;
        font-weight: bold;
        color: #333;
    }
    .alert-card {
        background: #fff3cd;
        border-left: 4px solid #ffc107;
        padding: 15px;
        margin-bottom: 15px;
        border-radius: 5px;
    }
    .alert-card-danger {
        background: #f8d7da;
        border-left: 4px solid #dc3545;
    }
</style>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="welcome-card" style="background: white; padding: 30px; border-radius: 10px; margin-bottom: 30px;">
                <h2>Bonjour, <?php echo htmlspecialchars($userName); ?> !</h2>
                <p>Bienvenue dans votre espace superviseur. Suivez les performances de votre magasin.</p>
                <?php if ($currentMagasin): ?>
                <p class="text-muted mt-2">
                    <i class="fas fa-store"></i> Magasin : <strong><?php echo htmlspecialchars($currentMagasin['nom_magasin']); ?></strong>
                </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Alertes importantes -->
    <?php if (!empty($alerts)): ?>
    <div class="row mb-4">
        <div class="col-md-12">
            <h4><i class="fas fa-bell"></i> Alertes</h4>
            <?php foreach ($alerts as $alert): ?>
            <div class="alert-card <?php echo $alert['niveau'] == 'critical' ? 'alert-card-danger' : ''; ?>">
                <strong>
                    <?php 
                    $icons = [
                        'stock_minimum' => '📦',
                        'stock_rupture' => '⚠️',
                        'peremption' => '📅',
                        'caisse_difference' => '💰'
                    ];
                    echo $icons[$alert['type_alerte']] ?? '🔔';
                    ?>
                </strong>
                <?php echo htmlspecialchars($alert['message']); ?>
                <small class="text-muted d-block mt-1"><?php echo date('d/m/Y H:i', strtotime($alert['date_creation'])); ?></small>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Cartes statistiques -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stats-card">
                <h3><i class="fas fa-boxes"></i> Produits</h3>
                <div class="value"><?php echo $stats['total_products'] ?? 0; ?></div>
                <small class="text-muted">en stock</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <h3><i class="fas fa-shopping-cart"></i> Ventes du jour</h3>
                <div class="value"><?php echo number_format($stats['today_sales'] ?? 0, 0, ',', ' '); ?> G</div>
                <small class="text-muted"><?php echo $stats['today_ventes'] ?? 0; ?> transactions</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <h3><i class="fas fa-exclamation-triangle"></i> Stock bas</h3>
                <div class="value"><?php echo $stats['low_stock'] ?? 0; ?></div>
                <small class="text-muted">produits à réapprovisionner</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <h3><i class="fas fa-chart-line"></i> Panier moyen</h3>
                <div class="value"><?php echo number_format($stats['avg_basket'] ?? 0, 0, ',', ' '); ?> G</div>
                <small class="text-muted">par transaction</small>
            </div>
        </div>
    </div>
    
    <!-- Top produits -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-trophy"></i> Top 10 produits</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Produit</th>
                                    <th>Quantité vendue</th>
                                    <th>CA</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($top_products)): ?>
                                <tr><td colspan="4" class="text-center text-muted">Aucune donnée</td></tr>
                                <?php else: ?>
                                <?php foreach ($top_products as $index => $product): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($product['nom_produit']); ?></td>
                                    <td class="text-center"><?php echo $product['quantite_vendue']; ?></td>
                                    <td><?php echo number_format($product['chiffre_affaires'], 0, ',', ' '); ?> G</td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-clock"></i> Sessions de caisse</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Caissier</th>
                                    <th>Ouverture</th>
                                    <th>Fermeture</th>
                                    <th>Ventes</th>
                                    <th>Différence</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($cash_sessions)): ?>
                                <tr><td colspan="5" class="text-center text-muted">Aucune session active</td></tr>
                                <?php else: ?>
                                <?php foreach ($cash_sessions as $session): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($session['caissier']); ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($session['date_ouverture'])); ?></td>
                                    <td><?php echo $session['date_fermeture'] ? date('d/m/Y H:i', strtotime($session['date_fermeture'])) : '-'; ?></td>
                                    <td><?php echo number_format($session['montant_total_ventes'], 0, ',', ' '); ?> G</td>
                                    <td class="<?php echo $session['difference'] >= 0 ? 'text-success' : 'text-danger'; ?>">
                                        <?php echo $session['difference'] >= 0 ? '+' : ''; ?><?php echo number_format($session['difference'], 0, ',', ' '); ?> G
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
    
    <!-- Équipe -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-users"></i> Équipe du magasin</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Nom</th>
                                    <th>Rôle</th>
                                    <th>Email</th>
                                    <th>Dernière connexion</th>
                                    <th>Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($team)): ?>
                                <tr><td colspan="5" class="text-center text-muted">Aucun membre dans l'équipe</td></tr>
                                <?php else: ?>
                                <?php foreach ($team as $member): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($member['nom'] . ' ' . ($member['prenom'] ?? '')); ?></td>
                                    <td><?php echo htmlspecialchars($member['nom_role']); ?></td>
                                    <td><?php echo htmlspecialchars($member['email']); ?></td>
                                    <td><?php echo $member['derniere_connexion'] ? date('d/m/Y H:i', strtotime($member['derniere_connexion'])) : '-'; ?></td>
                                    <td>
                                        <?php if ($member['est_actif']): ?>
                                            <span class="badge bg-success">Actif</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inactif</span>
                                        <?php endif; ?>
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

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>