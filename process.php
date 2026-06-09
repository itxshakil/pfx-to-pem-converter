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

// Read the results into memory so the page can offer copy + per-file and ZIP
// downloads entirely client-side. Nothing is persisted: the temp dir (PFX,
// PEMs, ZIP) is wiped by the shutdown handler the moment this request ends.
$privateKeyPem = $pkcs12['pkey'];
$certPem       = $pkcs12['cert'];
$zipBase64     = base64_encode((string) file_get_contents($zipFilePath));

// This page contains the user's private key: never cache or index it.
if (!headers_sent()) {
    header('Cache-Control: no-store, max-age=0');
    header('Pragma: no-cache');
    header('X-Robots-Tag: noindex');
}

$page = [
    'title'       => 'Conversion Complete – PFX to PEM Converter',
    'description' => 'Your PFX file has been converted to PEM format.',
    'bodyClass'   => 'bg-gray-50 dark:bg-gray-900 dark:text-white',
];
require $_SERVER['DOCUMENT_ROOT'] . '/partials/head.php';
require $_SERVER['DOCUMENT_ROOT'] . '/partials/header.php';
?>
<main class="flex-grow">
    <section class="py-12">
        <div class="container mx-auto px-4 max-w-4xl">
            <div class="text-center mb-8">
                <i class="fas fa-circle-check text-5xl text-green-500 mb-4" aria-hidden="true"></i>
                <h1 class="text-3xl font-bold text-gray-800 dark:text-white mb-2">Conversion complete</h1>
                <p class="text-gray-600 dark:text-gray-300">
                    Copy the contents below or download the files. Everything was processed in memory and
                    has already been deleted from the server.
                </p>
            </div>

            <div class="glass-card p-6 mb-6">
                <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
                    <h2 class="text-xl font-semibold text-gray-800 dark:text-white">
                        <i class="fas fa-key text-blue-600 dark:text-blue-400 mr-2" aria-hidden="true"></i>private.pem
                    </h2>
                    <div class="flex flex-wrap gap-4">
                        <button type="button" data-copy="pem-key"
                                class="inline-flex items-center px-4 py-2 rounded-lg bg-blue-600 text-white font-medium hover:bg-blue-700 transition duration-200">
                            <i class="fas fa-copy mr-2" aria-hidden="true"></i><span class="copy-label">Copy</span>
                        </button>
                        <button type="button" data-download="pem-key" data-filename="private.pem"
                                class="inline-flex items-center px-4 py-2 rounded-lg bg-gray-800 dark:bg-gray-700 text-white font-medium hover:bg-blue-700 transition duration-200">
                            <i class="fas fa-download mr-2" aria-hidden="true"></i>Download
                        </button>
                    </div>
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">
                    <i class="fas fa-triangle-exclamation mr-1" aria-hidden="true"></i>
                    Keep your private key secret. Store it somewhere safe and never share it.
                </p>
                <pre id="pem-key" class="pem-block"><?= htmlspecialchars($privateKeyPem, ENT_QUOTES) ?></pre>
            </div>

            <div class="glass-card p-6 mb-6">
                <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
                    <h2 class="text-xl font-semibold text-gray-800 dark:text-white">
                        <i class="fas fa-certificate text-blue-600 dark:text-blue-400 mr-2" aria-hidden="true"></i>cert.pem
                    </h2>
                    <div class="flex flex-wrap gap-4">
                        <button type="button" data-copy="pem-cert"
                                class="inline-flex items-center px-4 py-2 rounded-lg bg-blue-600 text-white font-medium hover:bg-blue-700 transition duration-200">
                            <i class="fas fa-copy mr-2" aria-hidden="true"></i><span class="copy-label">Copy</span>
                        </button>
                        <button type="button" data-download="pem-cert" data-filename="cert.pem"
                                class="inline-flex items-center px-4 py-2 rounded-lg bg-gray-800 dark:bg-gray-700 text-white font-medium hover:bg-blue-700 transition duration-200">
                            <i class="fas fa-download mr-2" aria-hidden="true"></i>Download
                        </button>
                    </div>
                </div>
                <pre id="pem-cert" class="pem-block"><?= htmlspecialchars($certPem, ENT_QUOTES) ?></pre>
            </div>

            <div class="text-center">
                <button type="button" id="download-zip"
                        data-zip="<?= $zipBase64 ?>" data-filename="<?= htmlspecialchars($zipFileName, ENT_QUOTES) ?>"
                        class="inline-flex items-center px-6 py-3 rounded-lg bg-blue-600 text-white font-bold hover:bg-blue-700 transition duration-200 shadow-lg">
                    <i class="fas fa-file-zipper mr-2" aria-hidden="true"></i>Download both as .zip
                </button>
                <p class="mt-6">
                    <a href="/" class="text-blue-600 dark:text-blue-400 font-medium">
                        <i class="fas fa-arrow-left mr-2" aria-hidden="true"></i>Convert another file
                    </a>
                </p>
            </div>
        </div>
    </section>
</main>
<script nonce="<?= $nonce ?>">
    (function () {
        'use strict';

        const flash = (btn, text) => {
            const label = btn.querySelector('.copy-label');
            if (!label) return;
            const original = label.textContent;
            label.textContent = text;
            setTimeout(() => { label.textContent = original; }, 2000);
        };

        const saveBlob = (blob, filename) => {
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            a.remove();
            URL.revokeObjectURL(url);
        };

        document.querySelectorAll('[data-copy]').forEach((btn) => {
            btn.addEventListener('click', async () => {
                const source = document.getElementById(btn.getAttribute('data-copy'));
                if (!source) return;
                try {
                    await navigator.clipboard.writeText(source.textContent);
                    flash(btn, 'Copied!');
                } catch (err) {
                    flash(btn, 'Press Ctrl+C');
                }
            });
        });

        document.querySelectorAll('[data-download]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const source = document.getElementById(btn.getAttribute('data-download'));
                if (!source) return;
                saveBlob(new Blob([source.textContent], { type: 'application/x-pem-file' }),
                    btn.getAttribute('data-filename'));
            });
        });

        const zipBtn = document.getElementById('download-zip');
        if (zipBtn) {
            zipBtn.addEventListener('click', () => {
                const binary = atob(zipBtn.getAttribute('data-zip'));
                const bytes = new Uint8Array(binary.length);
                for (let i = 0; i < binary.length; i++) {
                    bytes[i] = binary.charCodeAt(i);
                }
                saveBlob(new Blob([bytes], { type: 'application/zip' }),
                    zipBtn.getAttribute('data-filename'));
            });
        }
    })();
</script>
<?php
require $_SERVER['DOCUMENT_ROOT'] . '/partials/footer.php';

// Temp files (PFX, PEMs, ZIP) are removed by the shutdown handler above.
exit;
