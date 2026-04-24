<?php
$title = 'Nouveau client';
include dirname(__DIR__) . '/layouts/header.php';

$form_data = $_SESSION['form_data'] ?? [];
$form_errors = $_SESSION['form_errors'] ?? [];
unset($_SESSION['form_data'], $_SESSION['form_errors']);
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2><i class="fas fa-user-plus"></i> Nouveau client</h2>
                <a href="<?php echo \BASE_PATH; ?>/index.php?action=clients" class="btn btn-secondary">
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
                    <h5 class="mb-0"><i class="fas fa-user"></i> Informations client</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?php echo \BASE_PATH; ?>/index.php?action=client_store">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="nom" class="form-label fw-bold">Nom *</label>
                                <input type="text" name="nom" id="nom" class="form-control" 
                                       value="<?php echo htmlspecialchars($form_data['nom'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="prenom" class="form-label">Prénom</label>
                                <input type="text" name="prenom" id="prenom" class="form-control" 
                                       value="<?php echo htmlspecialchars($form_data['prenom'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="telephone" class="form-label">Téléphone</label>
                                <input type="tel" name="telephone" id="telephone" class="form-control" 
                                       value="<?php echo htmlspecialchars($form_data['telephone'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" name="email" id="email" class="form-control" 
                                       value="<?php echo htmlspecialchars($form_data['email'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="adresse" class="form-label">Adresse</label>
                            <textarea name="adresse" id="adresse" class="form-control" rows="2"><?php echo htmlspecialchars($form_data['adresse'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="plaque_immatriculation" class="form-label">
                                    <i class="fas fa-car"></i> Plaque d'immatriculation
                                </label>
                                <input type="text" name="plaque_immatriculation" id="plaque_immatriculation" class="form-control" 
                                       placeholder="Ex: AB-123-CD"
                                       value="<?php echo htmlspecialchars($form_data['plaque_immatriculation'] ?? ''); ?>">
                                <small class="text-muted">Optionnel - Pour le suivi véhicule</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="type_client" class="form-label">Type de client</label>
                                <select name="type_client" id="type_client" class="form-select">
                                    <option value="particulier" <?php echo ($form_data['type_client'] ?? '') == 'particulier' ? 'selected' : ''; ?>>Particulier</option>
                                    <option value="entreprise" <?php echo ($form_data['type_client'] ?? '') == 'entreprise' ? 'selected' : ''; ?>>Entreprise</option>
                                    <option value="grossiste" <?php echo ($form_data['type_client'] ?? '') == 'grossiste' ? 'selected' : ''; ?>>Grossiste</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="alert alert-info mt-3">
                            <i class="fas fa-info-circle"></i>
                            <strong>Programme de fidélité :</strong><br>
                            - 1 point par tranche de 100 G dépensés<br>
                            - 1000 points = 5% de réduction<br>
                            - 5000 points = 10% de réduction<br>
                            - 10000 points = 15% de réduction
                        </div>
                        
                        <div class="text-end mt-4">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-save"></i> Créer le client
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>