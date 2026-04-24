<?php
$title = 'Mon profil';
include dirname(__DIR__) . '/layouts/header.php';
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2><i class="fas fa-user-circle"></i> Mon profil</h2>
                <a href="<?php echo \BASE_PATH; ?>/index.php?action=dashboard" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Retour
                </a>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Informations personnelles -->
        <div class="col-md-4 mb-4">
            <div class="card text-center">
                <div class="card-body">
                    <div class="mb-3">
                        <i class="fas fa-user-circle fa-5x text-primary"></i>
                    </div>
                    <h4><?php echo htmlspecialchars($user['nom'] . ' ' . ($user['prenom'] ?? '')); ?></h4>
                    <p class="text-muted">@<?php echo htmlspecialchars($user['username']); ?></p>
                    <span class="badge bg-<?php 
                        echo $role['niveau'] == 1 ? 'danger' : ($role['niveau'] == 2 ? 'warning' : ($role['niveau'] == 3 ? 'info' : 'secondary')); 
                    ?>">
                        <i class="fas fa-tag"></i> <?php echo htmlspecialchars($role['nom_role']); ?>
                    </span>
                    <?php if ($magasin): ?>
                        <div class="mt-2">
                            <span class="badge bg-primary">
                                <i class="fas fa-store"></i> <?php echo htmlspecialchars($magasin['nom_magasin']); ?>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-chart-line"></i> Statistiques</h5>
                </div>
                <div class="card-body text-center">
                    <div class="row">
                        <div class="col-6">
                            <div class="display-6 text-primary"><?php echo date('d/m/Y', strtotime($user['date_creation'])); ?></div>
                            <small class="text-muted">Inscription</small>
                        </div>
                        <div class="col-6">
                            <div class="display-6 text-primary">
                                <?php echo $user['derniere_connexion'] ? date('d/m/Y', strtotime($user['derniere_connexion'])) : '-'; ?>
                            </div>
                            <small class="text-muted">Dernière connexion</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Détails du compte -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-id-card"></i> Informations personnelles</h5>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tr>
                            <th style="width: 30%;">Nom d'utilisateur</th>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                        </tr>
                        <tr>
                            <th>Nom complet</th>
                            <td><?php echo htmlspecialchars($user['nom'] . ' ' . ($user['prenom'] ?? '')); ?></td>
                        </tr>
                        <tr>
                            <th>Email</th>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                        </tr>
                        <tr>
                            <th>Téléphone</th>
                            <td><?php echo htmlspecialchars($user['telephone'] ?? '-'); ?></td>
                        </tr>
                        <tr>
                            <th>Adresse</th>
                            <td><?php echo nl2br(htmlspecialchars($user['adresse'] ?? '-')); ?></td>
                        </tr>
                        <tr>
                            <th>Rôle</th>
                            <td>
                                <span class="badge bg-<?php 
                                    echo $role['niveau'] == 1 ? 'danger' : ($role['niveau'] == 2 ? 'warning' : ($role['niveau'] == 3 ? 'info' : 'secondary')); 
                                ?>">
                                    <?php echo htmlspecialchars($role['nom_role']); ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>Magasin d'attache</th>
                            <td><?php echo $magasin ? htmlspecialchars($magasin['nom_magasin']) : '-'; ?></td>
                        </tr>
                        <tr>
                            <th>Date d'inscription</th>
                            <td><?php echo date('d/m/Y H:i:s', strtotime($user['date_creation'])); ?></td>
                        </tr>
                        <tr>
                            <th>Dernière connexion</th>
                            <td><?php echo $user['derniere_connexion'] ? date('d/m/Y H:i:s', strtotime($user['derniere_connexion'])) : 'Jamais'; ?></td>
                        </tr>
                    </table>
                </div>
            </div>
            
            <!-- Changement de mot de passe -->
            <div class="card mt-4">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="fas fa-key"></i> Changer mon mot de passe</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?php echo \BASE_PATH; ?>/index.php?action=user_change_password">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="current_password" class="form-label fw-bold">Mot de passe actuel</label>
                                <input type="password" name="current_password" id="current_password" class="form-control" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="new_password" class="form-label fw-bold">Nouveau mot de passe</label>
                                <input type="password" name="new_password" id="new_password" class="form-control" required>
                                <small class="text-muted">Minimum 6 caractères</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="confirm_password" class="form-label fw-bold">Confirmer le nouveau mot de passe</label>
                                <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
                            </div>
                        </div>
                        <div class="text-end">
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-key"></i> Changer le mot de passe
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Notifications -->
            <div class="card mt-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-bell"></i> Préférences de notifications</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?php echo \BASE_PATH; ?>/index.php?action=notification_update_settings">
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" name="notification_email" id="notification_email" 
                                <?php echo ($user['notification_email'] == 1) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="notification_email">
                                <i class="fas fa-envelope"></i> Recevoir les notifications par email
                            </label>
                        </div>
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" name="notification_sms" id="notification_sms" 
                                <?php echo ($user['notification_sms'] == 1) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="notification_sms">
                                <i class="fas fa-phone"></i> Recevoir les notifications par SMS
                            </label>
                        </div>
                        <button type="submit" class="btn btn-info">
                            <i class="fas fa-save"></i> Sauvegarder les préférences
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Mes permissions -->
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-lock"></i> Mes permissions</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($permissions)): ?>
                        <div class="text-center text-muted py-3">
                            <i class="fas fa-shield-alt fa-2x mb-2"></i>
                            <p>Aucune permission spécifique</p>
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <?php 
                            $modules = [];
                            foreach ($permissions as $perm) {
                                if (!isset($modules[$perm['module']])) {
                                    $modules[$perm['module']] = [];
                                }
                                $modules[$perm['module']][] = $perm['nom_permission'];
                            }
                            ?>
                            <?php foreach ($modules as $module => $perms): ?>
                            <div class="col-md-4 mb-3">
                                <div class="card h-100">
                                    <div class="card-header bg-light">
                                        <strong><i class="fas fa-folder"></i> <?php echo ucfirst($module); ?></strong>
                                    </div>
                                    <div class="card-body">
                                        <ul class="list-unstyled mb-0">
                                            <?php foreach ($perms as $perm): ?>
                                            <li>
                                                <i class="fas fa-check-circle text-success"></i>
                                                <?php echo ucfirst(str_replace('_', ' ', $perm)); ?>
                                            </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>