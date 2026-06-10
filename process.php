<?php
session_start();

const MAX_UPLOAD_BYTES = 10 * 1024 * 1024; // 10 MB

function redirectWithError($error) {
    $_SESSION['error'] = $error;
    header('Location: index.php');
    exit;
}

/**
 * Does the openssl CLI on this host support the -legacy flag?
 *
 * Only OpenSSL 3+ has the separate "legacy" provider (and the -legacy flag).
 * LibreSSL and OpenSSL 1.x read RC2/RC4/3DES-encrypted bundles natively and
 * reject the flag, so we must not pass it there.
 */
function opensslCliSupportsLegacy() {
    if (!function_exists('proc_open')) {
        return false;
    }
    $descriptors = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
    $proc = @proc_open(['openssl', 'version'], $descriptors, $pipes);
    if (!is_resource($proc)) {
        return false;
    }
    $out = stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    proc_close($proc);

    // "OpenSSL 3.0.2 ..." → legacy provider exists. "LibreSSL"/"OpenSSL 1.x" → no.
    return (bool) preg_match('/^OpenSSL\s+([3-9]|\d{2,})\./', trim((string) $out));
}

/**
 * Fallback PFX reader for OpenSSL 3 hosts.
 *
 * OpenSSL 3 moved the legacy ciphers (RC2/RC4/3DES) that many older .pfx
 * exports rely on into a provider that is disabled by default, so the built-in
 * openssl_pkcs12_read() returns false for those files even with the correct
 * password. The openssl CLI can still read them with -legacy. We shell out via
 * proc_open with an argument array (no shell is involved, so the file path
 * cannot be interpreted) and pass the password through the environment so it
 * never appears in the process list / argv.
 *
 * Returns ['pkey' => <pem>, 'cert' => <pem>] on success, or null on failure.
 */
function pfxReadLegacyFallback($pfxPath, $password) {
    if (!function_exists('proc_open')) {
        return null;
    }

    $args = ['openssl', 'pkcs12', '-in', $pfxPath, '-nodes', '-passin', 'env:PFX_PASS'];
    if (opensslCliSupportsLegacy()) {
        $args[] = '-legacy';
    }

    $descriptors = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];

    // proc_open replaces the environment wholesale, so PATH must be carried
    // over for the binary to be found.
    $env = [
        'PFX_PASS' => $password,
        'PATH'     => getenv('PATH') ?: '/usr/local/bin:/usr/bin:/bin',
    ];

    $proc = @proc_open($args, $descriptors, $pipes, null, $env);
    if (!is_resource($proc)) {
        return null;
    }

    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $exitCode = proc_close($proc);

    if ($exitCode !== 0 || !is_string($stdout) || $stdout === '') {
        return null;
    }

    if (!preg_match('/-----BEGIN [A-Z0-9 ]*PRIVATE KEY-----.*?-----END [A-Z0-9 ]*PRIVATE KEY-----/s', $stdout, $keyMatch)
        || !preg_match_all('/-----BEGIN CERTIFICATE-----.*?-----END CERTIFICATE-----/s', $stdout, $certMatches)) {
        return null;
    }

    // The first certificate is the leaf; any remaining ones are the CA chain,
    // mirroring how openssl_pkcs12_read() splits 'cert' from 'extracerts'.
    $certs      = $certMatches[0];
    $leaf       = array_shift($certs);
    $extracerts = array_map(static function ($c) { return $c . "\n"; }, $certs);

    return [
        'pkey'       => $keyMatch[0] . "\n",
        'cert'       => $leaf . "\n",
        'extracerts' => $extracerts,
    ];
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
$caChainFile    = $workDir . '/ca-chain.pem';

if (!move_uploaded_file($uploadedFile['tmp_name'], $pfxFilePath)) {
    redirectWithError('File upload failed. Please try again.');
}

$pfxContent = file_get_contents($pfxFilePath);
if ($pfxContent === false) {
    redirectWithError('Failed to read the uploaded file.');
}

$pkcs12 = [];
if (!openssl_pkcs12_read($pfxContent, $pkcs12, $pfxPassword)) {
    // Many older .pfx files are encrypted with legacy ciphers (RC2/RC4/3DES)
    // that OpenSSL 3 disables by default, which makes the native reader fail
    // even when the password is correct. Retry via the openssl CLI -legacy
    // path before giving up.
    $pkcs12 = pfxReadLegacyFallback($pfxFilePath, $pfxPassword);
    if ($pkcs12 === null) {
        redirectWithError('Could not extract the private key and certificate. Please check that the password is correct and the file is a valid .pfx.');
    }
}

file_put_contents($privateKeyFile, $pkcs12['pkey']);
file_put_contents($certFile, $pkcs12['cert']);

// Assemble the CA chain (intermediate + root certificates) if the PFX bundled
// any. Many servers need this for the certificate chain to validate.
$caChainPem = '';
if (!empty($pkcs12['extracerts']) && is_array($pkcs12['extracerts'])) {
    $caChainPem = trim(implode("\n", array_map('trim', $pkcs12['extracerts']))) . "\n";
}
$hasCaChain = $caChainPem !== '';
if ($hasCaChain) {
    file_put_contents($caChainFile, $caChainPem);
}

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
if ($hasCaChain) {
    $zip->addFile($caChainFile, 'ca-chain.pem');
}
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
    'bodyClass'   => 'scroll-mt-20 scroll-smooth',
];
require $_SERVER['DOCUMENT_ROOT'] . '/partials/head.php';
require $_SERVER['DOCUMENT_ROOT'] . '/partials/header.php';
?>
<main class="grow">
    <section class="py-14">
        <div class="container mx-auto px-4 max-w-4xl">
            <div class="text-center mb-10">
                <span class="icon-tile w-14 h-14 mx-auto mb-4" style="background: var(--success-soft); color: var(--success);">
                    <svg class="icon text-2xl" aria-hidden="true"><use href="#i-circle-check"/></svg>
                </span>
                <h1 class="text-3xl font-bold text-body mb-2">Conversion complete</h1>
                <p class="text-muted max-w-xl mx-auto">
                    Copy the contents below or download the files. Everything was processed in a temporary folder and
                    has already been deleted from the server.
                </p>
            </div>

            <div class="surface-card p-6 mb-6">
                <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
                    <h2 class="text-lg font-semibold text-body flex items-center gap-2">
                        <svg class="icon text-brand" aria-hidden="true"><use href="#i-key"/></svg>private.pem
                    </h2>
                    <div class="flex flex-wrap gap-3">
                        <button type="button" data-copy="pem-key"
                                class="inline-flex items-center gap-2 px-4 py-2 rounded-lg btn-gradient font-medium text-sm">
                            <svg class="icon" aria-hidden="true"><use href="#i-copy"/></svg><span class="copy-label">Copy</span>
                        </button>
                        <button type="button" data-download="pem-key" data-filename="private.pem"
                                class="inline-flex items-center gap-2 px-4 py-2 rounded-lg btn-secondary font-medium text-sm">
                            <svg class="icon" aria-hidden="true"><use href="#i-download"/></svg>Download
                        </button>
                    </div>
                </div>
                <p class="text-sm text-faint mb-3 flex items-center gap-1.5">
                    <svg class="icon" aria-hidden="true"><use href="#i-triangle-exclamation"/></svg>
                    Keep your private key secret. Store it somewhere safe and never share it.
                </p>
                <pre id="pem-key" class="pem-block"><?= htmlspecialchars($privateKeyPem, ENT_QUOTES) ?></pre>
            </div>

            <div class="surface-card p-6 mb-6">
                <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
                    <h2 class="text-lg font-semibold text-body flex items-center gap-2">
                        <svg class="icon text-brand" aria-hidden="true"><use href="#i-certificate"/></svg>cert.pem
                    </h2>
                    <div class="flex flex-wrap gap-3">
                        <button type="button" data-copy="pem-cert"
                                class="inline-flex items-center gap-2 px-4 py-2 rounded-lg btn-gradient font-medium text-sm">
                            <svg class="icon" aria-hidden="true"><use href="#i-copy"/></svg><span class="copy-label">Copy</span>
                        </button>
                        <button type="button" data-download="pem-cert" data-filename="cert.pem"
                                class="inline-flex items-center gap-2 px-4 py-2 rounded-lg btn-secondary font-medium text-sm">
                            <svg class="icon" aria-hidden="true"><use href="#i-download"/></svg>Download
                        </button>
                    </div>
                </div>
                <pre id="pem-cert" class="pem-block"><?= htmlspecialchars($certPem, ENT_QUOTES) ?></pre>
            </div>

            <?php if ($hasCaChain): ?>
            <div class="surface-card p-6 mb-6">
                <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
                    <h2 class="text-lg font-semibold text-body flex items-center gap-2">
                        <svg class="icon text-brand" aria-hidden="true"><use href="#i-route"/></svg>ca-chain.pem
                    </h2>
                    <div class="flex flex-wrap gap-3">
                        <button type="button" data-copy="pem-ca"
                                class="inline-flex items-center gap-2 px-4 py-2 rounded-lg btn-gradient font-medium text-sm">
                            <svg class="icon" aria-hidden="true"><use href="#i-copy"/></svg><span class="copy-label">Copy</span>
                        </button>
                        <button type="button" data-download="pem-ca" data-filename="ca-chain.pem"
                                class="inline-flex items-center gap-2 px-4 py-2 rounded-lg btn-secondary font-medium text-sm">
                            <svg class="icon" aria-hidden="true"><use href="#i-download"/></svg>Download
                        </button>
                    </div>
                </div>
                <p class="text-sm text-faint mb-3 flex items-center gap-1.5">
                    <svg class="icon" aria-hidden="true"><use href="#i-lightbulb"/></svg>
                    Intermediate &amp; root certificates. Many servers need this for the chain to validate.
                </p>
                <pre id="pem-ca" class="pem-block"><?= htmlspecialchars($caChainPem, ENT_QUOTES) ?></pre>
            </div>
            <?php endif; ?>

            <div class="text-center">
                <button type="button" id="download-zip"
                        data-zip="<?= $zipBase64 ?>" data-filename="<?= htmlspecialchars($zipFileName, ENT_QUOTES) ?>"
                        class="inline-flex items-center gap-2 px-6 py-3 rounded-lg btn-gradient font-semibold">
                    <svg class="icon" aria-hidden="true"><use href="#i-file-zipper"/></svg>Download all as .zip
                </button>
                <p class="mt-6">
                    <a href="/" class="inline-flex items-center gap-2 text-brand font-medium">
                        <svg class="icon" aria-hidden="true"><use href="#i-arrow-left"/></svg>Convert another file
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
