<?php
$title = 'Détails du magasin - ' . $magasin['nom_magasin'];
include dirname(__DIR__) . '/layouts/header.php';
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2><i class="fas fa-store"></i> <?php echo htmlspecialchars($magasin['nom_magasin']); ?></h2>
                <a href="<?php echo \BASE_PATH; ?>/index.php?action=magasins" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Retour
                </a>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Informations générales</h5>
                </div>
                <div class="card-body">
                    <p><strong>Code:</strong> <?php echo htmlspecialchars($magasin['code_magasin']); ?></p>
                    <p><strong>Adresse:</strong> <?php echo htmlspecialchars($magasin['adresse'] ?? '-'); ?></p>
                    <p><strong>Ville:</strong> <?php echo htmlspecialchars($magasin['ville'] ?? '-'); ?></p>
                    <p><strong>Téléphone:</strong> <?php echo htmlspecialchars($magasin['telephone'] ?? '-'); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($magasin['email'] ?? '-'); ?></p>
                    <p><strong>Date création:</strong> <?php echo date('d/m/Y', strtotime($magasin['date_creation'])); ?></p>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Équipe du magasin</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <?php foreach ($stats as $stat): ?>
                        <div class="col text-center">
                            <div class="small text-muted"><?php echo $stat['nom_role']; ?></div>
                            <div class="h4"><?php echo $stat['total']; ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                发展
                                    <th>Utilisateur</th>
                                    <th>Rôle</th>
                                    <th>Email</th>
                                    <th>Manager</th>
                                    <th>Statut</th>
                                </thead>
                            <tbody>
                                <?php foreach ($employes as $emp): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($emp['username']); ?> (<?php echo htmlspecialchars($emp['nom'] . ' ' . $emp['prenom']); ?>)</td>
                                    <td><span class="badge bg-secondary"><?php echo $emp['nom_role']; ?></span></td>
                                    <td><?php echo htmlspecialchars($emp['email']); ?></td>
                                    <td><?php 
                                        $manager = $this->userModel->find($emp['id_manager']);
                                        echo $manager ? htmlspecialchars($manager['nom'] . ' ' . $manager['prenom']) : '-';
                                    ?></td>
                                    <td><?php echo $emp['est_actif'] ? '<span class="badge bg-success">Actif</span>' : '<span class="badge bg-secondary">Inactif</span>'; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>