/*
 * Converts the chosen .pfx in the browser and shows the result in place, so the
 * file and password are never sent anywhere. The form still posts to process.php
 * when JS is off, and the server is offered (behind an explicit button) only for
 * files the browser cannot read. A wrong password is never retried on the server.
 */
(function () {
    'use strict';

    const form = document.getElementById('convert-form');
    const result = document.getElementById('result');
    if (!form || !result || !window.PfxConvert || !window.PfxResult) return;

    const fileInput = document.getElementById('file');
    const password = document.getElementById('password');
    const submit = form.querySelector('[type="submit"]');
    const errorBox = document.getElementById('convert-error');
    const errorText = document.getElementById('convert-error-text');
    const fallback = document.getElementById('server-fallback');

    const { loadForge, renderDetails } = window.PfxResult;

    // Start the download as soon as there is a file, so the click itself feels instant.
    fileInput.addEventListener('change', () => {
        hideError();
        if (fileInput.files.length) loadForge().catch(() => {});
    });
    password.addEventListener('input', () => password.removeAttribute('aria-invalid'));

    function hideError() {
        errorBox.hidden = true;
        fallback.hidden = true;
        password.removeAttribute('aria-invalid');
    }

    function showError(message, { offerServer = false, field = null } = {}) {
        errorText.textContent = message;
        fallback.hidden = !offerServer;
        errorBox.hidden = false;
        if (field) {
            field.setAttribute('aria-invalid', 'true');
            field.focus();
        }
    }

    // Same naming as the server path: <file>_cert_<YYYYMMDD_HHMMSS>.zip
    const zipName = (fileName) => {
        const base = fileName.replace(/\.[^.]*$/, '').replace(/[^a-zA-Z0-9\-_ ]/g, '').replace(/ /g, '_') || 'certificates';
        const now = new Date();
        const pad = (n) => String(n).padStart(2, '0');
        const stamp = `${now.getFullYear()}${pad(now.getMonth() + 1)}${pad(now.getDate())}_`
            + `${pad(now.getHours())}${pad(now.getMinutes())}${pad(now.getSeconds())}`;
        return `${base}_cert_${stamp}.zip`;
    };

    function showResult(pem, fileName) {
        document.getElementById('pem-key').textContent = pem.key;
        document.getElementById('pem-cert').textContent = pem.cert;
        // With no intermediates the fullchain is just cert.pem again, so its card stays hidden.
        document.getElementById('pem-full').textContent = pem.fullchain === pem.cert ? '' : pem.fullchain;
        document.getElementById('pem-ca').textContent = pem.chain.join('');
        result.querySelectorAll('[data-pem-card]').forEach((card) => {
            card.hidden = document.getElementById(card.dataset.pemCard).textContent === '';
        });
        document.getElementById('download-zip').setAttribute('data-filename', zipName(fileName));

        result.hidden = false;
        result.focus();
        result.scrollIntoView({ block: 'start' });
        renderDetails();
    }

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        hideError();

        const file = fileInput.files[0];
        if (!file) return;

        submit.disabled = true;
        submit.setAttribute('aria-busy', 'true');
        try {
            const [forge, buffer] = await Promise.all([loadForge(), file.arrayBuffer()]);
            const pem = window.PfxConvert.convertPfx(forge, forge.util.createBuffer(buffer), password.value);
            // The key is on the page now; the password has done its job.
            password.value = '';
            showResult(pem, file.name);
        } catch (error) {
            if (error.code === 'password') {
                showError('That password does not open this file. Check it and try again.', { field: password });
            } else if (error.code === 'no-key') {
                showError('This file does not contain both a private key and a certificate, so there is nothing to convert.');
            } else {
                showError('Your browser could not read this file. The password may be wrong, or the file may use a format only our server can open.', { offerServer: true });
            }
        } finally {
            submit.disabled = false;
            submit.removeAttribute('aria-busy');
        }
    });

    // form.submit() skips the submit event, so this goes straight to process.php.
    document.getElementById('server-fallback-button').addEventListener('click', () => form.submit());
})();
