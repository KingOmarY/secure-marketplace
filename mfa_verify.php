<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['pending_user_id'])) {
    header("Location: login.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recaptcha_response = $_POST['g-recaptcha-response'] ?? '';
    
    if (empty($recaptcha_response)) {
        $error = "Please check the 'I'm not a robot' box";
    } else {
        // Verify with Google using YOUR secret key
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://www.google.com/recaptcha/api/siteverify');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'secret' => '6LcSLx0tAAAAAB5RvChnS7Up2tjmDzFR_-XPj3ZW',
            'response' => $recaptcha_response
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);
        
        $data = json_decode($result, true);
        
        if ($data['success'] === true) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $_SESSION['pending_user_id'];
            $_SESSION['role'] = $_SESSION['pending_role'] ?? 'user';
            unset($_SESSION['pending_user_id'], $_SESSION['pending_role']);
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "reCAPTCHA failed. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>MFA - UTHM Marketplace</title>
    <style>
        body { font-family: Arial; background: white; margin: 0; }
        .navbar { background: #003366; padding: 15px; color: white; }
        .navbar a { color: white; text-decoration: none; margin-right: 15px; }
        .container { max-width: 500px; margin: 50px auto; padding: 20px; }
        .card { border: 1px solid #ccc; padding: 30px; border-radius: 5px; text-align: center; }
        .error { color: red; background: #ffe5e5; padding: 10px; margin-bottom: 20px; border-radius: 5px; }
        button { background: #003366; color: white; padding: 10px 20px; border: none; cursor: pointer; margin-top: 20px; border-radius: 5px; }
        .footer { text-align: center; padding: 20px; background: #f0f0f0; margin-top: 50px; }
        .g-recaptcha { margin: 20px 0; display: flex; justify-content: center; }
    </style>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>
<body>
    <div class="navbar">
        <a href="login.php">UTHM Marketplace</a>
        <a href="login.php">Login</a>
        <a href="register.php">Register</a>
    </div>
    <div class="container">
        <div class="card">
            <h2>Multi-Factor Authentication</h2>
            <p>Complete the reCAPTCHA below to verify you are human.</p>
            
            <?php if ($error): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="g-recaptcha" data-sitekey="6LcSLx0tAAAAAMPzU4N6H8KsWFb7B7SRiI4uy0Q-"></div>
                <button type="submit">Verify & Login</button>
            </form>
        </div>
    </div>
    <div class="footer">
        <p>UTHM Secure Marketplace | Web Security Project</p>
    </div>
</body>
</html>