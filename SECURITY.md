# Security Policy

## Reporting a vulnerability

If you discover a security vulnerability in PFX to PEM Converter, please report it **privately** — do not open a public issue.

- Use GitHub's [private security advisory](https://github.com/itxshakil/pfx-to-pem-converter/security/advisories/new) form, or
- Email **itxshakil@gmail.com** with the details.

Please include:

- A description of the issue and its impact.
- Steps to reproduce or a proof of concept.
- Any suggested remediation, if you have one.

You can expect an initial acknowledgement within a few days. Once the issue is confirmed and fixed, you'll be credited in the release notes unless you prefer to remain anonymous.

## Scope

This converter runs in the browser by default, so the file and password are not sent anywhere. A server fallback (`process.php`) handles files the browser cannot read, in temp storage that is deleted at the end of the request. Reports of particular interest include:

- Ways the file, key or password could leave the browser without the user choosing the server fallback.
- Ways uploaded files, keys, or passwords could be persisted, leaked, or accessed by another request.
- CSRF, CSP, or header-hardening bypasses.
- Path traversal or temp-file handling issues.
- Anything that could lead to remote code execution.

## A note for users

Your PFX password and private keys are sensitive. This tool converts them in your browser and never stores them. Even so, as a general practice, be careful with production private keys on any website you do not control. For maximum safety, you can self-host this project or use OpenSSL locally — see the [SSL guides](https://pfx-to-pem-converter.shakiltech.com).
