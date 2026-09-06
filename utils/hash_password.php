<?php
// Simple utility to generate and verify password hashes
// Usage (CLI):
//   php utils/hash_password.php "your_password" [cost]
// Usage (Web):
//   Open this file in the browser and use the form.

function generate_hash(string $password, int $cost = 12): string {
    $cost = max(4, min($cost, 31)); // clamp cost to safe range for bcrypt/argon2 cost-like values

    // Prefer Argon2id if available, else use PASSWORD_DEFAULT
    if (defined('PASSWORD_ARGON2ID')) {
        $options = [
            'memory_cost' => 1<<17, // 128 MB
            'time_cost'   => 4,
            'threads'     => 2,
        ];
        $hash = password_hash($password, PASSWORD_ARGON2ID, $options);
    } else {
        $options = ['cost' => $cost];
        $hash = password_hash($password, PASSWORD_DEFAULT, $options);
    }

    if ($hash === false) {
        throw new RuntimeException('Failed to generate password hash');
    }

    return $hash;
}

function handle_cli(array $argv): void {
    $password = $argv[1] ?? null;
    $cost     = isset($argv[2]) ? (int)$argv[2] : 12;

    if (!$password) {
        fwrite(STDERR, "Usage: php utils/hash_password.php \"your_password\" [cost]\n");
        exit(1);
    }

    $hash = generate_hash($password, $cost);
    echo "Password: " . str_repeat('*', max(1, strlen($password))) . PHP_EOL;
    echo "Hash:     $hash" . PHP_EOL;
}

function handle_web(): void {
    $password = $_POST['password'] ?? '';
    $cost     = isset($_POST['cost']) ? (int)$_POST['cost'] : 12;
    $hash     = null;
    $verified = null;

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $password !== '') {
        try {
            $hash = generate_hash($password, $cost);
            $verified = password_verify($password, $hash);
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }
    }

    header('Content-Type: text/html; charset=utf-8');
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Password Hash Utility</title>
  <link rel="stylesheet" href="../assets/main.css" />
  <style>
    .hash-tool { max-width: 700px; margin: 100px auto 40px; background: #fff; border: 1px solid #e9ecef; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,.05); padding: 24px; }
    .hash-tool h1 { margin-bottom: 12px; }
    .hash-tool .row { display: grid; grid-template-columns: 1fr 140px; gap: 12px; }
    .hash-tool label { font-weight: 600; }
    .hash-tool input[type="text"], .hash-tool input[type="password"], .hash-tool input[type="number"] { width: 100%; padding: 10px 12px; border: 2px solid #e9ecef; border-radius: 8px; }
    .hash-tool pre { background: #0b1620; color: #e6edf3; padding: 12px; border-radius: 8px; overflow: auto; }
    .muted { color: #6c757d; font-size: 0.9rem; }
  </style>
</head>
<body>
  <div class="hash-tool">
    <h1>Password Hash Generator</h1>
    <p class="muted">Enter a password to generate a secure hash. This tool prefers Argon2id when available, otherwise it falls back to PHP's default (e.g., bcrypt).</p>

    <?php if (!empty($error)): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post">
      <div class="form-group">
        <label for="password">Password</label>
        <input id="password" name="password" type="password" placeholder="Enter password" required />
      </div>

      <div class="form-group">
        <label for="cost">Cost (for bcrypt fallback)</label>
        <input id="cost" name="cost" type="number" min="4" max="31" value="<?= (int)$cost ?>" />
        <div class="muted">Ignored when Argon2id is used. For bcrypt, higher cost increases work factor.</div>
      </div>

      <div class="form-actions">
        <button type="submit" class="btn btn-primary">Generate Hash</button>
      </div>
    </form>

    <?php if ($hash): ?>
      <div class="mt-2">
        <label>Generated Hash</label>
        <pre><?= htmlspecialchars($hash) ?></pre>
        <div class="muted">Verification result: <?= $verified ? 'valid ✔' : 'failed ✖' ?></div>
      </div>
    <?php endif; ?>
  </div>
</body>
</html>
<?php
}

// Router: choose CLI or Web
if (php_sapi_name() === 'cli') {
    handle_cli($argv);
} else {
    handle_web();
}
