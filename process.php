<?php
session_start();

const MAX_UPLOAD_BYTES = 10 * 1024 * 1024; // 10 MB

function redirectWithError($error) {
    $_SESSION['error'] = $error;
    header('Location: index.php');
    exit;
}

function sanitizeFilename($filename) {
    $name = pathinfo($filename, PATHINFO_FILENAME);
    $name = preg_replace('/[^a-zA-Z0-9-_ ]/', '', $name);

    if (empty($name)) {
        return 'certificates';
    }
    return str_replace(' ', '_', $name);
}

// Validate CSRF token (timing-safe)
if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
    redirectWithError('Invalid CSRF token. Please try again.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectWithError('Invalid request method.');
}

$pfxPassword = $_POST['password'] ?? '';

// Validate the upload
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    redirectWithError('No file was uploaded, or the upload failed. Please try again.');
}

$uploadedFile = $_FILES['file'];

$fileType = strtolower(pathinfo($uploadedFile['name'], PATHINFO_EXTENSION));
if ($fileType !== 'pfx') {
    redirectWithError('Only .pfx files are allowed.');
}

if ($uploadedFile['size'] > MAX_UPLOAD_BYTES) {
    redirectWithError('File is too large. The maximum size is 10MB.');
}

// Work in a private, unique directory OUTSIDE the web root so secrets are
// never reachable by URL, and use unique paths so concurrent requests can
// never collide or read each other's keys.
$workDir = sys_get_temp_dir() . '/pfx_' . bin2hex(random_bytes(16));
if (!mkdir($workDir, 0700, true) && !is_dir($workDir)) {
    redirectWithError('Server error. Please try again later.');
}

// Guarantee cleanup of all temp files no matter how the script exits.
register_shutdown_function(function () use ($workDir) {
    if ($workDir && is_dir($workDir)) {
        foreach (glob($workDir . '/*') ?: [] as $file) {
            @unlink($file);
        }
        @rmdir($workDir);
    }
});

$pfxFilePath    = $workDir . '/source.pfx';
$privateKeyFile = $workDir . '/private.pem';
$certFile       = $workDir . '/cert.pem';

if (!move_uploaded_file($uploadedFile['tmp_name'], $pfxFilePath)) {
    redirectWithError('File upload failed. Please try again.');
}

$pfxContent = file_get_contents($pfxFilePath);
if ($pfxContent === false) {
    redirectWithError('Failed to read the uploaded file.');
}

$pkcs12 = [];
if (!openssl_pkcs12_read($pfxContent, $pkcs12, $pfxPassword)) {
    redirectWithError('Could not extract the private key and certificate. Please check that the password is correct and the file is a valid .pfx.');
}

file_put_contents($privateKeyFile, $pkcs12['pkey']);
file_put_contents($certFile, $pkcs12['cert']);

$baseName  = sanitizeFilename($uploadedFile['name']);
$timestamp = date('Ymd_His');
$zipFileName = "{$baseName}_cert_{$timestamp}.zip";
$zipFilePath = $workDir . '/' . $zipFileName;

$zip = new ZipArchive();
if ($zip->open($zipFilePath, ZipArchive::CREATE) !== true) {
    redirectWithError('Error creating the ZIP archive. Please try again later.');
}

$zip->addFile($privateKeyFile, 'private.pem');
$zip->addFile($certFile, 'cert.pem');
$zip->close();

header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $zipFileName . '"');
header('Content-Length: ' . filesize($zipFilePath));
header('X-Content-Type-Options: nosniff');
readfile($zipFilePath);

// Temp files (including the freshly created ZIP) are removed by the
// shutdown handler registered above.
exit;
