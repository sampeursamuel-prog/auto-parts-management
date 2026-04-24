<?php
$title = 'Gestion des permissions - ' . $role['nom_role'];
include dirname(__DIR__) . '/layouts/header.php';
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h2 class="fw-bold mb-1">
                        <i class="fas fa-lock" style="color: #667eea;"></i> Gestion des permissions
                    </h2>
                    <p class="text-muted">Rôle : <strong><?php echo htmlspecialchars($role['nom_role']); ?></strong></p>
                </div>
                <div class="d-flex gap-2">
                    <a href="<?php echo \BASE_PATH; ?>/index.php?action=users" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Retour aux utilisateurs
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-key"></i> Permissions du rôle <?php echo htmlspecialchars($role['nom_role']); ?></h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?php echo \BASE_PATH; ?>/index.php?action=user_save_permissions">
                        <input type="hidden" name="role_id" value="<?php echo $role['id_role']; ?>">
                        
                        <?php
                        $modules = [];
                        foreach ($allPermissions as $perm) {
                            if (!isset($modules[$perm['module']])) {
                                $modules[$perm['module']] = [];
                            }
                            $modules[$perm['module']][] = $perm;
                        }
                        ?>
                        
                        <div class="row">
                            <?php foreach ($modules as $module => $perms): ?>
                            <div class="col-md-6 mb-4">
                                <div class="card h-100">
                                    <div class="card-header bg-light">
                                        <strong><i class="fas fa-folder-open"></i> Module : <?php echo ucfirst($module); ?></strong>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-2">
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input" id="check_all_<?php echo $module; ?>" 
                                                       onclick="toggleModule('<?php echo $module; ?>', this.checked)">
                                                <label class="form-check-label fw-bold" for="check_all_<?php echo $module; ?>">
                                                    <i class="fas fa-check-double"></i> Tout cocher / décocher
                                                </label>
                                            </div>
                                            <hr>
                                        </div>
                                        
                                        <?php foreach ($perms as $perm): ?>
                                        <div class="form-check mb-2">
                                            <input type="checkbox" class="form-check-input permission-checkbox module-<?php echo $module; ?>" 
                                                   name="permissions[]" value="<?php echo $perm['id_permission']; ?>"
                                                   id="perm_<?php echo $perm['id_permission']; ?>"
                                                   <?php echo in_array($perm['id_permission'], $rolePermissionIds) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="perm_<?php echo $perm['id_permission']; ?>">
                                                <i class="fas fa-<?php 
                                                    echo $perm['nom_permission'] == 'create' ? 'plus-circle text-success' : 
                                                        ($perm['nom_permission'] == 'read' ? 'eye text-info' : 
                                                        ($perm['nom_permission'] == 'update' ? 'edit text-warning' : 
                                                        ($perm['nom_permission'] == 'delete' ? 'trash text-danger' : 'circle'))); 
                                                ?>"></i>
                                                <?php 
                                                    $label = ucfirst(str_replace('_', ' ', $perm['nom_permission']));
                                                    echo $label;
                                                ?>
                                            </label>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="alert alert-info mt-3">
                            <i class="fas fa-info-circle"></i>
                            <strong>Information :</strong> Les permissions définies ici s'appliquent à tous les utilisateurs ayant ce rôle.
                            Les modifications sont immédiates.
                        </div>
                        
                        <div class="text-end mt-4">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-save"></i> Sauvegarder les permissions
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleModule(module, checked) {
    let checkboxes = document.querySelectorAll('.module-' + module);
    checkboxes.forEach(cb => {
        cb.checked = checked;
    });
}

// Vérifier l'état des cases "Tout cocher" au chargement
document.addEventListener('DOMContentLoaded', function() {
    <?php foreach ($modules as $module => $perms): ?>
        let allChecked_<?php echo $module; ?> = true;
        let checkboxes_<?php echo $module; ?> = document.querySelectorAll('.module-<?php echo $module; ?>');
        checkboxes_<?php echo $module; ?>.forEach(cb => {
            if (!cb.checked) allChecked_<?php echo $module; ?> = false;
        });
        let checkAll_<?php echo $module; ?> = document.getElementById('check_all_<?php echo $module; ?>');
        if (checkAll_<?php echo $module; ?>) {
            checkAll_<?php echo $module; ?>.checked = allChecked_<?php echo $module; ?>;
        }
    <?php endforeach; ?>
});
</script>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>