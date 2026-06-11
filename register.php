<?php
session_start();
require_once 'config/database.php';
require_once 'includes/security_functions.php';

$errors = [];
$form_data = [];

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id        = sanitizeInput($_POST['user_id'] ?? '');
    $full_name      = sanitizeInput($_POST['full_name'] ?? '');
    $id_number      = sanitizeInput($_POST['id_number'] ?? '');
    $email          = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $phone          = sanitizeInput($_POST['phone'] ?? '');
    $password       = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $security_phrase  = sanitizeInput($_POST['security_phrase'] ?? '');
    $role           = sanitizeInput($_POST['role'] ?? 'user');

    $form_data = compact('user_id', 'full_name', 'id_number', 'email', 'phone', 'role');

    if (empty($user_id)) {
        $errors['user_id'] = "Matric number is required";
    } else {
        $r = validateMatricNumber($user_id);
        if ($r !== true) $errors['user_id'] = $r;
    }

    if (empty($full_name)) {
        $errors['full_name'] = "Full name is required";
    } elseif (strlen($full_name) < 3) {
        $errors['full_name'] = "Full name must be at least 3 characters";
    }

    if (empty($id_number)) {
        $errors['id_number'] = "IC Number or Passport is required";
    } else {
        $r = validateIdNumber($id_number);
        if ($r !== true) $errors['id_number'] = $r;
    }

    if (empty($email)) {
        $errors['email'] = "Email is required";
    } else {
        $r = validateUthmEmail($email, $user_id);
        if ($r !== true) $errors['email'] = $r;
    }

    $r = validatePhoneNumber($phone);
    if ($r !== true) $errors['phone'] = $r;

    if (empty($password)) {
        $errors['password'] = "Password is required";
    } else {
        $r = validateStrongPassword($password);
        if ($r !== true) $errors['password'] = $r;
    }

    if ($password !== $confirm_password) {
        $errors['confirm_password'] = "Passwords do not match";
    }

    if (empty($security_phrase)) {
        $errors['security_phrase'] = "Security phrase is required";
    } elseif (strlen($security_phrase) < 3) {
        $errors['security_phrase'] = "Security phrase must be at least 3 characters";
    }

    if (!in_array($role, ['user', 'seller'])) {
        $role = 'user';
    }

    if (empty($errors)) {
        if (isIdNumberOrPhoneBlocked($pdo, $id_number, $phone)) {
            $errors['blocked'] = "This ID Number or Phone number has been blocked. Contact admin.";
        } elseif (isUserIdOrEmailExists($pdo, $user_id, $email)) {
            $errors['duplicate'] = "Matric number or email already registered.";
        }
    }

    if (empty($errors)) {
        $result = registerUser($pdo, $user_id, $full_name, $id_number, $email, $phone, $password, $security_phrase, $role);
        if ($result) {
            $_SESSION['registration_success'] = "Registration successful! Please login.";
            header("Location: login.php");
            exit();
        } else {
            $errors['database'] = "Registration failed. Please try again.";
        }
    }
}
?>

<?php require_once 'templates/header.php'; ?>

<div class="card">
    <h2>Register</h2>

    <?php if (!empty($errors)): ?>
        <div class="error">
            <strong>Please fix the following errors:</strong>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <label for="user_id">Matric Number *</label>
        <input type="text" id="user_id" name="user_id"
               value="<?php echo htmlspecialchars($form_data['user_id'] ?? ''); ?>" required>
        <small>Format: CD210245 (2 letters + 2-digit year + 4 digits). Year 2020 and below are blocked.</small>

        <label for="full_name">Full Name *</label>
        <input type="text" id="full_name" name="full_name"
               value="<?php echo htmlspecialchars($form_data['full_name'] ?? ''); ?>" required>

        <label for="id_number">IC Number / Passport Number *</label>
        <input type="text" id="id_number" name="id_number"
               value="<?php echo htmlspecialchars($form_data['id_number'] ?? ''); ?>" required>
        <small>Malaysian IC: 12 digits (e.g., 900101011234). International: Passport (6-20 characters).</small>

        <label for="email">Email *</label>
        <input type="email" id="email" name="email"
               value="<?php echo htmlspecialchars($form_data['email'] ?? ''); ?>" required>
        <small>Must be: [matric]@student.uthm.edu.my</small>

        <label for="phone">Phone Number *</label>
        <input type="tel" id="phone" name="phone"
               value="<?php echo htmlspecialchars($form_data['phone'] ?? ''); ?>" required>
        <small>Malaysian format: 0123456789 (10-11 digits)</small>

        <label for="password">Password *</label>
        <input type="password" id="password" name="password" required>
        <small>Min 8 characters with uppercase, lowercase, number, and special character.</small>

        <label for="confirm_password">Confirm Password *</label>
        <input type="password" id="confirm_password" name="confirm_password" required>

        <label for="security_phrase">Security Phrase *</label>
        <input type="text" id="security_phrase" name="security_phrase" required>
        <small>A memorable phrase used for MFA (e.g., "my blue cat").</small>

        <label for="role">Register as *</label>
        <select id="role" name="role">
            <option value="user" <?php echo ($form_data['role'] ?? 'user') == 'user' ? 'selected' : ''; ?>>User (Buy only)</option>
            <option value="seller" <?php echo ($form_data['role'] ?? '') == 'seller' ? 'selected' : ''; ?>>Seller (Buy and Sell)</option>
        </select>

        <br>
        <button type="submit">Register</button>
    </form>

    <p style="margin-top: 1rem; text-align: center;">
        Already have an account? <a href="login.php">Login here</a>
    </p>
</div>

<?php require_once 'templates/footer.php'; ?>
