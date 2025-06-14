<?php
session_start();
if ($_SESSION['role'] !== 'adopter') {
    header('Location: ../login.php');
    exit();
}
include '../includes/db.php';

$message = '';
$error = '';

// Get user details
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $new_password = trim($_POST['new_password']);
    
    // Check if username is taken by another user
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
    $stmt->execute([$username, $_SESSION['user_id']]);
    if ($stmt->fetch()) {
        $error = "Username already taken by another user.";
    } else {
        // Update profile
        if (!empty($new_password)) {
            // Update with new password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, password = ? WHERE id = ?");
            $stmt->execute([$username, $email, $hashed_password, $_SESSION['user_id']]);
        } else {
            // Update without changing password
            $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
            $stmt->execute([$username, $email, $_SESSION['user_id']]);
        }
        
        $_SESSION['username'] = $username;
        $message = "Profile updated successfully!";
        
        // Refresh user data
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
    }
}

include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header">
                    <h5>Edit My Profile
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
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" name="username" class="form-control" 
                                   value="<?= htmlspecialchars($user['username']) ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" 
                                   value="<?= htmlspecialchars($user['email']) ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">New Password (Leave empty to keep current)</label>
                            <input type="password" name="new_password" class="form-control">
                            <small class="form-text text-muted">Only enter a password if you want to change it</small>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100">Update Profile</button>
                    </form>

                    <script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form[method="POST"]');
    if (form) {
        form.addEventListener('submit', function(e) {
            const username = form.querySelector('input[name="username"]').value.trim();
            const email = form.querySelector('input[name="email"]').value.trim();
            const newPassword = form.querySelector('input[name="new_password"]').value.trim();

            if (!username || !email) {
                alert('Username and email are required!');
                e.preventDefault();
                return;
            }

            if (newPassword && newPassword.length < 6) {
                alert('Password must be at least 6 characters!');
                e.preventDefault();
                return;
            }
        });
    }
});
</script>

                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
