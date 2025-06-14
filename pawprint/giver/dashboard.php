<?php
session_start();
if ($_SESSION['role'] !== 'giver') {
    header('Location: ../login.php');
    exit();
}
include '../includes/db.php';

$message = '';
$error = '';

// Handle search and sort parameters
$search = $_GET['search'] ?? '';
$sort_by = $_GET['sort_by'] ?? 'id';
$sort_order = $_GET['sort_order'] ?? 'DESC';

// Validate sort parameters
$valid_sorts = ['id', 'name', 'breed', 'age', 'status'];
$valid_orders = ['ASC', 'DESC'];
if (!in_array($sort_by, $valid_sorts)) $sort_by = 'id';
if (!in_array($sort_order, $valid_orders)) $sort_order = 'DESC';

// Handle pet deletion
if (isset($_GET['delete_pet'])) {
    $pet_id = $_GET['delete_pet'];
    $stmt = $pdo->prepare("DELETE FROM pets WHERE id = ? AND giver_id = ?");
    if ($stmt->execute([$pet_id, $_SESSION['user_id']])) {
        $message = "Pet deleted successfully!";
    } else {
        $error = "Error deleting pet.";
    }
}

// Handle pet submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_pet'])) {
    $name = $_POST['name'];
    $breed = $_POST['breed'];
    $age = $_POST['age'];
    $description = $_POST['description'];
    $image_name = '';
    
    // Handle image upload
    if (isset($_FILES['pet_image']) && $_FILES['pet_image']['error'] == 0) {
        $target_dir = "../uploads/pets/";
        
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES['pet_image']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = array('jpg', 'jpeg', 'png', 'gif');
        
        if (in_array($file_extension, $allowed_extensions)) {
            $image_name = uniqid() . '.' . $file_extension;
            $target_file = $target_dir . $image_name;
            
            if (!move_uploaded_file($_FILES['pet_image']['tmp_name'], $target_file)) {
                $error = "Error uploading image.";
            }
        } else {
            $error = "Only JPG, JPEG, PNG & GIF files are allowed.";
        }
    }
    
    if (empty($error)) {
        $stmt = $pdo->prepare("INSERT INTO pets (name, breed, age, description, image, giver_id) 
                             VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$name, $breed, $age, $description, $image_name, $_SESSION['user_id']])) {
            $message = "Pet added successfully!";
        } else {
            $error = "Error adding pet to database.";
        }
    }
}

include '../includes/header.php';
?>

<div class="container mt-4">
    <h2 class="mb-4">Pet Giver Dashboard</h2>
    
    <?php if (!empty($message)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <div class="row">
        <!-- Add Pet Form -->
        <div class="col-md-6">
            <div class="card shadow mb-4">
                <div class="card-header">
                    <h5>Add New Pet</h5>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="form-label">Pet Name</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Breed</label>
                            <input type="text" name="breed" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Age</label>
                            <input type="number" name="age" class="form-control" min="0" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Pet Image</label>
                            <input type="file" name="pet_image" class="form-control" accept="image/*" required>
                        </div>
                        <button type="submit" name="submit_pet" class="btn btn-primary w-100">Add Pet</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- My Pets with Search & Sort -->
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header">
                    <h5>My Pets</h5>
                </div>
                <div class="card-body">
                    <!-- Search and Sort Controls -->
                    <form method="GET" class="mb-3">
                        <div class="row g-2">
                            <div class="col-md-6">
                                <input type="text" name="search" class="form-control form-control-sm" 
                                       value="<?= htmlspecialchars($search) ?>" 
                                       placeholder="Search pets...">
                            </div>
                            <div class="col-md-3">
                                <select name="sort_by" class="form-select form-select-sm">
                                    <option value="id" <?= $sort_by == 'id' ? 'selected' : '' ?>>Date Added</option>
                                    <option value="name" <?= $sort_by == 'name' ? 'selected' : '' ?>>Name</option>
                                    <option value="breed" <?= $sort_by == 'breed' ? 'selected' : '' ?>>Breed</option>
                                    <option value="age" <?= $sort_by == 'age' ? 'selected' : '' ?>>Age</option>
                                    <option value="status" <?= $sort_by == 'status' ? 'selected' : '' ?>>Status</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-primary btn-sm w-100">Filter</button>
                            </div>
                        </div>
                    </form>
                    
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                        const petForm = document.querySelector('form[method="POST"]');
                         if (petForm) {
                            petForm.addEventListener('submit', function(e) {
                             const name = petForm.querySelector('input[name="name"]').value.trim();
                             const breed = petForm.querySelector('input[name="breed"]').value.trim();
                             const age = petForm.querySelector('input[name="age"]').value.trim();
                             const image = petForm.querySelector('input[name="pet_image"]').files[0];

                             if (!name || !breed || !age || !image) {
                                 alert('All fields are required!');
                                 e.preventDefault();
                                 return;
                             }

                             if (isNaN(age) || age < 0) {
                                 alert('Age must be a positive number!');
                                 e.preventDefault();
                                 return;
                             }
                            });
                        }
                        });
                    </script>

                    <?php
                    $where_clause = "giver_id = ?";
                    $params = [$_SESSION['user_id']];
                    
                    if (!empty($search)) {
                        $where_clause .= " AND (name LIKE ? OR breed LIKE ? OR description LIKE ?)";
                        $search_term = "%$search%";
                        $params[] = $search_term;
                        $params[] = $search_term;
                        $params[] = $search_term;
                    }
                    
                    $sql = "SELECT * FROM pets WHERE $where_clause ORDER BY $sort_by $sort_order";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);
                    $pets = $stmt->fetchAll();
                    
                    if ($pets): ?>
                        <?php foreach ($pets as $pet): ?>
                            <div class="card mb-3">
                                <div class="row g-0">
                                    <div class="col-md-4">
                                        <?php if (!empty($pet['image'])): ?>
                                            <img src="../uploads/pets/<?= htmlspecialchars($pet['image']) ?>" 
                                                 class="img-fluid rounded-start" 
                                                 style="height: 120px; object-fit: cover; width: 100%;">
                                        <?php else: ?>
                                            <div class="bg-light d-flex align-items-center justify-content-center rounded-start" 
                                                 style="height: 120px;">
                                                <span class="text-muted">No Image</span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-8">
                                        <div class="card-body p-2">
                                            <h6 class="card-title"><?= htmlspecialchars($pet['name']) ?></h6>
                                            <p class="card-text small">
                                                Breed: <?= htmlspecialchars($pet['breed']) ?><br>
                                                Age: <?= htmlspecialchars($pet['age']) ?> years<br>
                                                Status: <span class="badge bg-<?= $pet['status'] == 'available' ? 'success' : 'secondary' ?>">
                                                    <?= ucfirst($pet['status']) ?>
                                                </span>
                                            </p>
                                            <div class="btn-group btn-group-sm">
                                                <a href="edit_pet.php?id=<?= $pet['id'] ?>" class="btn btn-outline-primary">Edit</a>
                                                <a href="?delete_pet=<?= $pet['id'] ?>&search=<?= urlencode($search) ?>&sort_by=<?= $sort_by ?>&sort_order=<?= $sort_order ?>" class="btn btn-outline-danger" 
                                                   onclick="return confirm('Are you sure you want to delete this pet?')">Delete</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted">No pets found matching your criteria.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
