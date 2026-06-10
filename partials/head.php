<?php
/**
 * Shared document <head> + opening <body> and layout wrapper.
 *
 * A page defines $page before including this file:
 *   $page = [
 *     'title'         => 'Page title',            // required
 *     'description'   => 'Meta description',       // required
 *     'keywords'      => 'a, b, c',                // optional
 *     'canonical'     => 'https://.../path',       // optional (also og:url)
 *     'ogTitle'       => '...',                     // optional, defaults to title
 *     'ogDescription' => '...',                     // optional, defaults to description
 *     'image'         => 'https://.../img.png',     // optional (og/twitter image)
 *     'appleTitle'    => '...',                      // optional
 *     'bodyClass'     => '...',                      // optional
 *     'jsonld'        => '{ ... }',                  // optional raw JSON-LD string
 *   ];
 *
 * A per-request $nonce is generated here (if not already set) and reused for the
 * Content-Security-Policy and every inline <script>.
 */

if (!isset($nonce)) {
    $nonce = base64_encode(random_bytes(16));
}

if (!headers_sent()) {
    header(
        "Content-Security-Policy: " .
        "default-src 'self'; " .
        "script-src 'self' 'nonce-$nonce' https://www.googletagmanager.com https://www.google-analytics.com; " .
        "style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; " .
        "font-src 'self' https://cdnjs.cloudflare.com; " .
        "img-src 'self' data: https://www.google-analytics.com https://ui-avatars.com; " .
        "connect-src 'self' https://www.google-analytics.com; " .
        "object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'"
    );
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
}

$e = static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES);

$pTitle      = $page['title']         ?? 'PFX to PEM Converter';
$pDesc       = $page['description']   ?? '';
$pKeywords   = $page['keywords']      ?? '';
$pCanonical  = $page['canonical']     ?? '';
$pOgTitle    = $page['ogTitle']       ?? $pTitle;
$pOgDesc     = $page['ogDescription'] ?? $pDesc;
$pImage      = $page['image']         ?? 'https://ui-avatars.com/api/?name=PFX+to+PEM&background=1e40af&color=ffffff&size=512';
$pAppleTitle = $page['appleTitle']    ?? 'PFX to PEM Converter';
$pBodyClass  = $page['bodyClass']     ?? 'scroll-mt-20 scroll-smooth';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $e($pTitle) ?></title>

    <meta name="theme-color" content="#1e40af" />
    <meta name="apple-mobile-web-app-capable" content="yes" />
    <meta name="apple-mobile-web-app-status-bar-style" content="#1e40af" />
    <meta name="apple-mobile-web-app-title" content="<?= $e($pAppleTitle) ?>" />
    <meta name="msapplication-TitleImage" content="/images/ios/144.png" />
    <meta name="msapplication-TitleColor" content="#1e40af" />
    <link rel="shortcut icon" href="/images/favicon.ico" type="image/x-icon">
    <link rel="apple-touch-icon" href="/images/ios/512.png">

    <!-- SEO Meta Tags -->
    <meta name="description" content="<?= $e($pDesc) ?>">
    <?php if ($pKeywords !== ''): ?>
    <meta name="keywords" content="<?= $e($pKeywords) ?>">
    <?php endif; ?>
    <meta name="author" content="Shakil Alam">

    <!-- Open Graph Meta Tags for Social Sharing -->
    <meta property="og:title" content="<?= $e($pOgTitle) ?>">
    <meta property="og:description" content="<?= $e($pOgDesc) ?>">
    <meta property="og:image" content="<?= $e($pImage) ?>">
    <?php if ($pCanonical !== ''): ?>
    <meta property="og:url" content="<?= $e($pCanonical) ?>">
    <?php endif; ?>
    <meta property="og:type" content="website">
    <meta property="og:locale" content="en_US">

    <!-- Twitter Card Data -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= $e($pOgTitle) ?>">
    <meta name="twitter:description" content="<?= $e($pOgDesc) ?>">
    <meta name="twitter:image" content="<?= $e($pImage) ?>">

    <?php if ($pCanonical !== ''): ?>
    <!-- Canonical Link -->
    <link rel="canonical" href="<?= $e($pCanonical) ?>">
    <?php endif; ?>

    <?php if (!empty($page['jsonld'])): ?>
    <!-- Schema.org Structured Data Markup -->
    <script type="application/ld+json" nonce="<?= $nonce ?>"><?= $page['jsonld'] ?></script>
    <?php endif; ?>

    <!-- Tailwind CSS -->
    <link href="/css/app.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="/css/custom.css" rel="stylesheet">

    <link rel="manifest" href="/manifest.json" />
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-08FR6JHTZX"></script>
    <script nonce="<?= $nonce ?>">
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());

        gtag('config', 'G-08FR6JHTZX');
    </script>
</head>

<body class="<?= $e($pBodyClass) ?>">

<div class="min-h-[100svh] flex flex-col">
