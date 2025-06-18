<?php
require_once 'config/database.php';
session_start();

header('Content-Type: application/json');

$response = [
    'loggedIn' => false,
    'avatar' => 'default-avatar.jpg'
];

if (isset($_SESSION['user_id'])) {
    $response['loggedIn'] = true;
    $response['avatar'] = $_SESSION['user_avatar'] ?? 'default-avatar.jpg';
}

echo json_encode($response);

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.html');
    exit;
}
?>