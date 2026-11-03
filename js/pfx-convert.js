/*
 * Converts a .pfx (PKCS#12) file to PEM entirely in the browser, so the file and
 * password never leave the device. Produces what process.php returns: a PKCS#8
 * private key, the leaf certificate and the CA chain.
 *
 * Plain script with no build step: the page gets window.PfxConvert, the Node
 * tests get module.exports.
 */
(function (root) {
    'use strict';

    const fail = (code, message) => Object.assign(new Error(message), { code });

    // forge writes PEM with CRLF; OpenSSL and the server path use LF.
    const toPem = (forge, type, asn1) =>
        forge.pem.encode({ type, body: forge.asn1.toDer(asn1).getBytes() }).replace(/\r\n/g, '\n');

    const bagsOf = (p12, type) => p12.getBags({ bagType: type })[type] || [];

    const localKeyId = (bag) => {
        const ids = bag.attributes && bag.attributes.localKeyId;
        return ids && ids.length ? ids[0] : null;
    };

    /**
     * forge only builds key and certificate objects for RSA. For anything else
     * (EC keys, for one) it leaves the raw ASN.1 on the bag, which is all PEM needs.
     */
    const keyAsn1 = (forge, bag) =>
        bag.key ? forge.pki.wrapRsaPrivateKey(forge.pki.privateKeyToAsn1(bag.key)) : bag.asn1;

    const certAsn1 = (forge, bag) => (bag.cert ? forge.pki.certificateToAsn1(bag.cert) : bag.asn1);

    // The leaf is the certificate that belongs to the key. Position in the file
    // means nothing: some exporters put the CA first.
    const findLeaf = (keyBag, certBags) => {
        const keyId = localKeyId(keyBag);
        const byId = keyId === null ? -1 : certBags.findIndex((bag) => localKeyId(bag) === keyId);
        if (byId !== -1) return byId;

        if (keyBag.key) {
            const modulus = keyBag.key.n.toString(16);
            const byKey = certBags.findIndex((bag) => bag.cert && bag.cert.publicKey.n
                && bag.cert.publicKey.n.toString(16) === modulus);
            if (byKey !== -1) return byKey;
        }

        return 0;
    };

    /**
     * @param {object} forge  node-forge
     * @param {string} der    the .pfx file as a binary string
     * @param {string} password
     * @returns {{key: string, cert: string, chain: string[]}}
     * @throws {Error} with code "password", "unsupported" or "no-key"
     */
    function convertPfx(forge, der, password) {
        let p12;
        try {
            p12 = forge.pkcs12.pkcs12FromAsn1(forge.asn1.fromDer(der), false, password);
        } catch (error) {
            if (/MAC could not be verified/i.test(String(error && error.message))) {
                throw fail('password', 'The password is not correct.');
            }
            throw fail('unsupported', 'This file could not be read in the browser.');
        }

        const oids = forge.pki.oids;
        const keyBags = bagsOf(p12, oids.pkcs8ShroudedKeyBag).concat(bagsOf(p12, oids.keyBag));
        const certBags = bagsOf(p12, oids.certBag);
        if (keyBags.length === 0 || certBags.length === 0) {
            throw fail('no-key', 'This file does not contain both a private key and a certificate.');
        }

        const keyBag = keyBags[0];
        const leafIndex = findLeaf(keyBag, certBags);

        return {
            key: toPem(forge, 'PRIVATE KEY', keyAsn1(forge, keyBag)),
            cert: toPem(forge, 'CERTIFICATE', certAsn1(forge, certBags[leafIndex])),
            chain: certBags
                .filter((_, i) => i !== leafIndex)
                .map((bag) => toPem(forge, 'CERTIFICATE', certAsn1(forge, bag))),
        };
    }

    if (typeof module === 'object' && module.exports) {
        module.exports = { convertPfx };
    } else {
        root.PfxConvert = { convertPfx };
    }
})(typeof window !== 'undefined' ? window : globalThis);
