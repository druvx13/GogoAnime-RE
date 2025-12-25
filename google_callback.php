<?php
// Handle Google OAuth Callback
// Logic:
// 1. Get code from URL
// 2. Exchange code for access token
// 3. Get user profile from Google
// 4. Find or Create user in DB
// 5. Log in user

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'app/config/db.php';
require_once 'app/config/info.php';

$config_file = __DIR__ . '/app/config/google_auth.json';
if (!file_exists($config_file)) {
    die("Google Auth not configured.");
}
$config = json_decode(file_get_contents($config_file), true);

if (!isset($_GET['code'])) {
    header("Location: $base_url/login.html");
    exit;
}

$code = $_GET['code'];

// Exchange code for token
$token_url = 'https://oauth2.googleapis.com/token';
$params = [
    'code' => $code,
    'client_id' => $config['client_id'],
    'client_secret' => $config['client_secret'],
    'redirect_uri' => $config['redirect_uri'],
    'grant_type' => 'authorization_code'
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $token_url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

$token_data = json_decode($response, true);

if (!isset($token_data['access_token'])) {
    die("Error fetching access token: " . htmlspecialchars(print_r($token_data, true)));
}

$access_token = $token_data['access_token'];

// Get User Info
$info_url = 'https://www.googleapis.com/oauth2/v1/userinfo?access_token=' . $access_token;
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $info_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$user_info = json_decode(curl_exec($ch), true);
curl_close($ch);

if (!isset($user_info['email'])) {
    die("Error fetching user info.");
}

$email = $user_info['email'];
$name = $user_info['name'] ?? 'Google User';
$google_id = $user_info['id'];

// Check if user exists
try {
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = :email");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // User exists, log in
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['role'] = $user['role'];
    } else {
        // Create new user
        // Generate a random password since they use Google
        $password = bin2hex(random_bytes(10));
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (:name, :email, :password, 'user')");
        $stmt->execute(['name' => $name, 'email' => $email, 'password' => $hashed_password]);

        $uid = $conn->lastInsertId();
        $_SESSION['user_id'] = $uid;
        $_SESSION['user_name'] = $name;
        $_SESSION['role'] = 'user';
    }

    header("Location: $base_url/user.html");
    exit;

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
