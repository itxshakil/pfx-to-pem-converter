// Certificate details, chain order and key matching, checked against OpenSSL on fresh fixtures.
import { test, before, after } from 'node:test';
import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import { mkdtempSync, readFileSync, rmSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import { createRequire } from 'node:module';

const require = createRequire(import.meta.url);
globalThis.window ??= globalThis;
const forge = require('../js/vendor/forge.min.js');
const { convertPfx } = require('../js/pfx-convert.js');
const { describeCertificate, describePrivateKey, keyMatchesCertificate } = require('../js/cert-details.js');

let dir;
const openssl = (args) => execFileSync('openssl', args, { cwd: dir, encoding: 'utf8' }).replace(/\r\n/g, '\n');
const file = (name) => readFileSync(join(dir, name), 'utf8').replace(/\r\n/g, '\n');
const pemOf = (name) => openssl(['x509', '-in', name]);

before(() => {
    dir = mkdtempSync(join(tmpdir(), 'details-'));
    writeFileSync(join(dir, 'ca.ext'), 'basicConstraints=critical,CA:TRUE\nkeyUsage=keyCertSign,cRLSign\n');
    writeFileSync(join(dir, 'leaf.ext'), 'subjectAltName=DNS:example.test,DNS:www.example.test,IP:127.0.0.1\n');

    openssl(['req', '-x509', '-newkey', 'rsa:2048', '-nodes', '-keyout', 'root.key', '-out', 'root.crt', '-days', '30', '-subj', '/CN=Test Root/O=Test Org']);
    openssl(['req', '-newkey', 'rsa:2048', '-nodes', '-keyout', 'int.key', '-out', 'int.csr', '-subj', '/CN=Test Intermediate/O=Test Org']);
    openssl(['x509', '-req', '-in', 'int.csr', '-CA', 'root.crt', '-CAkey', 'root.key', '-CAcreateserial', '-out', 'int.crt', '-days', '20', '-extfile', 'ca.ext']);
    openssl(['req', '-newkey', 'rsa:3072', '-nodes', '-keyout', 'leaf.key', '-out', 'leaf.csr', '-subj', '/CN=example.test/O=Example Ltd/C=IN']);
    openssl(['x509', '-req', '-in', 'leaf.csr', '-CA', 'int.crt', '-CAkey', 'int.key', '-CAcreateserial', '-out', 'leaf.crt', '-days', '10', '-extfile', 'leaf.ext']);
    // Root before intermediate, the opposite of the order a server needs.
    writeFileSync(join(dir, 'bundle.crt'), file('root.crt') + file('int.crt'));
    openssl(['pkcs12', '-export', '-inkey', 'leaf.key', '-in', 'leaf.crt', '-certfile', 'bundle.crt', '-out', 'chain.pfx', '-passout', 'pass:secret']);

    openssl(['ecparam', '-name', 'secp384r1', '-genkey', '-noout', '-out', 'ec.key']);
    openssl(['req', '-new', '-x509', '-key', 'ec.key', '-out', 'ec.crt', '-days', '5', '-subj', '/CN=ec.test', '-addext', 'subjectAltName=DNS:ec.test']);
});

after(() => rmSync(dir, { recursive: true, force: true }));

const opensslDate = (name, flag) => new Date(openssl(['x509', '-in', name, '-noout', flag]).split('=')[1].trim());

test('the chain comes out leaf first: intermediate, then root, and fullchain skips the root', () => {
    const pem = convertPfx(forge, readFileSync(join(dir, 'chain.pfx')).toString('binary'), 'secret');

    assert.deepEqual(pem.chain, [pemOf('int.crt'), pemOf('root.crt')]);
    assert.equal(pem.fullchain, pemOf('leaf.crt') + pemOf('int.crt'));
});

test('an RSA leaf is described the way OpenSSL sees it', () => {
    const details = describeCertificate(forge, pemOf('leaf.crt'));

    assert.equal(details.subject.CN, 'example.test');
    assert.equal(details.subject.O, 'Example Ltd');
    assert.equal(details.subject.C, 'IN');
    assert.equal(details.issuer.CN, 'Test Intermediate');
    assert.deepEqual(details.altNames, ['example.test', 'www.example.test', '127.0.0.1']);
    assert.equal(details.notBefore.getTime(), opensslDate('leaf.crt', '-startdate').getTime());
    assert.equal(details.notAfter.getTime(), opensslDate('leaf.crt', '-enddate').getTime());
    assert.equal(details.serial, openssl(['x509', '-in', 'leaf.crt', '-noout', '-serial']).split('=')[1].trim());
    assert.equal(details.sha256, openssl(['x509', '-in', 'leaf.crt', '-noout', '-fingerprint', '-sha256']).split('=')[1].trim());
    assert.equal(details.key, 'RSA 3072-bit');
    assert.equal(details.isCA, false);
    assert.equal(details.selfSigned, false);
});

test('CA and EC certificates are recognised', () => {
    assert.equal(describeCertificate(forge, pemOf('int.crt')).isCA, true);
    assert.equal(describeCertificate(forge, pemOf('root.crt')).selfSigned, true);

    const ec = describeCertificate(forge, pemOf('ec.crt'));
    assert.equal(ec.key, 'EC P-384');
    assert.deepEqual(ec.altNames, ['ec.test']);
});

test('the private key is described and checked against its certificate', () => {
    const rsaKey = openssl(['pkey', '-in', 'leaf.key']);
    const ecKey = openssl(['pkey', '-in', 'ec.key']);

    assert.equal(describePrivateKey(forge, rsaKey), 'RSA 3072-bit');
    assert.equal(describePrivateKey(forge, ecKey), 'EC P-384');
    assert.equal(keyMatchesCertificate(forge, rsaKey, pemOf('leaf.crt')), true);
    assert.equal(keyMatchesCertificate(forge, ecKey, pemOf('ec.crt')), true);
    assert.equal(keyMatchesCertificate(forge, rsaKey, pemOf('int.crt')), false);
    assert.equal(keyMatchesCertificate(forge, ecKey, pemOf('leaf.crt')), false);
});
