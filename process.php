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

/**
 * Walks issuer to subject from the leaf so the chain is in the order servers
 * expect, whatever order the file stored it in. Same rule as js/pfx-convert.js.
 */
function orderChain($leafPem, array $certPems) {
    $remaining = array_values($certPems);
    $ordered = [];
    $current = openssl_x509_parse($leafPem);
    while ($current !== false) {
        $next = null;
        foreach ($remaining as $i => $pem) {
            $parsed = openssl_x509_parse($pem);
            if ($parsed !== false && $parsed['subject'] === $current['issuer']) {
                $next = $i;
                break;
            }
        }
        if ($next === null) {
            break;
        }
        $ordered[] = $remaining[$next];
        $current = openssl_x509_parse($remaining[$next]);
        array_splice($remaining, $next, 1);
    }
    return array_merge($ordered, $remaining);
}

function isSelfSigned($pem) {
    $parsed = openssl_x509_parse($pem);
    return $parsed !== false && $parsed['subject'] === $parsed['issuer'];
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

$pfxFilePath = $workDir . '/source.pfx';

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

// Intermediate and root certificates, leaf first, plus the fullchain (leaf and
// intermediates) that nginx and most hosts want.
$chain = [];
if (!empty($pkcs12['extracerts']) && is_array($pkcs12['extracerts'])) {
    $chain = orderChain($pkcs12['cert'], array_map(static fn ($pem) => trim($pem) . "\n", $pkcs12['extracerts']));
}
$caChainPem = implode('', $chain);
$intermediates = array_filter($chain, static fn ($pem) => !isSelfSigned($pem));
// Without intermediates the fullchain is cert.pem again, so leave it out.
$fullchainPem = $intermediates === [] ? '' : $pkcs12['cert'] . implode('', $intermediates);

$zipFileName = sanitizeFilename($uploadedFile['name']) . '_cert_' . date('Ymd_His') . '.zip';

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
<?php
$result = [
    'key'       => $pkcs12['pkey'],
    'cert'      => $pkcs12['cert'],
    'fullchain' => $fullchainPem,
    'chain'     => $caChainPem,
    'zipName'   => $zipFileName,
];
$resultIntro = 'Copy the contents below or download the files. Your file was converted on our server in a temporary folder that is deleted as soon as this page is sent.';
require $_SERVER['DOCUMENT_ROOT'] . '/partials/result.php';
?>
</main>
<script src="/js/pfx-convert.js" defer></script>
<script src="/js/cert-details.js" defer></script>
<script src="/js/result.js" defer></script>
<?php
require $_SERVER['DOCUMENT_ROOT'] . '/partials/footer.php';

// The uploaded .pfx is removed by the shutdown handler above.
exit;
