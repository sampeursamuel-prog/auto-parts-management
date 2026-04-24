<?php
// Rapport de caisse
?>
<style>
    .cash-card {
        background: linear-gradient(135deg, #2c3e50, #1a252f);
        color: white;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 20px;
    }
    .cash-positive {
        color: #10b981;
        font-weight: bold;
    }
</style>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-money-bill-wave"></i> Rapport de caisse</h2>
        <div>
            <button class="btn btn-danger" onclick="window.print()">
                <i class="fas fa-print"></i> Imprimer
            </button>
        </div>
    </div>

    <!-- Filtres -->
    <div class="filter-bar">
        <form method="GET" action="<?php echo \BASE_PATH; ?>/index.php?action=reports_cash" class="row">
            <div class="col-md-4">
                <label class="form-label">Date début</label>
                <input type="date" name="date_start" class="form-control" value="<?php echo $dateStart; ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Date fin</label>
                <input type="date" name="date_end" class="form-control" value="<?php echo $dateEnd; ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn btn-primary w-100">Filtrer</button>
            </div>
        </form>
    </div>

    <?php if (empty($cashSessions)): ?>
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="fas fa-money-bill-wave fa-4x text-muted mb-3"></i>
                <p>Aucune session de caisse trouvée pour cette période</p>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($cashSessions as $session): ?>
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-cash-register"></i> Session du <?php echo date('d/m/Y H:i', strtotime($session['date_ouverture'])); ?></h5>
                    <span class="badge bg-light text-dark">Caissier: <?php echo htmlspecialchars($session['caissier']); ?></span>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="cash-card text-center">
                            <small>Montant initial</small>
                            <h3><?php echo number_format($session['montant_initial'], 0, ',', ' '); ?> G</h3>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="cash-card text-center">
                            <small>Ventes du jour</small>
                            <h3><?php echo number_format($session['montant_final'] - $session['montant_initial'], 0, ',', ' '); ?> G</h3>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="cash-card text-center">
                            <small>Montant final</small>
                            <h3><?php echo number_format($session['montant_final'], 0, ',', ' '); ?> G</h3>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="cash-card text-center">
                            <small>Date fermeture</small>
                            <h6><?php echo date('d/m/Y H:i', strtotime($session['date_fermeture'])); ?></h6>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>