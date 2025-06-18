<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

require_once __DIR__ . '/config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

try {
    if (empty($input['email']) || empty($input['password'])) {
        throw new Exception("Email dan password harus diisi");
    }

    $email = filter_var($input['email'], FILTER_SANITIZE_EMAIL);
    
    // Cari user
    $stmt = $conn->prepare("SELECT id, nama, email, password FROM pelanggan WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($input['password'], $user['password'])) {
        throw new Exception("Email atau password salah");
    }

    // Update last login
    $conn->prepare("UPDATE pelanggan SET last_login = NOW() WHERE id = ?")
         ->execute([$user['id']]);

    echo json_encode([
        'status' => 'success',
        'message' => 'Login berhasil',
        'data' => [
            'id' => $user['id'],
            'name' => $user['nama'],
            'email' => $user['email']
        ]
    ]);

} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>