<?php
session_start();
include 'includes/db.php';

// Initialize all variables to prevent undefined variable errors
$username = '';
$password = '';
$error = '';

$role = $_GET['role'] ?? '';
$valid_roles = ['admin', 'giver', 'adopter'];

if (!in_array($role, $valid_roles)) {
    header('Location: index.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    if (!empty($username) && !empty($password)) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND role = ?");
            $stmt->execute([$username, $role]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['username'] = $user['username'];
                
                switch($user['role']) {
                    case 'admin':
                        header('Location: admin/dashboard.php');
                        break;
                    case 'adopter':
                        header('Location: adopter/dashboard.php');
                        break;
                    case 'giver':
                        header('Location: giver/dashboard.php');
                        break;
                }
                exit();
            } else {
                $error = "Invalid username or password!";
            }
        } catch(PDOException $e) {
            $error = "Database error occurred!";
        }
    } else {
        $error = "Please fill in all fields!";
    }
}
?>

<!DOCTYPE html>
<html>
<?php include 'includes/header.php'; ?>
<body class="bg-light">
<div class="container mt-5" style="max-width: 500px;">
    <div class="card shadow">
        <div class="card-body">
            <h2 class="text-center mb-4">
                <?= ucfirst($role) ?> Login
                <a href="index.php" class="btn btn-sm btn-outline-secondary float-end">Back</a>
            </h2>
            
            <?php if(!empty($error)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-control" 
                           value="<?= htmlspecialchars($username) ?>" required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                
                <button type="submit" class="btn btn-primary w-100 mb-3">Login</button>
                
                <?php if($role !== 'admin'): ?>
                <div class="text-center">
                    <span>Don't have an account? </span>
                    <a href="register.php?role=<?= htmlspecialchars($role) ?>" class="text-decoration-none">
                        Register as <?= ucfirst($role) ?>
                    </a>
                </div>
                <?php endif; ?>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form[method="POST"]');
    if (form) {
        form.addEventListener('submit', function(e) {
            const username = form.querySelector('input[name="username"]').value.trim();
            const password = form.querySelector('input[name="password"]').value.trim();

            if (!username || !password) {
                alert('Username and password are required!');
                e.preventDefault();
                return;
            }
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>

</body>
</html>
