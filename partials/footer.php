    <!-- Footer -->
    <footer class="mt-24 border-t-base bg-surface-2">
        <div class="container mx-auto max-w-6xl px-4 py-14 text-sm">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-10 mb-12">
                <div class="col-span-2 md:col-span-1">
                    <a href="/" class="flex items-center gap-2.5 mb-3">
                        <span class="icon-tile w-8 h-8 gradient-bg" style="color:#fff;">
                            <svg class="icon text-sm" aria-hidden="true"><use href="#i-shield-halved"/></svg>
                        </span>
                        <span class="font-bold tracking-tight text-body">PFX&nbsp;to&nbsp;PEM</span>
                    </a>
                    <p class="text-muted leading-relaxed">
                        A free, focused tool to extract private keys and certificates from PFX files in PEM format.
                    </p>
                </div>
                <div>
                    <h4 class="font-semibold text-body mb-3">Links</h4>
                    <ul class="space-y-2.5">
                        <li><a href="/" class="link-muted">Home</a></li>
                        <li><a href="/#features" class="link-muted">Features</a></li>
                        <li><a href="/#how-it-works" class="link-muted">How it works</a></li>
                        <li><a href="/#faq" class="link-muted">FAQ</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-semibold text-body mb-3">Articles</h4>
                    <ul class="space-y-2.5">
                        <li><a href="/blogs/extract-ssl-certificate-from-pfx/" class="link-muted">Extract SSL certificate from PFX</a></li>
                        <li><a href="/blogs/install-ssl-apache/" class="link-muted">Install SSL on Apache</a></li>
                        <li><a href="/blogs/renew-ssl-certificate/" class="link-muted">Renew SSL certificate</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-semibold text-body mb-3">Connect</h4>
                    <div class="flex gap-5 text-lg">
                        <a href="https://www.x.com/itxshakil/" aria-label="X (Twitter)" class="link-muted">
                            <svg class="icon" aria-hidden="true"><use href="#i-twitter"/></svg>
                        </a>
                        <a href="https://www.github.com/itxshakil/" aria-label="GitHub" class="link-muted">
                            <svg class="icon" aria-hidden="true"><use href="#i-github"/></svg>
                        </a>
                        <a href="https://www.linkedin.com/in/itxshakil/" aria-label="LinkedIn" class="link-muted">
                            <svg class="icon" aria-hidden="true"><use href="#i-linkedin"/></svg>
                        </a>
                    </div>
                </div>
            </div>

            <div class="border-t-base pt-6 flex flex-col sm:flex-row justify-between items-center gap-3 text-muted">
                <p>Built by <a href="https://shakiltech.com?utm_source=pfx2pem" class="text-body font-medium link-muted">Shakil Alam</a></p>
                <p>&copy; <?= date('Y') ?> PFX to PEM Converter</p>
            </div>
        </div>
    </footer>
    <div class="fixed bottom-0 end-0 p-4">
        <button id="shareButton" type="button" aria-label="Share this tool" class="inline-flex items-center px-4 py-2.5 rounded-full btn-secondary text-sm font-medium shadow-lg">
            <svg class="icon mr-2" aria-hidden="true"><use href="#i-share"/></svg> Share
        </button>
    </div>
</div>

<script src="/js/app.js" defer></script>
</body>
</html>
