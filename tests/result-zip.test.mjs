// The "Download all as .zip" archive must open in standard tools; Python's zipfile checks the CRCs.
import { test } from 'node:test';
import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import { mkdtempSync, rmSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import { createRequire } from 'node:module';

const { makeZip } = createRequire(import.meta.url)('../js/result.js');

test('the zip opens and holds each file unchanged', () => {
    const dir = mkdtempSync(join(tmpdir(), 'zip-'));
    const path = join(dir, 'certs.zip');
    const files = [
        { name: 'private.pem', text: '-----BEGIN PRIVATE KEY-----\nabc\n-----END PRIVATE KEY-----\n' },
        { name: 'cert.pem', text: '-----BEGIN CERTIFICATE-----\nxyz é\n-----END CERTIFICATE-----\n' },
    ];

    try {
        writeFileSync(path, makeZip(files));
        const python = process.platform === 'win32' ? 'python' : 'python3';
        const listing = execFileSync(python, ['-c', [
            'import json, sys, zipfile',
            'z = zipfile.ZipFile(sys.argv[1])',
            'assert z.testzip() is None',
            'print(json.dumps({n: z.read(n).decode("utf-8") for n in z.namelist()}))',
        ].join('\n'), path], { encoding: 'utf8' });

        assert.deepEqual(JSON.parse(listing), Object.fromEntries(files.map((f) => [f.name, f.text])));
    } finally {
        rmSync(dir, { recursive: true, force: true });
    }
});
