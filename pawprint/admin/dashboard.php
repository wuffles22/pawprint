<?php
session_start();
if ($_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}
include '../includes/db.php';

// Handle search and sort parameters
$search = $_GET['search'] ?? '';
$sort_by = $_GET['sort_by'] ?? 'id';
$sort_order = $_GET['sort_order'] ?? 'DESC';

// Validate sort parameters
$valid_sorts = ['id', 'name', 'breed', 'age', 'status', 'application_date'];
$valid_orders = ['ASC', 'DESC'];
if (!in_array($sort_by, $valid_sorts)) $sort_by = 'id';
if (!in_array($sort_order, $valid_orders)) $sort_order = 'DESC';

// Handle actions
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    
    if ($action == 'approve_application' && isset($_GET['id'])) {
        $stmt = $pdo->prepare("UPDATE applications SET status = 'approved' WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        
        $stmt = $pdo->prepare("UPDATE pets SET status = 'adopted' WHERE id = (SELECT pet_id FROM applications WHERE id = ?)");
        $stmt->execute([$_GET['id']]);
    }
    
    if ($action == 'reject_application' && isset($_GET['id'])) {
        $stmt = $pdo->prepare("UPDATE applications SET status = 'rejected' WHERE id = ?");
        $stmt->execute([$_GET['id']]);
    }
    
    if ($action == 'delete_application' && isset($_GET['id'])) {
        $stmt = $pdo->prepare("DELETE FROM applications WHERE id = ?");
        $stmt->execute([$_GET['id']]);
    }
    
    if ($action == 'change_pet_status' && isset($_GET['pet_id']) && isset($_GET['status'])) {
        $stmt = $pdo->prepare("UPDATE pets SET status = ? WHERE id = ?");
        $stmt->execute([$_GET['status'], $_GET['pet_id']]);
    }
    
    if ($action == 'delete_pet' && isset($_GET['pet_id'])) {
        $stmt = $pdo->prepare("DELETE FROM pets WHERE id = ?");
        $stmt->execute([$_GET['pet_id']]);
    }
}

include '../includes/header.php';
?>

<div class="container mt-4">
    <h2 class="mb-4">Admin Dashboard</h2>
    
    <!-- Search and Sort Controls -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Search Applications/Pets</label>
                    <input type="text" name="search" class="form-control" 
                           value="<?= htmlspecialchars($search) ?>" 
                           placeholder="Search by pet name, breed, or adopter name">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Sort By</label>
                    <select name="sort_by" class="form-select">
                        <option value="id" <?= $sort_by == 'id' ? 'selected' : '' ?>>Date</option>
                        <option value="name" <?= $sort_by == 'name' ? 'selected' : '' ?>>Pet Name</option>
                        <option value="breed" <?= $sort_by == 'breed' ? 'selected' : '' ?>>Breed</option>
                        <option value="status" <?= $sort_by == 'status' ? 'selected' : '' ?>>Status</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Order</label>
                    <select name="sort_order" class="form-select">
                        <option value="ASC" <?= $sort_order == 'ASC' ? 'selected' : '' ?>>Ascending</option>
                        <option value="DESC" <?= $sort_order == 'DESC' ? 'selected' : '' ?>>Descending</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary d-block">Search & Sort</button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="row">
        <!-- Applications Management -->
        <div class="col-md-6">
            <div class="card shadow mb-4">
                <div class="card-header">
                    <h5>Adoption Applications</h5>
                </div>
                <div class="card-body">
                    <?php
                    $where_clause = "1=1";
                    $params = [];
                    
                    if (!empty($search)) {
                        $where_clause .= " AND (p.name LIKE ? OR p.breed LIKE ? OR u.username LIKE ?)";
                        $search_term = "%$search%";
                        $params = [$search_term, $search_term, $search_term];
                    }
                    
                    $sql = "SELECT a.*, p.name AS pet_name, p.breed, u.username, u.email 
                           FROM applications a
                           JOIN pets p ON a.pet_id = p.id
                           JOIN users u ON a.adopter_id = u.id
                           WHERE $where_clause
                           ORDER BY a.$sort_by $sort_order";
                    
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);
                    
                    while ($app = $stmt->fetch()): ?>
                        <div class="card mb-2">
                            <div class="card-body p-2">
                                <h6><?= htmlspecialchars($app['pet_name']) ?> (<?= htmlspecialchars($app['breed']) ?>)</h6>
                                <p class="small mb-1">
                                    Applicant: <?= htmlspecialchars($app['username']) ?><br>
                                    Email: <?= htmlspecialchars($app['email']) ?><br>
                                    Date: <?= date('M j, Y', strtotime($app['application_date'])) ?><br>
                                    Status: <span class="badge bg-<?= $app['status'] == 'approved' ? 'success' : ($app['status'] == 'rejected' ? 'danger' : 'warning') ?>">
                                        <?= ucfirst($app['status']) ?>
                                    </span>
                                </p>
                                <div class="btn-group btn-group-sm">
                                    <?php if ($app['status'] == 'pending'): ?>
                                        <a href="?action=approve_application&id=<?= $app['id'] ?>&search=<?= urlencode($search) ?>&sort_by=<?= $sort_by ?>&sort_order=<?= $sort_order ?>" class="btn btn-success">Approve</a>
                                        <a href="?action=reject_application&id=<?= $app['id'] ?>&search=<?= urlencode($search) ?>&sort_by=<?= $sort_by ?>&sort_order=<?= $sort_order ?>" class="btn btn-danger">Reject</a>
                                    <?php endif; ?>
                                    <a href="?action=delete_application&id=<?= $app['id'] ?>&search=<?= urlencode($search) ?>&sort_by=<?= $sort_by ?>&sort_order=<?= $sort_order ?>" class="btn btn-outline-danger" 
                                       onclick="return confirm('Delete this application?')">Delete</a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>

        <!-- Pet Management -->
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header">
                    <h5>Pet Management</h5>
                </div>
                <div class="card-body">
                    <?php
                    $where_clause = "1=1";
                    $params = [];
                    
                    if (!empty($search)) {
                        $where_clause .= " AND (p.name LIKE ? OR p.breed LIKE ? OR u.username LIKE ?)";
                        $search_term = "%$search%";
                        $params = [$search_term, $search_term, $search_term];
                    }
                    
                    $sql = "SELECT p.*, u.username as giver_name 
                           FROM pets p 
                           JOIN users u ON p.giver_id = u.id 
                           WHERE $where_clause
                           ORDER BY p.$sort_by $sort_order";
                    
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);
                    
                    while ($pet = $stmt->fetch()): ?>
                        <div class="card mb-2">
                            <div class="card-body p-2">
                                <h6><?= htmlspecialchars($pet['name']) ?> (<?= htmlspecialchars($pet['breed']) ?>)</h6>
                                <p class="small mb-1">
                                    Age: <?= htmlspecialchars($pet['age']) ?> years<br>
                                    Giver: <?= htmlspecialchars($pet['giver_name']) ?><br>
                                    Status: <span class="badge bg-<?= $pet['status'] == 'available' ? 'success' : 'secondary' ?>">
                                        <?= ucfirst($pet['status']) ?>
                                    </span>
                                </p>
                                <div class="btn-group btn-group-sm">
                                    <select onchange="changePetStatus(<?= $pet['id'] ?>, this.value)" class="form-select form-select-sm">
                                        <option value="available" <?= $pet['status'] == 'available' ? 'selected' : '' ?>>Available</option>
                                        <option value="adopted" <?= $pet['status'] == 'adopted' ? 'selected' : '' ?>>Adopted</option>
                                    </select>
                                    <a href="edit_pet.php?id=<?= $pet['id'] ?>" class="btn btn-outline-primary">Edit</a>
                                    <a href="?action=delete_pet&pet_id=<?= $pet['id'] ?>&search=<?= urlencode($search) ?>&sort_by=<?= $sort_by ?>&sort_order=<?= $sort_order ?>" class="btn btn-outline-danger" 
                                       onclick="return confirm('Delete this pet?')">Delete</a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function changePetStatus(petId, status) {
    const urlParams = new URLSearchParams(window.location.search);
    window.location.href = '?action=change_pet_status&pet_id=' + petId + '&status=' + status + 
                          '&search=' + encodeURIComponent(urlParams.get('search') || '') +
                          '&sort_by=' + encodeURIComponent(urlParams.get('sort_by') || 'id') +
                          '&sort_order=' + encodeURIComponent(urlParams.get('sort_order') || 'DESC');
}
</script>

<?php include '../includes/footer.php'; ?>
