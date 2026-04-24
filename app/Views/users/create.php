<?php
// Vue création utilisateur
?>
<style>
    .form-section {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 10px;
        margin-bottom: 20px;
    }
    .form-section h6 {
        margin-bottom: 15px;
        color: #667eea;
    }
</style>

<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-user-plus"></i> Créer un utilisateur</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($_SESSION['form_errors'])): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($_SESSION['form_errors'] as $error): ?>
                                    <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php unset($_SESSION['form_errors']); ?>
                    <?php endif; ?>

                    <form method="POST" action="<?php echo \BASE_PATH; ?>/index.php?action=user_store">
                        <div class="form-section">
                            <h6><i class="fas fa-user"></i> Informations personnelles</h6>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Nom d'utilisateur *</label>
                                    <input type="text" name="username" class="form-control" required value="<?php echo htmlspecialchars($_SESSION['form_data']['username'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email *</label>
                                    <input type="email" name="email" class="form-control" required value="<?php echo htmlspecialchars($_SESSION['form_data']['email'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Nom *</label>
                                    <input type="text" name="nom" class="form-control" required value="<?php echo htmlspecialchars($_SESSION['form_data']['nom'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Prénom</label>
                                    <input type="text" name="prenom" class="form-control" value="<?php echo htmlspecialchars($_SESSION['form_data']['prenom'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Téléphone</label>
                                    <input type="tel" name="telephone" class="form-control" value="<?php echo htmlspecialchars($_SESSION['form_data']['telephone'] ?? ''); ?>">
                                </div>
                                <div class="col-12 mb-3">
                                    <label class="form-label">Adresse</label>
                                    <textarea name="adresse" class="form-control" rows="2"><?php echo htmlspecialchars($_SESSION['form_data']['adresse'] ?? ''); ?></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <h6><i class="fas fa-lock"></i> Sécurité</h6>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Mot de passe *</label>
                                    <input type="password" name="password" class="form-control" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Confirmer le mot de passe *</label>
                                    <input type="password" name="password_confirm" class="form-control" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input type="checkbox" name="notification_email" class="form-check-input" value="1" checked>
                                        <label class="form-check-label">Notifications email</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input type="checkbox" name="notification_sms" class="form-check-input" value="1">
                                        <label class="form-check-label">Notifications SMS</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input type="checkbox" name="deux_facteurs" class="form-check-input" value="1">
                                        <label class="form-check-label">Double authentification</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <h6><i class="fas fa-building"></i> Affectation</h6>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Rôle *</label>
                                    <select name="id_role" id="id_role" class="form-select" required>
                                        <option value="">-- Sélectionner --</option>
                                        <?php foreach ($roles as $role): ?>
                                        <option value="<?php echo $role['id_role']; ?>" <?php echo (($_SESSION['form_data']['id_role'] ?? '') == $role['id_role']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($role['nom_role']); ?> (Niveau <?php echo $role['niveau']; ?>)
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Magasin d'attache *</label>
                                    <select name="id_magasin_attache" class="form-select" required>
                                        <option value="">-- Sélectionner --</option>
                                        <?php foreach ($magasins as $mag): ?>
                                        <option value="<?php echo $mag['id_magasin']; ?>" <?php echo (($_SESSION['form_data']['id_magasin_attache'] ?? '') == $mag['id_magasin']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($mag['nom_magasin']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Magasin par défaut</label>
                                    <select name="id_magasin_defaut" class="form-select">
                                        <option value="">-- Même que magasin d'attache --</option>
                                        <?php foreach ($magasins as $mag): ?>
                                        <option value="<?php echo $mag['id_magasin']; ?>">
                                            <?php echo htmlspecialchars($mag['nom_magasin']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3" id="managerField" style="display: none;">
                                    <label class="form-label">Manager / Superviseur</label>
                                    <select name="id_manager" class="form-select">
                                        <option value="">-- Aucun --</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="<?php echo \BASE_PATH; ?>/index.php?action=users" class="btn btn-secondary">Annuler</a>
                            <button type="submit" class="btn btn-primary">Créer l'utilisateur</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Gestion de l'affichage du champ manager selon le rôle
document.getElementById('id_role').addEventListener('change', function() {
    let roleId = this.value;
    let managerField = document.getElementById('managerField');
    let managerSelect = document.querySelector('select[name="id_manager"]');
    
    if (roleId == 4 || roleId == 5) { // Caissier ou Magasinier
        managerField.style.display = 'block';
        // Charger les superviseurs disponibles
        let magasinId = document.querySelector('select[name="id_magasin_attache"]').value;
        if (magasinId) {
            fetch('<?php echo \BASE_PATH; ?>/index.php?action=get_managers&role_id=' + roleId + '&magasin_id=' + magasinId)
                .then(response => response.json())
                .then(data => {
                    managerSelect.innerHTML = '<option value="">-- Aucun --</option>';
                    data.forEach(manager => {
                        managerSelect.innerHTML += `<option value="${manager.id_user}">${manager.nom} ${manager.prenom}</option>`;
                    });
                });
        }
    } else {
        managerField.style.display = 'none';
    }
});
</script>

<?php unset($_SESSION['form_data']); ?>