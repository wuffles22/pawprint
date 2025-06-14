<?php
session_start();
if ($_SESSION['role'] !== 'giver') {
    header('Location: ../login.php');
    exit();
}
include '../includes/db.php';

$pet_id = $_GET['id'] ?? 0;
$message = '';
$error = '';

// Get pet details
$stmt = $pdo->prepare("SELECT * FROM pets WHERE id = ? AND giver_id = ?");
$stmt->execute([$pet_id, $_SESSION['user_id']]);
$pet = $stmt->fetch();

if (!$pet) {
    header('Location: dashboard.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $breed = $_POST['breed'];
    $age = $_POST['age'];
    $description = $_POST['description'];
    $image_name = $pet['image']; // Keep existing image by default
    
    // Handle new image upload
    if (isset($_FILES['pet_image']) && $_FILES['pet_image']['error'] == 0) {
        $target_dir = "../uploads/pets/";
        $file_extension = strtolower(pathinfo($_FILES['pet_image']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = array('jpg', 'jpeg', 'png', 'gif');
        
        if (in_array($file_extension, $allowed_extensions)) {
            $image_name = uniqid() . '.' . $file_extension;
            $target_file = $target_dir . $image_name;
            
            if (move_uploaded_file($_FILES['pet_image']['tmp_name'], $target_file)) {
                // Delete old image if it exists
                if (!empty($pet['image']) && file_exists($target_dir . $pet['image'])) {
                    unlink($target_dir . $pet['image']);
                }
            } else {
                $error = "Error uploading new image.";
            }
        } else {
            $error = "Only JPG, JPEG, PNG & GIF files are allowed.";
        }
    }
    
    if (empty($error)) {
        $stmt = $pdo->prepare("UPDATE pets SET name = ?, breed = ?, age = ?, description = ?, image = ? WHERE id = ? AND giver_id = ?");
        if ($stmt->execute([$name, $breed, $age, $description, $image_name, $pet_id, $_SESSION['user_id']])) {
            $message = "Pet updated successfully!";
            // Refresh pet data
            $stmt = $pdo->prepare("SELECT * FROM pets WHERE id = ? AND giver_id = ?");
            $stmt->execute([$pet_id, $_SESSION['user_id']]);
            $pet = $stmt->fetch();
        } else {
            $error = "Error updating pet.";
        }
    }
}

include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header">
                    <h5>Edit Pet: <?= htmlspecialchars($pet['name']) ?>
                        <a href="dashboard.php" class="btn btn-sm btn-outline-secondary float-end">Back</a>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($message)): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Pet Name</label>
                                    <input type="text" name="name" class="form-control" 
                                           value="<?= htmlspecialchars($pet['name']) ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Breed</label>
                                    <input type="text" name="breed" class="form-control" 
                                           value="<?= htmlspecialchars($pet['breed']) ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Age</label>
                                    <input type="number" name="age" class="form-control" 
                                           value="<?= htmlspecialchars($pet['age']) ?>" min="0" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Description</label>
                                    <textarea name="description" class="form-control" rows="4"><?= htmlspecialchars($pet['description']) ?></textarea>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Current Image</label>
                                    <?php if (!empty($pet['image'])): ?>
                                        <div class="mb-2">
                                            <img src="../uploads/pets/<?= htmlspecialchars($pet['image']) ?>" 
                                                 class="img-fluid rounded" style="max-height: 200px;">
                                        </div>
                                    <?php else: ?>
                                        <p class="text-muted">No image uploaded</p>
                                    <?php endif; ?>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Upload New Image (Optional)</label>
                                    <input type="file" name="pet_image" class="form-control" accept="image/*">
                                    <small class="form-text text-muted">Leave empty to keep current image</small>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">Update Pet</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
