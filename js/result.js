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
            const files = [
                { name: 'private.pem', text: textOf('pem-key') },
                { name: 'cert.pem', text: textOf('pem-cert') },
                { name: 'ca-chain.pem', text: textOf('pem-ca') },
            ].filter((file) => file.text !== '');
            saveBlob(new Blob([makeZip(files)], { type: 'application/zip' }), zipBtn.getAttribute('data-filename'));
        });
    }
})(typeof window !== 'undefined' ? window : globalThis);
