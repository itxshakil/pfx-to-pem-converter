/*
 * Converts a .pfx (PKCS#12) file to PEM entirely in the browser, so the file and
 * password never leave the device. Produces what process.php returns: a PKCS#8
 * private key, the leaf certificate and the CA chain, plus a fullchain for servers.
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

    /**
     * The TBSCertificate fields by name, read straight from the ASN.1 so EC
     * certificates work too. The version field is optional, hence the offset.
     */
    const certificateFields = (forge, asn1) => {
        const tbs = asn1.value[0].value;
        const at = tbs[0].tagClass === forge.asn1.Class.CONTEXT_SPECIFIC && tbs[0].type === 0 ? 1 : 0;
        return {
            serial: tbs[at],
            issuer: tbs[at + 2],
            validity: tbs[at + 3],
            subject: tbs[at + 4],
            publicKeyInfo: tbs[at + 5],
            extensions: tbs.slice(at + 6).find((el) => el.tagClass === forge.asn1.Class.CONTEXT_SPECIFIC && el.type === 3),
        };
    };

    // Subject and issuer as DER bytes, so names compare exactly.
    const names = (forge, asn1) => {
        const fields = certificateFields(forge, asn1);
        return {
            subject: forge.asn1.toDer(fields.subject).getBytes(),
            issuer: forge.asn1.toDer(fields.issuer).getBytes(),
        };
    };

    const isSelfSigned = (forge, asn1) => {
        const { subject, issuer } = names(forge, asn1);
        return subject === issuer;
    };

    /**
     * Walks issuer to subject from the leaf, so the chain comes out in the order
     * servers expect no matter how the file stored it. Certificates that do not
     * fit the walk go last, in file order.
     */
    const orderChain = (forge, leaf, certs) => {
        const remaining = certs.map((asn1) => ({ asn1, ...names(forge, asn1) }));
        const ordered = [];
        let issuer = names(forge, leaf).issuer;
        for (;;) {
            const next = remaining.findIndex((cert) => cert.subject === issuer);
            if (next === -1) break;
            const [cert] = remaining.splice(next, 1);
            ordered.push(cert.asn1);
            issuer = cert.issuer;
        }
        return ordered.concat(remaining.map((cert) => cert.asn1));
    };

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
     * @param {string|object} der  the .pfx file as a binary string or a forge buffer
     * @param {string} password
     * @returns {{key: string, cert: string, chain: string[], fullchain: string}}
     * @throws {Error} with code "password", "unsupported" or "no-key"
     */
    function convertPfx(forge, der, password) {
        // PKCS#12 feeds the password to its MAC as UTF-16, which forge gets right, but
        // PBES2 (AES files) needs UTF-8 bytes and forge 1.4.2 passes the JS string as is.
        // Without this, any non-ASCII password fails on modern files. ASCII is unchanged.
        const pbe = forge.pki.pbe;
        const getCipherForPBES2 = pbe.getCipherForPBES2;
        pbe.getCipherForPBES2 = (oid, params, pass) => getCipherForPBES2(oid, params, forge.util.encodeUtf8(pass));

        let p12;
        try {
            p12 = forge.pkcs12.pkcs12FromAsn1(forge.asn1.fromDer(der), false, password);
        } catch (error) {
            if (/MAC could not be verified/i.test(String(error && error.message))) {
                throw fail('password', 'The password is not correct.');
            }
            throw fail('unsupported', 'This file could not be read in the browser.');
        } finally {
            pbe.getCipherForPBES2 = getCipherForPBES2;
        }

        const oids = forge.pki.oids;
        const keyBags = bagsOf(p12, oids.pkcs8ShroudedKeyBag).concat(bagsOf(p12, oids.keyBag));
        const certBags = bagsOf(p12, oids.certBag);
        if (keyBags.length === 0 || certBags.length === 0) {
            throw fail('no-key', 'This file does not contain both a private key and a certificate.');
        }

        const keyBag = keyBags[0];
        const leafIndex = findLeaf(keyBag, certBags);
        const leaf = certAsn1(forge, certBags[leafIndex]);
        const chain = orderChain(forge, leaf, certBags
            .filter((_, i) => i !== leafIndex)
            .map((bag) => certAsn1(forge, bag)));
        const certPem = (asn1) => toPem(forge, 'CERTIFICATE', asn1);
        const intermediates = chain.filter((cert) => !isSelfSigned(forge, cert));

        return {
            key: toPem(forge, 'PRIVATE KEY', keyAsn1(forge, keyBag)),
            cert: certPem(leaf),
            chain: chain.map(certPem),
            // What nginx's ssl_certificate wants: leaf plus intermediates. Browsers already trust the root.
            // Empty when there are no intermediates, since it would only repeat cert.
            fullchain: intermediates.length ? [leaf].concat(intermediates).map(certPem).join('') : '',
        };
    }

    if (typeof module === 'object' && module.exports) {
        module.exports = { convertPfx, certificateFields, isSelfSigned };
    } else {
        root.PfxConvert = { convertPfx, certificateFields, isSelfSigned };
    }
})(typeof window !== 'undefined' ? window : globalThis);
