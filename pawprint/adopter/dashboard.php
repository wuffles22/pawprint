<?php
session_start();
if ($_SESSION['role'] !== 'adopter') {
    header('Location: ../login.php');
    exit();
}
include '../includes/db.php';

// Handle search and sort parameters
$search = $_GET['search'] ?? '';
$sort_by = $_GET['sort_by'] ?? 'id';
$sort_order = $_GET['sort_order'] ?? 'DESC';

// Validate sort parameters
$valid_sorts = ['id', 'name', 'breed', 'age'];
$valid_orders = ['ASC', 'DESC'];
if (!in_array($sort_by, $valid_sorts)) $sort_by = 'id';
if (!in_array($sort_order, $valid_orders)) $sort_order = 'DESC';

// Handle adoption application
if (isset($_GET['adopt'])) {
    $pet_id = $_GET['adopt'];
    // Check if already applied
    $stmt = $pdo->prepare("SELECT id FROM applications WHERE pet_id = ? AND adopter_id = ?");
    $stmt->execute([$pet_id, $_SESSION['user_id']]);
    if (!$stmt->fetch()) {
        $stmt = $pdo->prepare("INSERT INTO applications (pet_id, adopter_id) VALUES (?, ?)");
        $stmt->execute([$pet_id, $_SESSION['user_id']]);
        $message = "Application submitted successfully!";
    } else {
        $error = "You have already applied for this pet.";
    }
}

include '../includes/header.php';
?>

<div class="container mt-4">
    <h2 class="mb-4">Adopter Dashboard</h2>
    
    <?php if (isset($message)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <div class="row">
        <!-- Available Pets with Search & Sort -->
        <div class="col-md-8">
            <div class="card shadow mb-4">
                <div class="card-header">
                    <h5>Available Pets</h5>
                </div>
                <div class="card-body">
                    <!-- Search and Sort Controls -->
                    <form method="GET" class="mb-3">
                        <div class="row g-3">
                            <div class="col-md-5">
                                <label class="form-label">Search Pets</label>
                                <input type="text" name="search" class="form-control" 
                                       value="<?= htmlspecialchars($search) ?>" 
                                       placeholder="Search by name, breed, or description">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Sort By</label>
                                <select name="sort_by" class="form-select">
                                    <option value="id" <?= $sort_by == 'id' ? 'selected' : '' ?>>Date Added</option>
                                    <option value="name" <?= $sort_by == 'name' ? 'selected' : '' ?>>Name</option>
                                    <option value="breed" <?= $sort_by == 'breed' ? 'selected' : '' ?>>Breed</option>
                                    <option value="age" <?= $sort_by == 'age' ? 'selected' : '' ?>>Age</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Order</label>
                                <select name="sort_order" class="form-select">
                                    <option value="ASC" <?= $sort_order == 'ASC' ? 'selected' : '' ?>>A-Z / Low-High</option>
                                    <option value="DESC" <?= $sort_order == 'DESC' ? 'selected' : '' ?>>Z-A / High-Low</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary d-block w-100">Search</button>
                            </div>
                        </div>
                    </form>
                    
                    <div class="row">
                        <?php
                        $where_clause = "status = 'available'";
                        $params = [];
                        
                        if (!empty($search)) {
                            $where_clause .= " AND (name LIKE ? OR breed LIKE ? OR description LIKE ?)";
                            $search_term = "%$search%";
                            $params = [$search_term, $search_term, $search_term];
                        }
                        
                        $sql = "SELECT * FROM pets WHERE $where_clause ORDER BY $sort_by $sort_order";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute($params);
                        
                        while ($row = $stmt->fetch()): ?>
                            <div class="col-md-6 mb-3">
                                <div class="card h-100">
                                    <?php if (!empty($row['image'])): ?>
                                        <img src="../uploads/pets/<?= htmlspecialchars($row['image']) ?>" 
                                             class="card-img-top" style="height: 200px; object-fit: cover;">
                                    <?php endif; ?>
                                    <div class="card-body d-flex flex-column">
                                        <h5><?= htmlspecialchars($row['name']) ?></h5>
                                        <p class="card-text flex-grow-1">
                                            <strong>Breed:</strong> <?= htmlspecialchars($row['breed']) ?><br>
                                            <strong>Age:</strong> <?= htmlspecialchars($row['age']) ?> years<br>
                                            <?php if (!empty($row['description'])): ?>
                                                <small class="text-muted"><?= htmlspecialchars(substr($row['description'], 0, 100)) ?>...</small>
                                            <?php endif; ?>
                                        </p>
                                        <a href="?adopt=<?= $row['id'] ?>&search=<?= urlencode($search) ?>&sort_by=<?= $sort_by ?>&sort_order=<?= $sort_order ?>" 
                                           class="btn btn-success mt-auto">Apply to Adopt</a>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-md-4">
            <!-- My Applications -->
            <div class="card shadow mb-4">
                <div class="card-header">
                    <h5>My Applications</h5>
                </div>
                <div class="card-body">
                    <?php
                    $stmt = $pdo->prepare("SELECT p.name, p.breed, a.status, a.application_date 
                                         FROM applications a
                                         JOIN pets p ON a.pet_id = p.id
                                         WHERE a.adopter_id = ?
                                         ORDER BY a.application_date DESC");
                    $stmt->execute([$_SESSION['user_id']]);
                    $applications = $stmt->fetchAll();
                    
                    if ($applications): ?>
                        <?php foreach ($applications as $row): ?>
                            <div class="mb-3 p-2 border rounded">
                                <strong><?= htmlspecialchars($row['name']) ?></strong><br>
                                <small class="text-muted"><?= htmlspecialchars($row['breed']) ?></small><br>
                                <span class="badge bg-<?= $row['status'] == 'approved' ? 'success' : ($row['status'] == 'rejected' ? 'danger' : 'warning') ?>">
                                    <?= ucfirst($row['status']) ?>
                                </span>
                                <small class="text-muted d-block"><?= date('M j, Y', strtotime($row['application_date'])) ?></small>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted">No applications yet.</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Profile Management -->
            <div class="card shadow">
                <div class="card-header">
                    <h5>My Profile</h5>
                </div>
                <div class="card-body">
                    <a href="edit_profile.php" class="btn btn-outline-primary w-100">Edit My Profile</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
