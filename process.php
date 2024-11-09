<?php
session_start();

function redirectWithError($error) {
    $_SESSION['error'] = $error;
    header("Location: index.php");
    exit;
}

// Validate CSRF token
if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    redirectWithError("Invalid CSRF token. Please try again.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pfxPassword = $_POST['password'];
    $uploadDir = 'uploads/';
    $generatedDir = 'generated/';

    if (!is_dir($uploadDir)) mkdir($uploadDir);
    if (!is_dir($generatedDir)) mkdir($generatedDir);

    $uploadedFile = $_FILES['file'];
    $fileType = strtolower(pathinfo($uploadedFile['name'], PATHINFO_EXTENSION));
    if ($fileType !== 'pfx') {
        redirectWithError("Only .pfx files are allowed.");
    }

    $pfxFilePath = $uploadDir . basename($uploadedFile['name']);

    if (!move_uploaded_file($uploadedFile['tmp_name'], $pfxFilePath)) {
        redirectWithError("File upload failed.");
    }

    $pfxContent = file_get_contents($pfxFilePath);
    if ($pfxContent === false) redirectWithError("Failed to read uploaded file.");

    if (openssl_pkcs12_read($pfxContent, $pkcs12, $pfxPassword)) {
        $privateKey = $pkcs12['pkey'];
        $cert = $pkcs12['cert'];

        $privateKeyFile = $generatedDir . 'private.pem';
        $certFile = $generatedDir . 'cert.pem';

        file_put_contents($privateKeyFile, $privateKey);
        file_put_contents($certFile, $cert);

        $zip = new ZipArchive();
        $zipFilePath = $generatedDir . 'certificate.zip';
        if ($zip->open($zipFilePath, ZipArchive::CREATE) === TRUE) {
            $zip->addFile($privateKeyFile, 'private.pem');
            $zip->addFile($certFile, 'cert.pem');
            $zip->close();

            unlink($privateKeyFile);
            unlink($certFile);
            unlink($pfxFilePath);

            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="certificate.zip"');
            header('Content-Length: ' . filesize($zipFilePath));
            readfile($zipFilePath);

            unlink($zipFilePath);
            exit;
        } else {
            redirectWithError("Failed to create zip file.");
        }
    } else {
        unlink($pfxFilePath);
        redirectWithError("Failed to extract private key and certificate. Incorrect password.");
    }
} else {
    redirectWithError("Invalid request method.");
}
?>
