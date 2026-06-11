<?php
require_once __DIR__ . '/../config/database.php';

// ============ SANITIZATION ============
function sanitizeInput($data) {
    $data = trim($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// ============ VALIDATION ============
/**
 * Validate Matric Number (UTHM Format)
 * Format: 2 letters + 2 digits (year) + 4 digits
 * Example: CD210245 (CD = prefix, 21 = year 2021, 0245 = running number)
 */
function validateMatricNumber($matric) {
    // Check if empty
    if (empty($matric)) {
        return "Matric number is required";
    }
    
    // Check format: 2 letters + 2 digits + 4 digits = total 8 characters
    if (!preg_match('/^[A-Za-z]{2}(\d{2})(\d{4})$/', $matric, $matches)) {
        return "Invalid matric format. Use: 2 letters + 2 digits (year) + 4 digits (e.g., CD210245)";
    }
    
    // Extract year (last two digits of enrollment year)
    $year_two_digits = (int)$matches[1]; // e.g., 21 from CD210245 means 2021
    $full_year = 2000 + $year_two_digits; // Convert 21 → 2021
    
    // Block if enrollment year is 2020 or below
    if ($full_year <= 2020) {
        return "Registration blocked: Enrollment year $full_year is 2020 or below. Only students from 2021 onwards can register.";
    }
    
    return true;
}

/**
 * Validate IC Number (MyKad for Malaysians) OR Passport (for Internationals)
 */
function validateIdNumber($id_number) {
    if (empty($id_number)) {
        return "ID Number (MyKad or Passport) is required";
    }
    
    // Check if it's a Malaysian IC (12 digits)
    if (preg_match('/^\d{12}$/', $id_number)) {
        // Valid Malaysian IC format
        return true;
    }
    
    // Check if it's a Passport (at least 6 characters, letters + numbers)
    elseif (preg_match('/^[A-Za-z0-9]{6,20}$/', $id_number)) {
        // Valid passport format for international students
        return true;
    }
    
    else {
        return "Invalid ID Number. Use 12-digit Malaysian IC (e.g., 900101011234) or Passport number (min 6 characters).";
    }
}

/**
 * Validate Email (must match matric number @student.uthm.edu.my)
 */
function validateUthmEmail($email, $matric_number) {
    if (empty($email)) {
        return "Email is required";
    }
    
    // Convert to lowercase for comparison
    $email = strtolower($email);
    $matric_number = strtolower($matric_number);
    
    // Expected email format: matric@student.uthm.edu.my
    $expected_email = $matric_number . "@student.uthm.edu.my";
    
    if ($email !== $expected_email) {
        return "Email must be: " . $matric_number . "@student.uthm.edu.my";
    }
    
    // Additional email format validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return "Invalid email format";
    }
    
    return true;
}

/**
 * Validate Phone Number (Malaysian format)
 */
function validatePhoneNumber($phone) {
    if (empty($phone)) {
        return "Phone number is required";
    }
    
    // Remove spaces, dashes, and +60 prefix
    $phone = preg_replace('/[\s\-]/', '', $phone);
    if (strpos($phone, '+60') === 0) {
        $phone = '0' . substr($phone, 3);
    }
    
    // Check Malaysian phone format (01X-XXXXXXX)
    if (!preg_match('/^01[0-9]{8,9}$/', $phone)) {
        return "Invalid phone number. Use Malaysian format: 0123456789 or 01112345678";
    }
    
    return true;
}

// Keep your existing strong password validation
function validateStrongPassword($password) {
    if (strlen($password) < 8) {
        return "Password must be at least 8 characters long";
    }
    if (!preg_match('/[A-Z]/', $password)) {
        return "Password must contain at least one uppercase letter";
    }
    if (!preg_match('/[a-z]/', $password)) {
        return "Password must contain at least one lowercase letter";
    }
    if (!preg_match('/\d/', $password)) {
        return "Password must contain at least one number";
    }
    if (!preg_match('/[\W_]/', $password)) {
        return "Password must contain at least one special character (!@#$% etc.)";
    }
    return true;
}

// ============ PASSWORD HASHING WITH SALT ============
function generateSalt() {
    return bin2hex(random_bytes(32));
}

function hashPassword($password, $salt) {
    return hash('sha256', $salt . $password);
}

// ============ REGISTRATION ============
function isIdNumberOrPhoneBlocked($pdo, $id_number, $phone) {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE id_number = ? OR phone = ?");
    $stmt->execute([$id_number, $phone]);
    return $stmt->fetch() ? true : false;
}

function isUserIdOrEmailExists($pdo, $user_id, $email) {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE user_id = ? OR email = ?");
    $stmt->execute([$user_id, $email]);
    return $stmt->fetch() ? true : false;
}

function registerUser($pdo, $user_id, $full_name, $id_number, $email, $phone, $password, $security_phrase, $role) {
    $salt = generateSalt();
    $password_hash = hashPassword($password, $salt);
    
    $sql = "INSERT INTO users (user_id, full_name, id_number, email, phone, password_hash, salt, security_phrase, role) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([$user_id, $full_name, $id_number, $email, $phone, $password_hash, $salt, $security_phrase, $role]);
}

// ============ LOGIN ============
function getUserByUserId($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}

function verifyPassword($password, $salt, $password_hash) {
    return hashPassword($password, $salt) === $password_hash;
}

function isUserBlocked($user) {
    return $user['status'] === 'blocked';
}

// ============ PRODUCT FUNCTIONS ============
function getProductCountBySeller($pdo, $seller_id) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE seller_id = ?");
    $stmt->execute([$seller_id]);
    return $stmt->fetchColumn();
}

function updateSellerTaxRate($pdo, $seller_id, $product_count) {
    $tax_rate = ($product_count > 5) ? 10.00 : 0.00;
    $stmt = $pdo->prepare("UPDATE users SET tax_rate = ? WHERE id = ?");
    $stmt->execute([$tax_rate, $seller_id]);
    
    // Log tax change
    $stmt2 = $pdo->prepare("INSERT INTO tax_log (seller_id, product_count, tax_applied) VALUES (?, ?, ?)");
    $stmt2->execute([$seller_id, $product_count, $tax_rate]);
    
    return $tax_rate;
}

function postProduct($pdo, $seller_id, $title, $description, $price) {
    $stmt = $pdo->prepare("INSERT INTO products (seller_id, title, description, price) VALUES (?, ?, ?, ?)");
    return $stmt->execute([$seller_id, $title, $description, $price]);
}

function getAllProducts($pdo) {
    $stmt = $pdo->prepare("SELECT products.*, users.full_name, users.phone, users.user_id 
                           FROM products 
                           JOIN users ON products.seller_id = users.id 
                           ORDER BY products.created_at DESC");
    $stmt->execute();
    return $stmt->fetchAll();
}

function searchProducts($pdo, $keyword) {
    $stmt = $pdo->prepare("SELECT products.*, users.full_name, users.phone 
                           FROM products 
                           JOIN users ON products.seller_id = users.id 
                           WHERE products.title LIKE ? OR products.description LIKE ?
                           ORDER BY products.created_at DESC");
    $searchTerm = "%$keyword%";
    $stmt->execute([$searchTerm, $searchTerm]);
    return $stmt->fetchAll();
}

// ============ LIKE FUNCTIONS ============
function isProductLiked($pdo, $user_id, $product_id) {
    $stmt = $pdo->prepare("SELECT id FROM likes WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$user_id, $product_id]);
    return $stmt->fetch() ? true : false;
}

function toggleLike($pdo, $user_id, $product_id) {
    if (isProductLiked($pdo, $user_id, $product_id)) {
        $stmt = $pdo->prepare("DELETE FROM likes WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$user_id, $product_id]);
        return "unliked";
    } else {
        $stmt = $pdo->prepare("INSERT INTO likes (user_id, product_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $product_id]);
        return "liked";
    }
}

function getLikeCount($pdo, $product_id) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE product_id = ?");
    $stmt->execute([$product_id]);
    return $stmt->fetchColumn();
}

// ============ COMMENT FUNCTIONS ============
function addComment($pdo, $user_id, $product_id, $comment) {
    $stmt = $pdo->prepare("INSERT INTO comments (user_id, product_id, comment) VALUES (?, ?, ?)");
    return $stmt->execute([$user_id, $product_id, $comment]);
}

function getCommentsByProduct($pdo, $product_id) {
    $stmt = $pdo->prepare("SELECT comments.*, users.full_name 
                           FROM comments 
                           JOIN users ON comments.user_id = users.id 
                           WHERE product_id = ? 
                           ORDER BY comments.created_at DESC");
    $stmt->execute([$product_id]);
    return $stmt->fetchAll();
}

// ============ ANNOUNCEMENT FUNCTIONS ============
function getAllAnnouncements($pdo) {
    $stmt = $pdo->prepare("SELECT * FROM announcements ORDER BY posted_at DESC");
    $stmt->execute();
    return $stmt->fetchAll();
}

function postAnnouncement($pdo, $title, $content, $posted_by) {
    $stmt = $pdo->prepare("INSERT INTO announcements (title, content, posted_by) VALUES (?, ?, ?)");
    return $stmt->execute([$title, $content, $posted_by]);
}

// ============ ADMIN FUNCTIONS ============
function blockUser($pdo, $user_id) {
    $stmt = $pdo->prepare("UPDATE users SET status = 'blocked' WHERE id = ?");
    return $stmt->execute([$user_id]);
}

function deleteUser($pdo, $user_id) {
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    return $stmt->execute([$user_id]);
}

function deleteProduct($pdo, $product_id) {
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    return $stmt->execute([$product_id]);
}

function getAllUsers($pdo) {
    $stmt = $pdo->prepare("SELECT id, user_id, full_name, email, phone, role, status, created_at FROM users ORDER BY created_at DESC");
    $stmt->execute();
    return $stmt->fetchAll();
}
?>