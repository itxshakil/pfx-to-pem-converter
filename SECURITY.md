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

This is a stateless converter that processes uploads in memory/temp storage and deletes them immediately. Reports of particular interest include:

- Ways uploaded files, keys, or passwords could be persisted, leaked, or accessed by another request.
- CSRF, CSP, or header-hardening bypasses.
- Path traversal or temp-file handling issues.
- Anything that could lead to remote code execution.

## A note for users

Your PFX password and private keys are sensitive. This tool never stores them, but as a general practice, avoid uploading production private keys to any online service you do not control. For maximum safety, you can self-host this project or use OpenSSL locally — see the [SSL guides](https://pfx-to-pem-converter.shakiltech.com).
