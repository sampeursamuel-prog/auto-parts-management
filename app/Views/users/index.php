<?php
// Vue liste des utilisateurs
?>
<style>
    .stat-card {
        transition: transform 0.3s;
        cursor: pointer;
    }
    .stat-card:hover {
        transform: translateY(-5px);
    }
    .user-row:hover {
        background: #f8f9fa;
    }
</style>

<div class="container-fluid">
    <?php $flash = \App\Helpers\Session::getFlash(); ?>
    <?php if ($flash): ?>
        <div class="alert alert-<?php echo $flash['type']; ?>">
            <?php echo $flash['message']; ?>
        </div>
    <?php endif; ?>

    <!-- Statistiques -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card stat-card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">Total utilisateurs</h6>
                            <h2 class="mb-0"><?php echo $stats['total'] ?? 0; ?></h2>
                        </div>
                        <i class="fas fa-users fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stat-card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">Actifs</h6>
                            <h2 class="mb-0"><?php echo $stats['actifs'] ?? 0; ?></h2>
                        </div>
                        <i class="fas fa-user-check fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stat-card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">Administrateurs</h6>
                            <h2 class="mb-0"><?php echo $stats['admins'] ?? 0; ?></h2>
                        </div>
                        <i class="fas fa-user-shield fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stat-card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">Caissiers</h6>
                            <h2 class="mb-0"><?php echo $stats['caissiers'] ?? 0; ?></h2>
                        </div>
                        <i class="fas fa-cash-register fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-users-cog"></i> Liste des utilisateurs</h5>
                <a href="<?php echo \BASE_PATH; ?>/index.php?action=user_create" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> Nouvel utilisateur
                </a>
            </div>
        </div>
        <div class="card-body">
            <?php if (empty($users)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-users fa-4x text-muted mb-3"></i>
                    <p>Aucun utilisateur trouvé</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Utilisateur</th>
                                <th>Email</th>
                                <th>Téléphone</th>
                                <th>Rôle</th>
                                <th>Magasin</th>
                                <th>Statut</th>
                                <th>Dernière connexion</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr class="user-row">
                                <td>
                                    <strong><?php echo htmlspecialchars($user['username']); ?></strong><br>
                                    <small><?php echo htmlspecialchars($user['nom'] . ' ' . ($user['prenom'] ?? '')); ?></small>
                                 </td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['telephone'] ?? '-'); ?></td>
                                <td>
                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($user['nom_role']); ?></span>
                                 </td>
                                <td><?php echo htmlspecialchars($user['nom_magasin'] ?? '-'); ?></td>
                                <td>
                                    <?php if ($user['est_actif']): ?>
                                        <span class="badge bg-success">Actif</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Inactif</span>
                                    <?php endif; ?>
                                 </td>
                                <td><?php echo $user['derniere_connexion'] ? date('d/m/Y H:i', strtotime($user['derniere_connexion'])) : '-'; ?></td>
                                 <td>
                                    <a href="<?php echo \BASE_PATH; ?>/index.php?action=user_edit&id=<?php echo $user['id_user']; ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php if ($user['id_user'] != $_SESSION['user_id']): ?>
                                    <a href="<?php echo \BASE_PATH; ?>/index.php?action=user_delete&id=<?php echo $user['id_user']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Supprimer cet utilisateur ?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                    <?php endif; ?>
                                 </td>
                             </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>