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
    // Validasi input
    if (empty($input['name']) || empty($input['email']) || empty($input['password'])) {
        throw new Exception("Nama, email, dan password harus diisi");
    }

    $name = htmlspecialchars($input['name']);
    $email = filter_var($input['email'], FILTER_SANITIZE_EMAIL);
    $phone = preg_replace('/[^0-9]/', '', $input['phone'] ?? '');
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Format email tidak valid");
    }

    if (strlen($input['password']) < 8) {
        throw new Exception("Password minimal 8 karakter");
    }

    // Cek email sudah terdaftar
    $stmt = $conn->prepare("SELECT id FROM pelanggan WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->rowCount() > 0) {
        throw new Exception("Email sudah terdaftar");
    }

    // Hash password
    $hashedPassword = password_hash($input['password'], PASSWORD_BCRYPT);

    // Simpan ke database
    $stmt = $conn->prepare("INSERT INTO pelanggan (nama, email, phone, password) VALUES (?, ?, ?, ?)");
    $stmt->execute([$name, $email, $phone, $hashedPassword]);

    http_response_code(201);
    echo json_encode([
        'status' => 'success',
        'message' => 'Registrasi berhasil',
        'data' => [
            'id' => $conn->lastInsertId(),
            'email' => $email
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error']);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>