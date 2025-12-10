<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Database connection
$host = 'localhost';
$dbname = 'api_koment';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database connection failed'
    ]);
    exit();
}

// GET - Ambil semua komentar
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $stmt = $pdo->query("SELECT id, name, message, created_at FROM comments ORDER BY created_at DESC");
        $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => $comments
        ]);
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to fetch comments'
        ]);
    }
    exit();
}

// POST - Simpan komentar baru
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validasi message (wajib)
    $message = isset($data['message']) ? trim($data['message']) : '';
    if (empty($message)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Message is required'
        ]);
        exit();
    }
    
    // Name opsional, default 'Anonim'
    $name = isset($data['name']) ? trim($data['name']) : '';
    if (empty($name)) {
        $name = 'Anonim';
    }
    
    // Sanitize input
    $name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    $message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
    
    // Get IP address
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
    
    try {
        $stmt = $pdo->prepare("INSERT INTO comments (name, message, ip_address) VALUES (?, ?, ?)");
        $stmt->execute([$name, $message, $ip_address]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Comment added successfully',
            'data' => [
                'id' => $pdo->lastInsertId(),
                'name' => $name
            ]
        ]);
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to add comment'
        ]);
    }
    exit();
}

// Method tidak dikenali
http_response_code(405);
echo json_encode([
    'success' => false,
    'error' => 'Method not allowed'
]);
?>