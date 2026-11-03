// Checks the in-browser converter against OpenSSL, byte for byte, on .pfx files
// generated fresh for each run (no private keys are committed).
import { test, before, after } from 'node:test';
import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import { mkdtempSync, readFileSync, rmSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import { createRequire } from 'node:module';

const require = createRequire(import.meta.url);
// The dist bundle is built for browsers and reads `window` at load time. Testing the
// vendored file itself, rather than the npm entry point, checks the bytes that ship.
globalThis.window ??= globalThis;
const forge = require('../js/vendor/forge.min.js');
// Only for building fixtures: the browser bundle has no random source under Node.
const nodeForge = require('node-forge');
const { convertPfx } = require('../js/pfx-convert.js');

let dir;
// Git for Windows ships an OpenSSL that writes CRLF; CI on Linux writes LF already.
const openssl = (args, input) =>
    execFileSync('openssl', args, { cwd: dir, encoding: 'utf8', input }).replace(/\r\n/g, '\n');
const passwordFile = (password) => {
    writeFileSync(join(dir, 'password.txt'), `${password}
`, 'utf8');
    return 'file:password.txt';
};
const pemBlocks = (text) => (text.match(/-----BEGIN [A-Z ]+-----[\s\S]*?-----END [A-Z ]+-----\n/g) || []);

before(() => {
    dir = mkdtempSync(join(tmpdir(), 'pfx-'));
    openssl(['req', '-x509', '-newkey', 'rsa:2048', '-nodes', '-keyout', 'ca.key', '-out', 'ca.crt', '-days', '2', '-subj', '/CN=Test CA']);
    openssl(['req', '-newkey', 'rsa:2048', '-nodes', '-keyout', 'leaf.key', '-out', 'leaf.csr', '-subj', '/CN=example.test']);
    openssl(['x509', '-req', '-in', 'leaf.csr', '-CA', 'ca.crt', '-CAkey', 'ca.key', '-CAcreateserial', '-out', 'leaf.crt', '-days', '2']);
    openssl(['ecparam', '-name', 'prime256v1', '-genkey', '-noout', '-out', 'ec.key']);
    openssl(['req', '-new', '-x509', '-key', 'ec.key', '-out', 'ec.crt', '-days', '2', '-subj', '/CN=ec.test']);

    const rsa = ['-inkey', 'leaf.key', '-in', 'leaf.crt', '-certfile', 'ca.crt'];
    const ec = ['-inkey', 'ec.key', '-in', 'ec.crt'];
    openssl(['pkcs12', '-export', ...rsa, '-out', 'rsa-aes.pfx', '-passout', 'pass:secret']);
    openssl(['pkcs12', '-export', '-legacy', ...rsa, '-out', 'rsa-legacy.pfx', '-passout', 'pass:secret']);
    openssl(['pkcs12', '-export', ...ec, '-out', 'ec-aes.pfx', '-passout', 'pass:secret']);
    openssl(['pkcs12', '-export', '-legacy', ...ec, '-out', 'ec-legacy.pfx', '-passout', 'pass:secret']);
    openssl(['pkcs12', '-export', '-inkey', 'leaf.key', '-in', 'leaf.crt', '-out', 'no-password.pfx', '-passout', 'pass:']);
    // Passwords go to OpenSSL through a UTF-8 file: on Windows, argv arrives in the ANSI code page.
    openssl(['pkcs12', '-export', ...rsa, '-out', 'unicode.pfx', '-passout', passwordFile('pässwörd')]);
    openssl(['pkcs12', '-export', '-legacy', ...rsa, '-out', 'unicode-legacy.pfx', '-passout', passwordFile('pässwörd')]);
});

after(() => rmSync(dir, { recursive: true, force: true }));

const read = (name) => readFileSync(join(dir, name)).toString('binary');

// What the server path (openssl_pkcs12_read) returns: PKCS#8 key, leaf cert, CA chain.
const expected = (name, pass) => {
    const pkcs12 = (...args) => openssl(['pkcs12', ...(name.includes('legacy') ? ['-legacy'] : []), '-in', name, '-passin', passwordFile(pass), ...args]);

    return {
        key: openssl(['pkey'], pkcs12('-nocerts', '-nodes')),
        cert: pemBlocks(pkcs12('-clcerts', '-nokeys')).join(''),
        chain: pemBlocks(pkcs12('-cacerts', '-nokeys')),
    };
};

for (const [name, pass] of [
    ['rsa-aes.pfx', 'secret'],
    ['rsa-legacy.pfx', 'secret'],
    ['ec-aes.pfx', 'secret'],
    ['ec-legacy.pfx', 'secret'],
    ['no-password.pfx', ''],
    ['unicode.pfx', 'pässwörd'],
    ['unicode-legacy.pfx', 'pässwörd'],
]) {
    test(`${name} converts to the same PEM files as OpenSSL`, () => {
        const result = convertPfx(forge, read(name), pass);
        const want = expected(name, pass);

        assert.equal(result.key, want.key);
        assert.match(result.key, /^-----BEGIN PRIVATE KEY-----\n/);
        assert.equal(result.cert, want.cert);
        assert.deepEqual(result.chain, want.chain);
    });
}

test('without a localKeyId, the leaf is found by its public key even when the CA comes first', () => {
    const pem = (name) => readFileSync(join(dir, name), 'utf8');
    const asn1 = nodeForge.pkcs12.toPkcs12Asn1(
        nodeForge.pki.privateKeyFromPem(pem('leaf.key')),
        [nodeForge.pki.certificateFromPem(pem('ca.crt')), nodeForge.pki.certificateFromPem(pem('leaf.crt'))],
        'secret',
        { algorithm: '3des', generateLocalKeyId: false },
    );

    const result = convertPfx(forge, nodeForge.asn1.toDer(asn1).getBytes(), 'secret');

    assert.equal(result.cert, pemBlocks(openssl(['x509', '-in', 'leaf.crt']))[0]);
    assert.deepEqual(result.chain, pemBlocks(openssl(['x509', '-in', 'ca.crt'])));
});

test('a wrong password is reported as such, not as an unsupported file', () => {
    assert.throws(() => convertPfx(forge, read('rsa-aes.pfx'), 'nope'), { code: 'password' });
});

test('a file that is not a pfx is reported as unsupported', () => {
    assert.throws(() => convertPfx(forge, 'definitely not der', 'secret'), { code: 'unsupported' });
});
