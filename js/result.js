/*
 * Copy, download and "Download all as .zip" for partials/result.php.
 * The ZIP is built here from the text on the page, so neither path needs the
 * server to create files.
 */
(function (root) {
    'use strict';

    const CRC_TABLE = (() => {
        const table = new Uint32Array(256);
        for (let n = 0; n < 256; n++) {
            let c = n;
            for (let k = 0; k < 8; k++) {
                c = c & 1 ? 0xedb88320 ^ (c >>> 1) : c >>> 1;
            }
            table[n] = c >>> 0;
        }
        return table;
    })();

    const crc32 = (bytes) => {
        let crc = 0xffffffff;
        for (let i = 0; i < bytes.length; i++) {
            crc = CRC_TABLE[(crc ^ bytes[i]) & 0xff] ^ (crc >>> 8);
        }
        return (crc ^ 0xffffffff) >>> 0;
    };

    /**
     * A store-only (uncompressed) ZIP. PEM files are a few KB, so compression is not worth a library.
     *
     * @param {{name: string, text: string}[]} files
     * @returns {Uint8Array}
     */
    function makeZip(files) {
        const encoder = new TextEncoder();
        const local = [];
        const central = [];
        let offset = 0;

        for (const file of files) {
            const name = encoder.encode(file.name);
            const data = encoder.encode(file.text);
            const crc = crc32(data);

            const header = new DataView(new ArrayBuffer(30));
            header.setUint32(0, 0x04034b50, true);
            header.setUint16(4, 20, true);
            header.setUint32(14, crc, true);
            header.setUint32(18, data.length, true);
            header.setUint32(22, data.length, true);
            header.setUint16(26, name.length, true);
            local.push(new Uint8Array(header.buffer), name, data);

            const entry = new DataView(new ArrayBuffer(46));
            entry.setUint32(0, 0x02014b50, true);
            entry.setUint16(4, 20, true);
            entry.setUint16(6, 20, true);
            entry.setUint32(16, crc, true);
            entry.setUint32(20, data.length, true);
            entry.setUint32(24, data.length, true);
            entry.setUint16(28, name.length, true);
            entry.setUint32(42, offset, true);
            central.push(new Uint8Array(entry.buffer), name);

            offset += 30 + name.length + data.length;
        }

        const centralSize = central.reduce((sum, part) => sum + part.length, 0);
        const end = new DataView(new ArrayBuffer(22));
        end.setUint32(0, 0x06054b50, true);
        end.setUint16(8, files.length, true);
        end.setUint16(10, files.length, true);
        end.setUint32(12, centralSize, true);
        end.setUint32(16, offset, true);

        const parts = [...local, ...central, new Uint8Array(end.buffer)];
        const zip = new Uint8Array(parts.reduce((sum, part) => sum + part.length, 0));
        let at = 0;
        for (const part of parts) {
            zip.set(part, at);
            at += part.length;
        }
        return zip;
    }

    if (typeof module === 'object' && module.exports) {
        module.exports = { makeZip };
        return;
    }

    const saveBlob = (blob, filename) => {
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        a.remove();
        URL.revokeObjectURL(url);
    };

    const flash = (btn, text) => {
        const label = btn.querySelector('.copy-label');
        if (!label) return;
        const original = label.textContent;
        label.textContent = text;
        setTimeout(() => { label.textContent = original; }, 2000);
    };

    const textOf = (id) => {
        const el = document.getElementById(id);
        return el ? el.textContent : '';
    };

    document.querySelectorAll('[data-copy]').forEach((btn) => {
        btn.addEventListener('click', async () => {
            try {
                await navigator.clipboard.writeText(textOf(btn.getAttribute('data-copy')));
                flash(btn, 'Copied!');
            } catch (err) {
                flash(btn, 'Press Ctrl+C');
            }
        });
    });

    document.querySelectorAll('[data-download]').forEach((btn) => {
        btn.addEventListener('click', () => {
            saveBlob(new Blob([textOf(btn.getAttribute('data-download'))], { type: 'application/x-pem-file' }),
                btn.getAttribute('data-filename'));
        });
    });

    const zipBtn = document.getElementById('download-zip');
    if (zipBtn) {
        zipBtn.addEventListener('click', () => {
            const files = [...document.querySelectorAll('[data-download]')]
                .map((btn) => ({ name: btn.getAttribute('data-filename'), text: textOf(btn.getAttribute('data-download')) }))
                .filter((file) => file.text !== '');
            saveBlob(new Blob([makeZip(files)], { type: 'application/zip' }), zipBtn.getAttribute('data-filename'));
        });
    }

    let forgeLoading = null;
    const loadForge = () => {
        if (root.forge) return Promise.resolve(root.forge);
        if (!forgeLoading) {
            forgeLoading = new Promise((resolve, reject) => {
                const script = document.createElement('script');
                script.src = '/js/vendor/forge.min.js';
                script.onload = () => resolve(root.forge);
                script.onerror = () => {
                    forgeLoading = null;
                    reject(new Error('forge failed to load'));
                };
                document.head.appendChild(script);
            });
        }
        return forgeLoading;
    };

    const pemBlocks = (text) => text.match(/-----BEGIN CERTIFICATE-----[\s\S]*?-----END CERTIFICATE-----\n?/g) || [];
    const formatDate = (date) => date.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
    const nameLine = (name) => [name.CN, name.O].filter(Boolean).join(', ') || 'Not stated';

    const expiry = (notAfter) => {
        const days = Math.ceil((notAfter - Date.now()) / 86400000);
        if (days < 0) return { state: 'bad', label: `Expired ${-days} day${days === -1 ? '' : 's'} ago` };
        if (days <= 30) return { state: 'warn', label: `Expires in ${days} day${days === 1 ? '' : 's'}` };
        return { state: 'good', label: `Valid for ${days} more days` };
    };

    // Everything below comes from the file, so it goes in as text, never as HTML.
    const row = (list, term, value, state) => {
        const dt = document.createElement('dt');
        dt.textContent = term;
        const dd = document.createElement('dd');
        dd.textContent = value;
        if (state) dd.dataset.state = state;
        list.append(dt, dd);
    };

    const certificateList = (cert) => {
        const list = document.createElement('dl');
        const status = expiry(cert.notAfter);
        row(list, 'Issued to', nameLine(cert.subject));
        row(list, 'Issued by', cert.selfSigned ? 'Itself (self-signed)' : nameLine(cert.issuer));
        if (cert.altNames.length) row(list, 'Domains', cert.altNames.join(', '));
        row(list, 'Valid', `${formatDate(cert.notBefore)} to ${formatDate(cert.notAfter)}`);
        row(list, 'Status', status.label, status.state);
        row(list, 'Key', cert.key);
        if (cert.isCA) row(list, 'Type', 'Certificate authority');
        row(list, 'Serial', cert.serial);
        row(list, 'SHA-256', cert.sha256);
        return list;
    };

    const fill = (id, nodes) => {
        const slot = document.querySelector(`[data-details-for="${id}"]`);
        if (!slot) return;
        slot.replaceChildren(...nodes);
        slot.hidden = nodes.length === 0;
    };

    /**
     * Fills the details under each card from the PEM text on the page. Details are a
     * bonus: if anything here fails, the PEM files above are still right, so stay quiet.
     */
    async function renderDetails() {
        const certPem = textOf('pem-cert');
        if (!certPem || !root.CertDetails) return;
        try {
            const forge = await loadForge();
            const details = root.CertDetails;
            const keyPem = textOf('pem-key');

            fill('pem-cert', [certificateList(details.describeCertificate(forge, certPem))]);
            fill('pem-ca', pemBlocks(textOf('pem-ca')).map((pem) => {
                const cert = details.describeCertificate(forge, pem);
                const group = document.createElement('div');
                const title = document.createElement('p');
                title.className = 'cert-details-title';
                title.textContent = cert.subject.CN || 'Certificate';
                group.append(title, certificateList(cert));
                return group;
            }));

            const keyList = document.createElement('dl');
            row(keyList, 'Key', details.describePrivateKey(forge, keyPem));
            const matches = details.keyMatchesCertificate(forge, keyPem, certPem);
            if (matches !== null) {
                row(keyList, 'Matches cert.pem', matches ? 'Yes' : 'No, this key belongs to a different certificate',
                    matches ? 'good' : 'bad');
            }
            fill('pem-key', [keyList]);
        } catch {
            // Leave the slots hidden.
        }
    }

    root.PfxResult = { loadForge, renderDetails };

    // The server fallback page arrives already filled in.
    const result = document.getElementById('result');
    if (result && !result.hidden) renderDetails();
})(typeof window !== 'undefined' ? window : globalThis);
