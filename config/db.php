<?php
// -------------------------------------------------------
// Database configuration (exact values per ITP4523M spec)
// -------------------------------------------------------
$hostname = "127.0.0.1";
$database = "projectDB";
$username = "root";
$password = "";
$conn = mysqli_connect($hostname, $username, $password, $database);

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}
mysqli_set_charset($conn, "utf8mb4");

// -------------------------------------------------------
// Path helpers
// -------------------------------------------------------
define('ROOT_DIR', realpath(__DIR__ . '/..'));

// Compute the URL base path so asset/nav links work regardless of where
// the project is placed under the web server document root.
$_docRoot = isset($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : '';
if ($_docRoot && strpos(ROOT_DIR, $_docRoot) === 0) {
    $_base = substr(ROOT_DIR, strlen($_docRoot));
    define('BASE_URL', rtrim(str_replace('\\', '/', $_base), '/'));
} else {
    define('BASE_URL', '');
}
unset($_docRoot, $_base);
