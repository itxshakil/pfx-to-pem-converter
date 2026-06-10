    <!-- Navigation -->
    <header class="sticky top-0 z-50 backdrop-blur-md border-b-base"
            style="background: color-mix(in srgb, var(--surface) 82%, transparent);">
        <div class="container mx-auto max-w-6xl px-4 h-16 flex justify-between items-center">
            <a href="/" class="flex items-center gap-2.5">
                <span class="icon-tile w-9 h-9 gradient-bg" style="color:#fff;">
                    <svg class="icon text-base" aria-hidden="true"><use href="#i-shield-halved"/></svg>
                </span>
                <span class="text-lg font-bold tracking-tight text-body">PFX&nbsp;to&nbsp;PEM</span>
            </a>

            <nav class="hidden md:flex items-center gap-8 text-sm font-medium">
                <a href="/#features" class="nav-link">Features</a>
                <a href="/#how-it-works" class="nav-link">How it works</a>
                <a href="/#faq" class="nav-link">FAQ</a>
            </nav>

            <div class="hidden md:flex items-center gap-4">
                <a href="https://github.com/itxshakil/pfx-to-pem-converter" target="_blank" rel="noopener"
                   class="inline-flex items-center gap-2 text-sm font-medium link-muted">
                    <svg class="icon text-base" aria-hidden="true"><use href="#i-github"/></svg> GitHub
                </a>
                <a href="/#converter" class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-semibold btn-gradient">
                    Convert a file
                </a>
            </div>

            <button id="mobile-menu-button" type="button" class="md:hidden focus:outline-hidden text-body"
                    aria-label="Open navigation menu" aria-expanded="false" aria-controls="mobile-menu">
                <svg class="icon text-lg" aria-hidden="true"><use href="#i-bars"/></svg>
            </button>
        </div>

        <!-- Mobile Menu -->
        <div id="mobile-menu" class="hidden md:hidden border-t-base bg-surface">
            <nav class="container mx-auto max-w-6xl px-4 py-3 text-sm font-medium flex flex-col">
                <a href="/#features" class="py-2.5 link-muted">Features</a>
                <a href="/#how-it-works" class="py-2.5 link-muted">How it works</a>
                <a href="/#faq" class="py-2.5 link-muted">FAQ</a>
                <a href="https://github.com/itxshakil/pfx-to-pem-converter" target="_blank" rel="noopener" class="py-2.5 link-muted">GitHub</a>
                <a href="/#converter" class="mt-2 inline-flex items-center justify-center px-4 py-2.5 rounded-lg text-sm font-semibold btn-gradient">
                    Convert a file
                </a>
            </nav>
        </div>
    </header>
