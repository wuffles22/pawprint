<?php
include 'includes/db.php';
$stmt = $pdo->query("SELECT * FROM pets WHERE status = 'available' ORDER BY id DESC LIMIT 6");
$pets = $stmt->fetchAll();
?>
/*ftf*/
<!DOCTYPE html>
<html>
<?php include 'includes/header.php'; ?>
<body class="bg-light">
<div class="container mt-5">
    <h2 class="text-center mb-4">Pets Available for Adoption</h2>
    
    <!-- Pets Grid -->
    <div class="row">
        <?php if ($pets): ?>
            <?php foreach($pets as $pet): ?>
            <div class="col-md-4 mb-4">
                <div class="card shadow">
                    <?php if (!empty($pet['image'])): ?>
                        <img src="uploads/pets/<?= htmlspecialchars($pet['image']) ?>" 
                             class="card-img-top" 
                             style="height: 250px; object-fit: cover;" 
                             alt="<?= htmlspecialchars($pet['name']) ?>">
                    <?php else: ?>
                        <div class="bg-light d-flex align-items-center justify-content-center" 
                             style="height: 250px;">
                            <span class="text-muted">No Image Available</span>
                        </div>
                    <?php endif; ?>
                    <div class="card-body">
                        <h5 class="card-title"><?= htmlspecialchars($pet['name']) ?></h5>
                        <p class="card-text">
                            <strong>Breed:</strong> <?= htmlspecialchars($pet['breed']) ?><br>
                            <strong>Age:</strong> <?= htmlspecialchars($pet['age']) ?> years<br>
                            <?php if (!empty($pet['description'])): ?>
                                <small class="text-muted"><?= htmlspecialchars(substr($pet['description'], 0, 100)) ?>...</small>
                            <?php endif; ?>
                        </p>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#loginModal">
                            Adopt Me
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12 text-center">
                <p class="text-muted">No pets available for adoption yet.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Login Modal -->
<div class="modal fade" id="loginModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Select Login Type</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <div class="d-grid gap-3">
                    <a href="login.php?role=admin" class="btn btn-danger">Admin Login</a>
                    <a href="login.php?role=giver" class="btn btn-warning">Pet Giver Login</a>
                    <a href="login.php?role=adopter" class="btn btn-success">Adopter Login</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
</body>
</html>
