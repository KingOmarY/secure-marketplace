// In register.php - check if previously blocked
$stmt = $pdo->prepare("SELECT id FROM users WHERE id_number = ? OR phone = ?");
$stmt->execute([$id_number, $phone]);
if ($stmt->fetch()) {
    $errors['blocked'] = "This ID number or phone was previously blocked. Cannot register again.";
}