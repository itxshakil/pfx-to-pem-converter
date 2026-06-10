# Contributing

Thanks for your interest in improving **PFX to PEM Converter**! Contributions of all kinds are welcome — bug reports, fixes, docs, and new features.

## Getting set up

```bash
git clone https://github.com/itxshakil/pfx-to-pem-converter.git
cd pfx-to-pem-converter
npm install
npm run watch        # rebuild CSS on change
php -S 127.0.0.1:8000
```

Open <http://127.0.0.1:8000>. You need **PHP 8.2+** with the `openssl` and `zip` extensions.

## Project notes

- **No framework, no Composer.** This is plain server-rendered PHP. The only build step is Tailwind CSS via npm.
- **Rebuild CSS after changing markup or classes.** Tailwind purges unused utilities from `index.php`, `**/*.php`, and `js/**/*.js`, so run `npm run build` (or keep `npm run watch` running) after any class change. Custom component styles live in `css/custom.css` and are not purged.
- **Icons** are inline SVG symbols in `partials/icons.php`, referenced with `<svg class="icon"><use href="#i-NAME"/></svg>`.
- **Security matters here.** The app sets a strict per-request CSP. Avoid inline event handlers and inline scripts without the nonce — they will be blocked.

## Making changes

1. Fork the repo and create a branch: `git checkout -b feat/short-description`.
2. Make your change, keeping it focused and small.
3. If you touched markup/JS classes, run `npm run build`.
4. Verify locally — convert a test `.pfx`, check the browser console for CSP violations.
5. Commit with a clear message and open a pull request describing **what** changed and **why**.

## Code style

- Match the surrounding style; keep diffs minimal.
- Prefer deletion over addition — simpler is better.
- PHP files must pass `php -l` (lint). JS must pass `node --check`. CI runs both.

## Reporting bugs

Open an [issue](https://github.com/itxshakil/pfx-to-pem-converter/issues) with steps to reproduce, what you expected, and what actually happened. **Never include a real private key, certificate, or PFX password** in an issue.
