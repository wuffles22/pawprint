<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pet Adoption System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= isset($_SESSION['role']) ? '../' : '' ?>css/style.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="/pawprint/index.php">Pet Adoption</a>
        <div class="d-flex">
            <?php if (isset($_SESSION['role'])): ?>
                <span class="navbar-text me-3">Welcome, <?= htmlspecialchars($_SESSION['username']) ?>!</span>
                <!-- Dashboard Button -->
                <a href="<?= $_SESSION['role'] ?>/dashboard.php" class="btn btn-outline-light me-2">Dashboard</a>
                <a href="/pawprint/logout.php" class="btn btn-light">Logout</a>
            <?php else: ?>
                <button class="btn btn-light" data-bs-toggle="modal" data-bs-target="#loginModal">Login/Register</button>
            <?php endif; ?>
        </div>
    </div>
</nav>
