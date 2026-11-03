<?php
/**
 * The converted key, certificate, fullchain and CA chain with copy and download buttons.
 * Shared by both paths so they look the same:
 *  - index.php renders it empty and hidden; js/convert.js fills it in after converting in the browser.
 *  - process.php renders it filled in after the server fallback.
 * js/result.js wires up the buttons, builds the ZIP from the text in the page and fills
 * the details slots (validity, domains, key match) from the PEM text, for both paths.
 *
 * Expects $result = ['key' => string, 'cert' => string, 'fullchain' => string, 'chain' => string, 'zipName' => ?string]
 * and $resultIntro. An empty key means the browser path: start hidden, below the page's own h1.
 */
$isEmpty = $result['key'] === '';
$heading = $isEmpty ? 'h2' : 'h1';
$blocks = [
    ['id' => 'pem-key', 'file' => 'private.pem', 'icon' => 'i-key', 'text' => $result['key'],
        'note' => ['i-triangle-exclamation', 'Keep your private key secret. Store it somewhere safe and never share it.']],
    ['id' => 'pem-cert', 'file' => 'cert.pem', 'icon' => 'i-certificate', 'text' => $result['cert'], 'note' => null],
    ['id' => 'pem-full', 'file' => 'fullchain.pem', 'icon' => 'i-list-check', 'text' => $result['fullchain'],
        'note' => ['i-lightbulb', 'Your certificate followed by its intermediates. Use this for Nginx <code>ssl_certificate</code> and most hosting panels.']],
    ['id' => 'pem-ca', 'file' => 'ca-chain.pem', 'icon' => 'i-route', 'text' => $result['chain'],
        'note' => ['i-lightbulb', 'Intermediate &amp; root certificates, in order from your certificate up. For Apache <code>SSLCertificateChainFile</code>.']],
];
?>
<section id="result" class="py-14" tabindex="-1" <?= $isEmpty ? 'hidden' : '' ?>>
    <div class="container mx-auto px-4 max-w-4xl">
        <div class="text-center mb-10">
            <span class="icon-tile w-14 h-14 mx-auto mb-4" style="background: var(--success-soft); color: var(--success);">
                <svg class="icon text-2xl" aria-hidden="true"><use href="#i-circle-check"/></svg>
            </span>
            <<?= $heading ?> class="text-3xl font-bold text-body mb-2">Conversion complete</<?= $heading ?>>
            <p class="text-muted max-w-xl mx-auto"><?= htmlspecialchars($resultIntro, ENT_QUOTES) ?></p>
        </div>

        <?php foreach ($blocks as $block): ?>
        <div class="surface-card p-6 mb-6" data-pem-card="<?= $block['id'] ?>" <?= $block['text'] === '' ? 'hidden' : '' ?>>
            <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
                <h3 class="text-lg font-semibold text-body flex items-center gap-2">
                    <svg class="icon text-brand" aria-hidden="true"><use href="#<?= $block['icon'] ?>"/></svg><?= $block['file'] ?>
                </h3>
                <div class="flex flex-wrap gap-3">
                    <button type="button" data-copy="<?= $block['id'] ?>"
                            class="inline-flex items-center gap-2 px-4 py-2 rounded-lg btn-gradient font-medium text-sm">
                        <svg class="icon" aria-hidden="true"><use href="#i-copy"/></svg><span class="copy-label">Copy</span>
                    </button>
                    <button type="button" data-download="<?= $block['id'] ?>" data-filename="<?= $block['file'] ?>"
                            class="inline-flex items-center gap-2 px-4 py-2 rounded-lg btn-secondary font-medium text-sm">
                        <svg class="icon" aria-hidden="true"><use href="#i-download"/></svg>Download
                    </button>
                </div>
            </div>
            <?php if ($block['note']): ?>
            <p class="text-sm text-faint mb-3 flex items-center gap-1.5">
                <svg class="icon" aria-hidden="true"><use href="#<?= $block['note'][0] ?>"/></svg>
                <?= $block['note'][1] ?>
            </p>
            <?php endif; ?>
            <pre id="<?= $block['id'] ?>" class="pem-block"><?= htmlspecialchars($block['text'], ENT_QUOTES) ?></pre>
            <div class="cert-details" data-details-for="<?= $block['id'] ?>" hidden></div>
        </div>
        <?php endforeach; ?>

        <div class="text-center">
            <button type="button" id="download-zip" data-filename="<?= htmlspecialchars($result['zipName'] ?? 'certificates.zip', ENT_QUOTES) ?>"
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
