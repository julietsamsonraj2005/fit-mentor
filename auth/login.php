<?php
require_once __DIR__ . '/../config/config.php';

if (is_logged_in()) {
    redirect('user/dashboard.php');
}

if (is_admin_logged_in()) {
    redirect('admin/dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize_input($_POST['email']);
    $password = $_POST['password'];
    $user_type = $_POST['user_type'];
    
    $errors = [];
    
    if (empty($email) || empty($password)) {
        $errors[] = "Please enter both username and password";
    }
    
    if (empty($errors)) {
        if ($user_type === 'admin') {
            $stmt = $pdo->prepare("SELECT id, email, password_hash, name FROM admins WHERE email = ?");
            $stmt->execute([$email]);
            $admin = $stmt->fetch();
            
            if ($admin && password_verify($password, $admin['password_hash'])) {
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_email'] = $admin['email'];
                redirect('admin/dashboard.php');
            } else {
                $errors[] = "Invalid admin credentials";
            }
        } else {
            $stmt = $pdo->prepare("SELECT id, email, password_hash, name, rank FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['email'] = $user['email'];
                // Attempt to record last login timestamp if the column exists
                try {
                    $upd = $pdo->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?");
                    $upd->execute([$user['id']]);
                } catch (Throwable $e) {
                    // Ignore if column doesn't exist
                }
                redirect('user/dashboard.php');
            } else {
                $errors[] = "Invalid username or password";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Fit-Mentor</title>
    <link rel="stylesheet" href="<?= rtrim($BASE_PATH, '/') ?>/assets/main.css">
</head>
<body>
    <div class="container">
        <?php include __DIR__ . '/../includes/header.php'; ?>
        
        <div class="form-wrapper">
            <div class="form-container">
                <div class="form-header">
                    <h2>Welcome Back</h2>
                    <p>Sign in to your Fit-Mentor account</p>
                </div>
                
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <?php foreach ($errors as $error): ?>
                            <p><?php echo $error; ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success">
                        <p><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></p>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="user_type">Login As</label>
                        <select class="form-control" id="user_type" name="user_type" required>
                            <option value="user">User</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                        <div class="form-error" id="email-error"></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                        <div class="form-error" id="password-error"></div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-block">Login</button>
                </form>
                
                    <p>Don't have an account? <a href="<?= $BASE_PATH ?>/auth/register.php">Register here</a></p>
                </div>
            </div>
        </div>
    </div>
<?php include __DIR__ . '/../includes/footer.php'; ?>