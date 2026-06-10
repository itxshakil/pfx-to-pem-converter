'use strict';

document.addEventListener('DOMContentLoaded', function () {
    const shareButton = document.getElementById('shareButton');
    if (!shareButton) return;

    // Check if Web Share API is supported
    if (navigator.share) {
        shareButton.addEventListener('click', async () => {
            try {
                await navigator.share({
                    title: '🔒 PFX to PEM Converter - Free Secure Tool',
                    text: 'A handy tool that converts PFX files to PEM format securely. Your file is processed over an encrypted connection and deleted right after — never stored. Perfect for setting up SSL on Apache, Nginx, or any web server.',
                    url: window.location.href
                });
            } catch (error) {
                console.error('Error sharing:', error);
            }
        });
    } else {
        // Fallback for browsers that don't support the Web Share API
        shareButton.addEventListener('click', () => {
            // Create a temporary input to copy the URL
            const input = document.createElement('input');
            input.value = window.location.href;
            document.body.appendChild(input);
            input.select();
            document.execCommand('copy');
            document.body.removeChild(input);

            // Change button text temporarily to show feedback
            const originalText = shareButton.innerHTML;
            shareButton.innerHTML = '<svg class="icon mr-2" aria-hidden="true"><use href="#i-check"/></svg> URL Copied!';

            setTimeout(() => {
                shareButton.innerHTML = originalText;
            }, 2000);
        });
    }
});

// Mobile menu toggle
const mobileMenuButton = document.getElementById('mobile-menu-button');
if (mobileMenuButton) {
    mobileMenuButton.addEventListener('click', function () {
        const mobileMenu = document.getElementById('mobile-menu');
        const isHidden = mobileMenu.classList.toggle('hidden');
        this.setAttribute('aria-expanded', String(!isHidden));
    });
}

// Password visibility toggle
const togglePassword = document.getElementById('toggle-password');
if (togglePassword) {
    togglePassword.addEventListener('click', function () {
        const passwordInput = document.getElementById('password');
        const use = this.querySelector('use');

        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            if (use) use.setAttribute('href', '#i-eye-slash');
            this.setAttribute('aria-pressed', 'true');
            this.setAttribute('aria-label', 'Hide password');
        } else {
            passwordInput.type = 'password';
            if (use) use.setAttribute('href', '#i-eye');
            this.setAttribute('aria-pressed', 'false');
            this.setAttribute('aria-label', 'Show password');
        }
    });
}

// File upload: drag-and-drop, client-side validation, and a removable file chip
(function () {
    const MAX_BYTES = 10 * 1024 * 1024; // keep in sync with process.php
    const fileInput = document.getElementById('file');
    const dropzone = document.getElementById('dropzone');
    const chip = document.getElementById('file-name');
    const errorEl = document.getElementById('file-error');

    // Only the converter page has the upload UI; bail out everywhere else.
    if (!fileInput || !dropzone || !chip || !errorEl) return;

    const formatSize = (bytes) => {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
    };

    const showError = (msg) => {
        errorEl.textContent = msg;
        errorEl.classList.remove('hidden');
    };
    const clearError = () => {
        errorEl.textContent = '';
        errorEl.classList.add('hidden');
    };

    const clearFile = () => {
        fileInput.value = '';
        chip.innerHTML = '';
    };

    const renderChip = (file) => {
        chip.innerHTML = '';
        const wrap = document.createElement('div');
        wrap.className = 'py-2 px-3 bg-blue-50 dark:bg-blue-900 text-blue-700 dark:text-blue-300 rounded-lg flex items-center justify-between gap-3';
        const label = document.createElement('span');
        label.className = 'flex items-center min-w-0';
        label.innerHTML = '<svg class="icon mr-2" aria-hidden="true"><use href="#i-file-alt"/></svg>';
        const name = document.createElement('span');
        name.className = 'truncate';
        name.textContent = `${file.name} (${formatSize(file.size)})`;
        label.appendChild(name);
        const remove = document.createElement('button');
        remove.type = 'button';
        remove.className = 'shrink-0 text-blue-700 dark:text-blue-300 hover:text-red-600 focus:outline-hidden';
        remove.setAttribute('aria-label', 'Remove selected file');
        remove.innerHTML = '<svg class="icon" aria-hidden="true"><use href="#i-times"/></svg>';
        remove.addEventListener('click', () => { clearFile(); clearError(); });
        wrap.appendChild(label);
        wrap.appendChild(remove);
        chip.appendChild(wrap);
    };

    const validate = (file) => {
        if (!file.name.toLowerCase().endsWith('.pfx')) {
            return 'Only .pfx files are allowed.';
        }
        if (file.size > MAX_BYTES) {
            return `That file is ${formatSize(file.size)}. The maximum size is 10MB.`;
        }
        return null;
    };

    const handleFile = (file) => {
        if (!file) return;
        const problem = validate(file);
        if (problem) {
            showError(problem);
            clearFile();
            return;
        }
        clearError();
        renderChip(file);
    };

    fileInput.addEventListener('change', function () {
        handleFile(this.files[0]);
    });

    ['dragenter', 'dragover'].forEach(evt =>
        dropzone.addEventListener(evt, (e) => {
            e.preventDefault();
            dropzone.classList.add('is-dragover');
        })
    );
    ['dragleave', 'dragend'].forEach(evt =>
        dropzone.addEventListener(evt, () => dropzone.classList.remove('is-dragover'))
    );
    dropzone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropzone.classList.remove('is-dragover');
        const dropped = e.dataTransfer.files && e.dataTransfer.files[0];
        if (!dropped) return;
        // Assign the dropped file to the input so it submits with the form
        const dt = new DataTransfer();
        dt.items.add(dropped);
        fileInput.files = dt.files;
        handleFile(dropped);
    });
})();

// FAQ accordion with ARIA state
document.querySelectorAll('.faq-toggle').forEach((toggle, i) => {
    const content = toggle.nextElementSibling;
    const panelId = `faq-panel-${i}`;
    content.id = panelId;
    toggle.setAttribute('aria-expanded', 'false');
    toggle.setAttribute('aria-controls', panelId);

    toggle.addEventListener('click', function () {
        const icon = this.querySelector('svg, i');
        const isOpen = !!content.style.maxHeight;

        if (isOpen) {
            content.style.maxHeight = null;
            icon.classList.remove('rotate-180');
            this.setAttribute('aria-expanded', 'false');
        } else {
            content.style.maxHeight = content.scrollHeight + 'px';
            icon.classList.add('rotate-180');
            this.setAttribute('aria-expanded', 'true');
        }
    });
});

// Service Worker Registration
window.addEventListener('load', () => {
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw.js');
    }
});
