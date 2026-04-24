<?php
$title = 'Modifier un utilisateur';
include dirname(__DIR__) . '/layouts/header.php';

$form_data = $_SESSION['form_data'] ?? [];
$form_errors = $_SESSION['form_errors'] ?? [];
unset($_SESSION['form_data'], $_SESSION['form_errors']);
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2><i class="fas fa-user-edit"></i> Modifier un utilisateur</h2>
                <a href="<?php echo \BASE_PATH; ?>/index.php?action=users" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Retour
                </a>
            </div>
        </div>
    </div>
    
    <?php if (!empty($form_errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($form_errors as $error): ?>
                    <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-user-cog"></i> Informations de l'utilisateur</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?php echo \BASE_PATH; ?>/index.php?action=user_update">
                        <input type="hidden" name="id" value="<?php echo $user['id_user']; ?>">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="username" class="form-label fw-bold">Nom d'utilisateur *</label>
                                <input type="text" name="username" id="username" class="form-control" 
                                       value="<?php echo htmlspecialchars($user['username']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label fw-bold">Email *</label>
                                <input type="email" name="email" id="email" class="form-control" 
                                       value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="nom" class="form-label fw-bold">Nom *</label>
                                <input type="text" name="nom" id="nom" class="form-control" 
                                       value="<?php echo htmlspecialchars($user['nom']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="prenom" class="form-label">Prénom</label>
                                <input type="text" name="prenom" id="prenom" class="form-control" 
                                       value="<?php echo htmlspecialchars($user['prenom'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="telephone" class="form-label">Téléphone</label>
                                <input type="tel" name="telephone" id="telephone" class="form-control" 
                                       value="<?php echo htmlspecialchars($user['telephone'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="id_role" class="form-label fw-bold">Rôle *</label>
                                <select name="id_role" id="id_role" class="form-select" required>
                                    <option value="">-- Sélectionner --</option>
                                    <?php foreach ($roles as $role): ?>
                                        <option value="<?php echo $role['id_role']; ?>" 
                                            <?php echo ($user['id_role'] == $role['id_role']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($role['nom_role']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="id_magasin_defaut" class="form-label">Magasin par défaut</label>
                                <select name="id_magasin_defaut" id="id_magasin_defaut" class="form-select">
                                    <option value="">-- Aucun --</option>
                                    <?php foreach ($magasins as $magasin): ?>
                                        <option value="<?php echo $magasin['id_magasin']; ?>" 
                                            <?php echo ($user['id_magasin_defaut'] == $magasin['id_magasin']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($magasin['nom_magasin']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="id_manager" class="form-label">Manager / Supérieur</label>
                                <select name="id_manager" id="id_manager" class="form-select">
                                    <option value="">-- Aucun --</option>
                                    <?php foreach ($users as $supervisor): ?>
                                        <?php if ($supervisor['id_user'] != $user['id_user']): ?>
                                            <option value="<?php echo $supervisor['id_user']; ?>" 
                                                <?php echo ($user['id_manager'] == $supervisor['id_user']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($supervisor['nom'] . ' ' . ($supervisor['prenom'] ?? '')); ?>
                                                (<?php echo $supervisor['nom_role']; ?>)
                                            </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="adresse" class="form-label">Adresse</label>
                            <textarea name="adresse" id="adresse" class="form-control" rows="2"><?php echo htmlspecialchars($user['adresse'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="est_actif" id="est_actif" value="1" 
                                        <?php echo ($user['est_actif'] == 1) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="est_actif">
                                        <i class="fas fa-check-circle text-success"></i> Compte actif
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="notification_email" id="notification_email" value="1" 
                                        <?php echo ($user['notification_email'] == 1) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="notification_email">
                                        <i class="fas fa-envelope"></i> Notifications par email
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="alert alert-info mt-3">
                            <i class="fas fa-info-circle"></i>
                            <strong>Information :</strong> Pour changer le mot de passe, utilisez le formulaire ci-dessous.
                        </div>
                        
                        <div class="text-end mt-4">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-save"></i> Enregistrer les modifications
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Formulaire de changement de mot de passe -->
            <div class="card mt-4">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="fas fa-key"></i> Changer le mot de passe</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?php echo \BASE_PATH; ?>/index.php?action=user_change_password">
                        <input type="hidden" name="id" value="<?php echo $user['id_user']; ?>">
                        
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="current_password" class="form-label fw-bold">Mot de passe actuel</label>
                                <input type="password" name="current_password" id="current_password" class="form-control" required>
                                <small class="text-muted">Requis uniquement pour modifier votre propre mot de passe</small>
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
        </div>
    </div>
</div>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>