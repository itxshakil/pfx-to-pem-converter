<?php
session_start();

// Generate CSRF token if it doesn't exist
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$page = [
    'title'         => 'Extract Private Key and Certificate from PFX | Secure PFX to PEM Converter',
    'description'   => 'Extract private key and certificate from your .pfx file and convert it to PEM format. Securely extract the private key and certificate from PFX files for server compatibility and SSL management.',
    'keywords'      => 'extract private key from PFX, extract certificate from PFX, PFX to PEM, PFX converter, PEM converter, SSL certificate tool, PKCS#12 to PEM, PFX private key extraction, PFX certificate extraction',
    'canonical'     => 'https://pfx-to-pem-converter.shakiltech.com',
    'ogTitle'       => 'Extract Private Key and Certificate from PFX | Secure Conversion',
    'ogDescription' => 'Extract private key and certificate from PFX files, then convert to PEM format with our secure and easy-to-use tool. Perfect for SSL/TLS certificate management.',
    'image'         => 'https://ui-avatars.com/api/?name=PFX+to+PEM&background=1e40af&color=ffffff&size=512',
    'jsonld'        => <<<'JSONLD'
{
    "@context": "https://schema.org",
    "@type": "SoftwareApplication",
    "name": "PFX to PEM Converter",
    "description": "A tool to extract private key and certificate from .pfx file and convert it into PEM format for SSL/TLS management.",
    "url": "https://pfx-to-pem-converter.shakiltech.com",
    "applicationCategory": "Utilities",
    "operatingSystem": "Web",
    "softwareVersion": "1.0",
    "author": {
        "@type": "Organization",
        "name": "Shakil Alam"
    },
    "license": "https://pfx-to-pem-converter.shakiltech.com/terms",
    "mainEntityOfPage": {
        "@type": "WebPage",
        "@id": "https://pfx-to-pem-converter.shakiltech.com"
    },
    "offers": {
        "@type": "Offer",
        "url": "https://pfx-to-pem-converter.shakiltech.com",
        "price": 0,
        "priceCurrency" : "INR",
        "eligibleRegion": {
            "@type": "Place",
            "name": "Worldwide"
        },
        "seller": {
            "@type": "Organization",
            "name": "Shakil Alam"
        }
    }
}
JSONLD,
];

require $_SERVER['DOCUMENT_ROOT'] . '/partials/head.php';
require $_SERVER['DOCUMENT_ROOT'] . '/partials/header.php';
?>
    <main class="flex-grow">
        <!-- Hero + converter (converter-first) -->
        <section class="hero-surface border-b-base">
            <div class="container mx-auto max-w-6xl px-4 py-16 md:py-20 grid lg:grid-cols-2 gap-12 lg:gap-16 items-start">
                <!-- Left: pitch + trust -->
                <div class="lg:pt-6">
                    <span class="eyebrow mb-5">
                        <svg class="icon" aria-hidden="true"><use href="#i-shield-halved"/></svg> Secure certificate tooling
                    </span>
                    <h1 class="text-4xl md:text-5xl font-bold text-body leading-tight mb-5">
                        Extract keys &amp; certificates from PFX, in PEM
                    </h1>
                    <p class="text-lg text-muted mb-8 max-w-xl">
                        Upload a <code class="text-brand font-medium">.pfx</code> file and get the private key and
                        certificate as ready-to-use PEM files. Processed over an encrypted connection and deleted the
                        moment your download is ready — never stored.
                    </p>
                    <div class="flex flex-wrap gap-3 mb-10">
                        <span class="trust-pill"><svg class="icon" aria-hidden="true"><use href="#i-lock"/></svg> Encrypted in transit</span>
                        <span class="trust-pill"><svg class="icon" aria-hidden="true"><use href="#i-trash-can"/></svg> Deleted instantly</span>
                        <span class="trust-pill"><svg class="icon" aria-hidden="true"><use href="#i-eye-slash"/></svg> Nothing logged</span>
                    </div>
                    <dl class="grid grid-cols-3 gap-6 max-w-md">
                        <div>
                            <dt class="text-2xl font-bold text-body">PKCS#12</dt>
                            <dd class="text-sm text-faint">Standard format</dd>
                        </div>
                        <div>
                            <dt class="text-2xl font-bold text-body">2 files</dt>
                            <dd class="text-sm text-faint">Key + certificate</dd>
                        </div>
                        <div>
                            <dt class="text-2xl font-bold text-body">Free</dt>
                            <dd class="text-sm text-faint">No sign-up</dd>
                        </div>
                    </dl>
                </div>

                <!-- Right: converter card -->
                <div id="converter" class="card-elevated p-6 md:p-8 scroll-mt-24">
                    <div class="flex items-center gap-3 mb-6">
                        <span class="icon-tile w-10 h-10 gradient-bg" style="color:#fff;">
                            <svg class="icon" aria-hidden="true"><use href="#i-right-left"/></svg>
                        </span>
                        <h2 class="text-xl font-bold text-body">Convert a PFX file</h2>
                    </div>

                    <?php if (!empty($_SESSION['error'])): ?>
                        <div class="mb-6 rounded-lg border-base p-4 flex items-start gap-3"
                             style="background: var(--success-soft);" role="alert">
                            <svg class="icon mt-0.5" style="color:#dc2626;" aria-hidden="true"><use href="#i-circle-exclamation"/></svg>
                            <span class="text-body text-sm"><strong>Error:</strong> <?= htmlspecialchars($_SESSION['error']) ?></span>
                        </div>
                    <?php endif; ?>

                    <form action="process.php" method="post" enctype="multipart/form-data" class="space-y-5">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

                        <div id="dropzone" class="p-6 rounded-xl text-center">
                            <label class="custom-file-input w-full flex flex-col items-center justify-center">
                                <span class="icon-tile w-12 h-12 mb-3">
                                    <svg class="icon text-xl" aria-hidden="true"><use href="#i-file-arrow-up"/></svg>
                                </span>
                                <span class="text-body font-medium">Drop your .pfx file here or click to browse</span>
                                <span class="text-faint text-sm mt-1">Maximum file size: 10MB</span>
                                <input id="file" type="file" name="file" class="hidden" required accept=".pfx" aria-describedby="file-error">
                            </label>
                            <div id="file-name" class="mt-3 text-sm text-left"></div>
                            <p id="file-error" class="mt-3 text-sm text-left hidden" style="color:#dc2626;" role="alert"></p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-body mb-2" for="password">Password</label>
                            <div class="relative">
                                <input id="password" type="password" name="password" autocomplete="off"
                                       class="w-full px-4 py-3 rounded-lg bg-surface text-body border-base focus:outline-none"
                                       style="border-color: var(--border-strong);">
                                <button type="button" id="toggle-password"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-faint focus:outline-none"
                                        aria-label="Show password" aria-pressed="false">
                                    <svg class="icon" aria-hidden="true"><use href="#i-eye"/></svg>
                                </button>
                            </div>
                            <p class="mt-2 text-sm text-faint">The password that protects your .pfx file.</p>
                        </div>

                        <button type="submit" class="w-full btn-gradient font-semibold py-3 px-6 rounded-lg flex items-center justify-center gap-2">
                            <svg class="icon" aria-hidden="true"><use href="#i-download"/></svg>
                            Extract &amp; download PEM files
                        </button>

                        <p class="text-xs text-faint text-center flex items-center justify-center gap-1.5">
                            <svg class="icon" aria-hidden="true"><use href="#i-lock"/></svg>
                            Your file and password are never stored or logged.
                        </p>
                    </form>
                </div>
            </div>
        </section>

        <div class="container mx-auto max-w-6xl px-4">
            <!-- What happens to your file -->
            <section aria-labelledby="privacy-heading" class="surface-card p-6 md:p-8 -mt-8 md:-mt-10 relative z-10 mb-16">
                <div class="flex items-start gap-4">
                    <span class="icon-tile w-11 h-11 shrink-0">
                        <svg class="icon text-lg" aria-hidden="true"><use href="#i-user-shield"/></svg>
                    </span>
                    <div>
                        <h2 id="privacy-heading" class="text-lg font-semibold text-body mb-2">What happens to your file</h2>
                        <p class="text-muted">
                            Your <code class="text-brand">.pfx</code> file is uploaded over an encrypted (HTTPS) connection and processed in an
                            isolated, temporary folder on our server. The private key, certificate, and ZIP are generated, sent to you,
                            and then permanently deleted in the same request. We never store your file or your password, and nothing is logged.
                        </p>
                    </div>
                </div>
            </section>

            <!-- Trust stats -->
            <section class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-20">
                <div class="surface-card grow-on-hover p-6">
                    <span class="icon-tile w-12 h-12 mb-4"><svg class="icon text-lg" aria-hidden="true"><use href="#i-shield-halved"/></svg></span>
                    <h3 class="text-lg font-semibold text-body mb-1.5">Private by design</h3>
                    <p class="text-muted text-sm">Processed over a secure connection and permanently deleted the moment your download is ready — never stored.</p>
                </div>
                <div class="surface-card grow-on-hover p-6">
                    <span class="icon-tile w-12 h-12 mb-4"><svg class="icon text-lg" aria-hidden="true"><use href="#i-bolt"/></svg></span>
                    <h3 class="text-lg font-semibold text-body mb-1.5">Fast &amp; in-browser delivery</h3>
                    <p class="text-muted text-sm">Conversion happens server-side in milliseconds; your PEM files are ready to copy or download instantly.</p>
                </div>
                <div class="surface-card grow-on-hover p-6">
                    <span class="icon-tile w-12 h-12 mb-4"><svg class="icon text-lg" aria-hidden="true"><use href="#i-server"/></svg></span>
                    <h3 class="text-lg font-semibold text-body mb-1.5">Server ready</h3>
                    <p class="text-muted text-sm">Output works with Apache, Nginx, IIS, and other servers expecting PEM-formatted keys and certificates.</p>
                </div>
            </section>

            <!-- Features -->
            <section id="features" class="mb-20 scroll-mt-24 cv-section">
                <div class="max-w-2xl mb-10">
                    <span class="eyebrow mb-3"><svg class="icon" aria-hidden="true"><use href="#i-list-check"/></svg> Features</span>
                    <h2 class="text-3xl font-bold text-body">Everything you need to convert with confidence</h2>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="surface-card grow-on-hover p-6 flex items-start gap-4">
                        <span class="icon-tile w-11 h-11 shrink-0"><svg class="icon" aria-hidden="true"><use href="#i-lock"/></svg></span>
                        <div>
                            <h3 class="text-lg font-semibold text-body mb-1.5">Secure processing</h3>
                            <p class="text-muted text-sm">Sent over an encrypted connection, processed in an isolated temporary space, and deleted immediately after conversion. We never store your file or password.</p>
                        </div>
                    </div>
                    <div class="surface-card grow-on-hover p-6 flex items-start gap-4">
                        <span class="icon-tile w-11 h-11 shrink-0"><svg class="icon" aria-hidden="true"><use href="#i-wand-magic-sparkles"/></svg></span>
                        <div>
                            <h3 class="text-lg font-semibold text-body mb-1.5">Simple process</h3>
                            <p class="text-muted text-sm">Upload your PFX file, enter your password, and download the converted files. No command line, no technical skills required.</p>
                        </div>
                    </div>
                    <div class="surface-card grow-on-hover p-6 flex items-start gap-4">
                        <span class="icon-tile w-11 h-11 shrink-0"><svg class="icon" aria-hidden="true"><use href="#i-certificate"/></svg></span>
                        <div>
                            <h3 class="text-lg font-semibold text-body mb-1.5">Certificate &amp; key extraction</h3>
                            <p class="text-muted text-sm">Get both the private key and the certificate in PEM format, ready for immediate use with your web server.</p>
                        </div>
                    </div>
                    <div class="surface-card grow-on-hover p-6 flex items-start gap-4">
                        <span class="icon-tile w-11 h-11 shrink-0"><svg class="icon" aria-hidden="true"><use href="#i-file-zipper"/></svg></span>
                        <div>
                            <h3 class="text-lg font-semibold text-body mb-1.5">Instant downloads</h3>
                            <p class="text-muted text-sm">Copy each file or grab everything in a convenient ZIP package with properly named files.</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- How it works -->
            <section id="how-it-works" class="mb-20 scroll-mt-24 cv-section">
                <div class="max-w-2xl mb-10">
                    <span class="eyebrow mb-3"><svg class="icon" aria-hidden="true"><use href="#i-route"/></svg> How it works</span>
                    <h2 class="text-3xl font-bold text-body">Three steps to your PEM files</h2>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div class="surface-card p-6">
                        <span class="icon-tile w-11 h-11 mb-4"><svg class="icon" aria-hidden="true"><use href="#i-upload"/></svg></span>
                        <h3 class="text-base font-semibold text-body mb-1.5">1. Upload your file</h3>
                        <p class="text-muted text-sm">Upload your .pfx file through the secure form. It is processed in an isolated temporary space and deleted right after.</p>
                    </div>
                    <div class="surface-card p-6">
                        <span class="icon-tile w-11 h-11 mb-4"><svg class="icon" aria-hidden="true"><use href="#i-key"/></svg></span>
                        <h3 class="text-base font-semibold text-body mb-1.5">2. Enter password</h3>
                        <p class="text-muted text-sm">Enter the password associated with your .pfx file to unlock its contents.</p>
                    </div>
                    <div class="surface-card p-6">
                        <span class="icon-tile w-11 h-11 mb-4"><svg class="icon" aria-hidden="true"><use href="#i-download"/></svg></span>
                        <h3 class="text-base font-semibold text-body mb-1.5">3. Download files</h3>
                        <p class="text-muted text-sm">Receive your private key and certificate in PEM format — copy them or download as a ZIP.</p>
                    </div>
                </div>
                <div class="surface-card p-6" style="background: var(--brand-soft); border-color: transparent;">
                    <div class="flex items-center gap-2.5 mb-4">
                        <svg class="icon text-brand" aria-hidden="true"><use href="#i-lightbulb"/></svg>
                        <h3 class="text-base font-semibold text-body">Pro tips</h3>
                    </div>
                    <ul class="space-y-2.5 text-sm text-muted">
                        <li class="flex items-start gap-2.5">
                            <svg class="icon mt-0.5" style="color: var(--success);" aria-hidden="true"><use href="#i-circle-check"/></svg>
                            <span>Make sure you have the correct password for your PFX file before starting.</span>
                        </li>
                        <li class="flex items-start gap-2.5">
                            <svg class="icon mt-0.5" style="color: var(--success);" aria-hidden="true"><use href="#i-circle-check"/></svg>
                            <span>Keep your private key secure and never share it with unauthorized parties.</span>
                        </li>
                        <li class="flex items-start gap-2.5">
                            <svg class="icon mt-0.5" style="color: var(--success);" aria-hidden="true"><use href="#i-circle-check"/></svg>
                            <span>For web servers, you'll typically need both the certificate and the private key files.</span>
                        </li>
                    </ul>
                </div>
            </section>

            <!-- FAQ -->
            <section id="faq" class="mb-20 scroll-mt-24 cv-section">
                <div class="max-w-2xl mb-10">
                    <span class="eyebrow mb-3"><svg class="icon" aria-hidden="true"><use href="#i-circle-question"/></svg> FAQ</span>
                    <h2 class="text-3xl font-bold text-body">Frequently asked questions</h2>
                </div>
                <div class="surface-card divide-base">
                    <div class="p-6">
                        <button class="faq-toggle flex justify-between items-center w-full text-left gap-4">
                            <span class="text-base font-semibold text-body">What is a .pfx file?</span>
                            <svg class="icon text-faint transition-transform duration-300" aria-hidden="true"><use href="#i-chevron-down"/></svg>
                        </button>
                        <div class="faq-content text-muted text-sm">
                            <p class="pt-4">A .pfx file (Personal Information Exchange) is a PKCS#12 archive that stores the server certificate, any intermediate certificates, and the private key in a single encrypted file. It's commonly used for securing websites, email, and other resources that require SSL/TLS.</p>
                        </div>
                    </div>
                    <div class="p-6">
                        <button class="faq-toggle flex justify-between items-center w-full text-left gap-4">
                            <span class="text-base font-semibold text-body">Why do I need to convert PFX to PEM?</span>
                            <svg class="icon text-faint transition-transform duration-300" aria-hidden="true"><use href="#i-chevron-down"/></svg>
                        </button>
                        <div class="faq-content text-muted text-sm">
                            <p class="pt-4">Many web servers like Apache and Nginx, plus various applications, expect certificates and private keys in PEM format. Converting from PFX to PEM lets you use your certificate with these systems. PEM is a widely supported base64-encoded format.</p>
                        </div>
                    </div>
                    <div class="p-6">
                        <button class="faq-toggle flex justify-between items-center w-full text-left gap-4">
                            <span class="text-base font-semibold text-body">Why do I need a password to upload?</span>
                            <svg class="icon text-faint transition-transform duration-300" aria-hidden="true"><use href="#i-chevron-down"/></svg>
                        </button>
                        <div class="faq-content text-muted text-sm">
                            <p class="pt-4">The password is required to decrypt the contents of the .pfx file, which is encrypted to protect the sensitive private key it contains. Without the correct password, the file cannot be decrypted and the conversion will fail.</p>
                        </div>
                    </div>
                    <div class="p-6">
                        <button class="faq-toggle flex justify-between items-center w-full text-left gap-4">
                            <span class="text-base font-semibold text-body">Is my file stored on the server?</span>
                            <svg class="icon text-faint transition-transform duration-300" aria-hidden="true"><use href="#i-chevron-down"/></svg>
                        </button>
                        <div class="faq-content text-muted text-sm">
                            <p class="pt-4">No. Your file and all generated files are processed temporarily and deleted automatically in the same request. We do not store any of your certificate files or private keys on our servers.</p>
                        </div>
                    </div>
                    <div class="p-6">
                        <button class="faq-toggle flex justify-between items-center w-full text-left gap-4">
                            <span class="text-base font-semibold text-body">What formats will I receive after conversion?</span>
                            <svg class="icon text-faint transition-transform duration-300" aria-hidden="true"><use href="#i-chevron-down"/></svg>
                        </button>
                        <div class="faq-content text-muted text-sm">
                            <p class="pt-4">You'll receive a ZIP containing <code class="text-brand">cert.pem</code> (your SSL/TLS certificate) and <code class="text-brand">private.pem</code> (your unencrypted private key), both in PEM format.</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Related Articles -->
            <section class="mb-20 scroll-mt-24 cv-section">
                <div class="max-w-2xl mb-8">
                    <span class="eyebrow mb-3"><svg class="icon" aria-hidden="true"><use href="#i-newspaper"/></svg> Guides</span>
                    <h2 class="text-3xl font-bold text-body">Related articles</h2>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <a href="/blogs/extract-ssl-certificate-from-pfx/" class="surface-card grow-on-hover p-6 block">
                        <span class="icon-tile w-12 h-12 mb-4"><svg class="icon text-lg" aria-hidden="true"><use href="#i-certificate"/></svg></span>
                        <h3 class="text-lg font-semibold text-body mb-2">How to extract SSL certificates from PFX: a complete guide</h3>
                        <p class="text-muted text-sm mb-4">Extract SSL certificates from a PFX file using OpenSSL — a step-by-step guide to converting your certificates quickly and securely.</p>
                        <span class="inline-flex items-center gap-2 text-brand font-medium text-sm">Read more <svg class="icon" aria-hidden="true"><use href="#i-arrow-right"/></svg></span>
                    </a>
                    <a href="/blogs/renew-ssl-certificate/" class="surface-card grow-on-hover p-6 block">
                        <span class="icon-tile w-12 h-12 mb-4"><svg class="icon text-lg" aria-hidden="true"><use href="#i-rotate"/></svg></span>
                        <h3 class="text-lg font-semibold text-body mb-2">How to renew your SSL certificates: a quick guide</h3>
                        <p class="text-muted text-sm mb-4">Renew your SSL certificates and keep your website secure — a quick, step-by-step guide for easy certificate renewal.</p>
                        <span class="inline-flex items-center gap-2 text-brand font-medium text-sm">Read more <svg class="icon" aria-hidden="true"><use href="#i-arrow-right"/></svg></span>
                    </a>
                </div>
            </section>
        </div>

        <!-- CTA -->
        <section class="gradient-bg scroll-mt-24">
            <div class="container mx-auto max-w-4xl px-4 py-16 text-center">
                <h2 class="text-3xl font-bold text-white mb-4">Ready to convert your PFX files?</h2>
                <p class="text-white/90 text-lg mb-8 max-w-2xl mx-auto">
                    Extract private keys and certificates from your PFX files in seconds. Free, secure, and nothing is stored.
                </p>
                <a href="#converter" class="inline-flex items-center gap-2 px-7 py-3.5 rounded-lg bg-white font-semibold shadow-lg" style="color: var(--brand-hover);">
                    <svg class="icon" aria-hidden="true"><use href="#i-arrow-up"/></svg> Convert now
                </a>
            </div>
        </section>
    </main>
<?php
require $_SERVER['DOCUMENT_ROOT'] . '/partials/footer.php';

unset($_SESSION['error']);
