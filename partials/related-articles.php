<?php
/**
 * Shared "Related articles" section for blog pages.
 *
 * A page sets $relatedSlugs (array of blog slugs) before including this file:
 *   $relatedSlugs = ['extract-ssl-certificate-from-pfx', 'ssl-tls-key-difference'];
 *   require $_SERVER['DOCUMENT_ROOT'] . '/partials/related-articles.php';
 *
 * Card copy + icon live in one central registry so every page agrees.
 */

$ARTICLES = [
    'extract-ssl-certificate-from-pfx' => [
        'icon'  => 'certificate',
        'title' => 'How to extract SSL certificates from PFX: a complete guide',
        'desc'  => 'Extract SSL certificates from a PFX file using OpenSSL — a step-by-step guide to converting your certificates quickly and securely.',
    ],
    'install-ssl-apache' => [
        'icon'  => 'server',
        'title' => 'How to install SSL certificates on Apache',
        'desc'  => 'Set up HTTPS on Apache — obtain, install, and configure your SSL certificate, then verify everything works.',
    ],
    'renew-ssl-certificate' => [
        'icon'  => 'rotate',
        'title' => 'How to renew your SSL certificates: a quick guide',
        'desc'  => 'Renew your SSL certificates and keep your website secure — a quick, step-by-step guide for easy certificate renewal.',
    ],
    'ssl-certificate-chain' => [
        'icon'  => 'route',
        'title' => 'Understanding SSL certificate chains and how they work',
        'desc'  => 'Learn what an SSL certificate chain is, how it validates trust, and how to fix common chain-of-trust issues.',
    ],
    'ssl-importance' => [
        'icon'  => 'shield-halved',
        'title' => 'What is SSL and why is it important for website security?',
        'desc'  => 'Learn what SSL is, how it works, and why it is essential for securing your website and earning visitor trust.',
    ],
    'ssl-tls-key-difference' => [
        'icon'  => 'right-left',
        'title' => 'SSL vs TLS: key differences and why they matter',
        'desc'  => 'Learn the key differences between SSL and TLS protocols and why TLS is now the preferred choice for secure communications.',
    ],
];

$relatedSlugs = array_filter($relatedSlugs ?? [], static fn($s) => isset($ARTICLES[$s]));
if (!$relatedSlugs) {
    return;
}
$h = static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES);
?>
<section class="border-t-base bg-surface-2">
    <div class="container mx-auto max-w-5xl px-4 py-14">
        <div class="mb-8">
            <span class="eyebrow mb-3"><svg class="icon" aria-hidden="true"><use href="#i-newspaper"/></svg> Guides</span>
            <h2 class="text-2xl font-bold text-body">Related articles</h2>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <?php foreach ($relatedSlugs as $slug): $a = $ARTICLES[$slug]; ?>
            <a href="/blogs/<?= $h($slug) ?>/" class="surface-card grow-on-hover p-6 block">
                <span class="icon-tile w-12 h-12 mb-4"><svg class="icon text-lg" aria-hidden="true"><use href="#i-<?= $h($a['icon']) ?>"/></svg></span>
                <h3 class="text-lg font-semibold text-body mb-2"><?= $h($a['title']) ?></h3>
                <p class="text-muted text-sm mb-4"><?= $h($a['desc']) ?></p>
                <span class="inline-flex items-center gap-2 text-brand font-medium text-sm">Read more <svg class="icon" aria-hidden="true"><use href="#i-arrow-right"/></svg></span>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
