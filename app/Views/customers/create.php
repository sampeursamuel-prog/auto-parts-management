<?php
// Vue création client
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
                    <h5 class="mb-0"><i class="fas fa-user-plus"></i> Nouveau client</h5>
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

                    <form method="POST" action="<?php echo \BASE_PATH; ?>/index.php?action=customer_store">
                        <div class="form-section">
                            <h6><i class="fas fa-user"></i> Informations personnelles</h6>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Nom *</label>
                                    <input type="text" name="nom" class="form-control" required 
                                           value="<?php echo htmlspecialchars($_SESSION['form_data']['nom'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Prénom</label>
                                    <input type="text" name="prenom" class="form-control"
                                           value="<?php echo htmlspecialchars($_SESSION['form_data']['prenom'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <h6><i class="fas fa-address-card"></i> Coordonnées</h6>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Téléphone</label>
                                    <input type="tel" name="telephone" class="form-control"
                                           value="<?php echo htmlspecialchars($_SESSION['form_data']['telephone'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" name="email" class="form-control"
                                           value="<?php echo htmlspecialchars($_SESSION['form_data']['email'] ?? ''); ?>">
                                </div>
                                <div class="col-12 mb-3">
                                    <label class="form-label">Adresse</label>
                                    <textarea name="adresse" class="form-control" rows="2"><?php echo htmlspecialchars($_SESSION['form_data']['adresse'] ?? ''); ?></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <h6><i class="fas fa-car"></i> Informations véhicule</h6>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Plaque d'immatriculation</label>
                                    <input type="text" name="plaque_immatriculation" class="form-control" placeholder="Ex: AB-123-CD"
                                           value="<?php echo htmlspecialchars($_SESSION['form_data']['plaque_immatriculation'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Type de client</label>
                                    <select name="type_client" class="form-select">
                                        <option value="particulier" <?php echo (($_SESSION['form_data']['type_client'] ?? '') == 'particulier') ? 'selected' : ''; ?>>Particulier</option>
                                        <option value="professionnel" <?php echo (($_SESSION['form_data']['type_client'] ?? '') == 'professionnel') ? 'selected' : ''; ?>>Professionnel</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="<?php echo \BASE_PATH; ?>/index.php?action=customers" class="btn btn-secondary">Annuler</a>
                            <button type="submit" class="btn btn-primary">Créer le client</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php unset($_SESSION['form_data']); ?>