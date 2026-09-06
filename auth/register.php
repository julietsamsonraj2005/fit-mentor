<?php
require_once __DIR__ . '/../config/config.php';

if (is_logged_in()) {
    redirect('/user/dashboard.php');
}

// Ensure required columns exist (safe on MySQL 8+). Ignored if already exist.
try {
    $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS age INT NULL");
    $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS gender VARCHAR(16) NULL");
    $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS height_cm INT NULL");
    $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS weight_kg INT NULL");
    $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS goal VARCHAR(50) NULL");
} catch (Throwable $e) {
    // Non-fatal: continue without interrupting registration
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize_input($_POST['name']);
    $email = sanitize_input($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // New fields
    $age = isset($_POST['age']) ? (int)$_POST['age'] : null;
    $gender = isset($_POST['gender']) ? strtolower(trim($_POST['gender'])) : null;
    $height_cm = isset($_POST['height_cm']) ? (int)$_POST['height_cm'] : null;
    $weight_kg = isset($_POST['weight_kg']) ? (int)$_POST['weight_kg'] : null;
    $goal = isset($_POST['goal']) ? trim($_POST['goal']) : null;
    
    $errors = [];
    
    // Validation
    if (empty($name) || empty($email) || empty($password)) {
        $errors[] = "All fields are required";
    }
    if (!empty($name) && !is_valid_name($name)) {
        $errors[] = "Name may only contain letters A-Z and a-z";
    }
    
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }
    
    if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long";
    }
    
    // Basic validation for new fields (optional fields allowed)
    if ($age !== null && ($age < 10 || $age > 120)) {
        $errors[] = "Please enter a valid age";
    }
    $allowed_genders = ['male','female','other',''];
    if ($gender !== null && !in_array($gender, $allowed_genders, true)) {
        $errors[] = "Invalid gender";
    }
    if ($height_cm !== null && $height_cm <= 0) {
        $errors[] = "Height must be positive";
    }
    if ($weight_kg !== null && $weight_kg <= 0) {
        $errors[] = "Weight must be positive";
    }
    if ($goal !== null && !in_array($goal, ['build muscle','loss weight','keep fit',''], true)) {
        $errors[] = "Invalid goal";
    }

    // Check if user exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $errors[] = "Email already exists";
    }
    
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        // Normalize empty optional values to null
        $genderVal = ($gender === '' ? null : $gender);
        $goalVal = ($goal === '' ? null : $goal);

        $stmt = $pdo->prepare("INSERT INTO users
            (name, email, password_hash, age, gender, height_cm, weight_kg, goal)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$name, $email, $hashed_password, $age ?: null, $genderVal, $height_cm ?: null, $weight_kg ?: null, $goalVal])) {
            $_SESSION['success'] = "Registration successful! Please login.";
            redirect('/auth/login.php');
        } else {
            $errors[] = "Registration failed. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Fit-Mentor</title>
    <link rel="stylesheet" href="<?= $BASE_PATH ?>/assets/main.css">
</head>
<body>
    <div class="container">
        <?php include __DIR__ . '/../includes/header.php'; ?>
        
        <div class="form-wrapper">
            <div class="form-container">
                <div class="form-header">
                    <h2>Create Your Account</h2>
                    <p>Join thousands of users transforming their fitness journey</p>
                </div>
                
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <?php foreach ($errors as $error): ?>
                            <p><?php echo $error; ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="" onsubmit="return validateForm(this)">
                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <input type="text" class="form-control" id="name" name="name" pattern="^[A-Za-z]+$" title="Only letters A-Z and a-z are allowed" required>
                        <div class="form-error" id="name-error"></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                        <div class="form-error" id="email-error"></div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="age">Age</label>
                            <input type="number" class="form-control" id="age" name="age" min="10" max="120" placeholder="e.g., 28">
                        </div>
                        <div class="form-group">
                            <label for="gender">Gender</label>
                            <select class="form-control" id="gender" name="gender">
                                <option value="">Select</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="height_cm">Height (cm)</label>
                            <input type="number" class="form-control" id="height_cm" name="height_cm" min="50" max="250" placeholder="e.g., 175">
                        </div>
                        <div class="form-group">
                            <label for="weight_kg">Weight (kg)</label>
                            <input type="number" class="form-control" id="weight_kg" name="weight_kg" min="20" max="400" placeholder="e.g., 72">
                        </div>
                    </div>

                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                        <div class="form-error" id="password-error"></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        <div class="form-error" id="confirm_password-error"></div>
                    </div>


                    <div class="form-group">
                        <label for="goal">Goal</label>
                        <select class="form-control" id="goal" name="goal">
                            <option value="">Select goal</option>
                            <option value="build muscle">Build muscle</option>
                            <option value="loss weight">Loss weight</option>
                            <option value="keep fit">Keep fit</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-block">Create Account</button>
                </form>
                
                <div class="form-footer">
                    <p>Already have an account? <a href="<?= $BASE_PATH ?>/auth/login.php">Login here</a></p>
                </div>
            </div>
        </div>
    </div>
    
    <?php include __DIR__ . '/../includes/footer.php'; ?>