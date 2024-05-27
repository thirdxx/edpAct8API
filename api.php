<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

$host = 'localhost';
$db = 'hr';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        if (isset($_GET['departments'])) {
            $stmt = $pdo->query("SELECT dnumber, dname FROM department");
            $departments = $stmt->fetchAll();
            echo json_encode($departments);
        } elseif (isset($_GET['salary']) && isset($_GET['dnumber'])) {
            $dnumber = $_GET['dnumber'];
            $stmt = $pdo->prepare("SELECT totalsalary FROM deptsal WHERE dnumber = ?");
            $stmt->execute([$dnumber]);
            $salary = $stmt->fetch();
            echo json_encode($salary);
        } else {
            $stmt = $pdo->query("SELECT accounts.userid, accounts.username, accounts.pass, accounts.email, accounts.dnumber,
            profile.full_name, profile.phone_number, profile.address FROM accounts 
            LEFT JOIN profile ON accounts.userid = profile.userid");
            $users = $stmt->fetchAll();
            echo json_encode($users);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Query failed: ' . $e->getMessage()]);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!isset($input['username']) || !isset($input['pass']) || !isset($input['email']) || !isset($input['dnumber']) || !isset($input['full_name']) || !isset($input['phone_number']) || !isset($input['address'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid input']);
        exit();
    }

    $sql = "INSERT INTO accounts (username, pass, email, dnumber) VALUES (?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$input['username'], $input['pass'], $input['email'], $input['dnumber']]);

    $userid = $pdo->lastInsertId();

    $sql = "INSERT INTO profile (userid, full_name, phone_number, address) VALUES (?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    try {
        $stmt->execute([$userid, $input['full_name'], $input['phone_number'], $input['address']]);
        echo json_encode(['message' => 'User added successfully']);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Insert failed: ' . $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>
