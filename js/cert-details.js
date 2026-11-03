/*
 * Human-readable details for a certificate or private key in PEM: who it is for,
 * who issued it, validity, domain names, key type, serial and fingerprint.
 * Works from the ASN.1, because forge only builds certificate objects for RSA.
 */
(function (root) {
    'use strict';

    const { certificateFields, isSelfSigned } = typeof module === 'object' && module.exports
        ? require('./pfx-convert.js')
        : root.PfxConvert;

    const RSA_OID = '1.2.840.113549.1.1.1';
    const EC_OID = '1.2.840.10045.2.1';
    // forge does not know the curve OIDs.
    const CURVES = {
        '1.2.840.10045.3.1.7': 'P-256',
        '1.3.132.0.34': 'P-384',
        '1.3.132.0.35': 'P-521',
    };

    // BIT STRINGs stay raw (leading "unused bits" byte included) so key bytes can be compared.
    const parse = (forge, der) => forge.asn1.fromDer(der, { decodeBitStrings: false });
    const pemBody = (forge, pem) => forge.pem.decode(pem)[0].body;
    const toHex = (forge, bytes) => forge.util.bytesToHex(bytes).toUpperCase();
    const oidOf = (forge, node) => forge.asn1.derToOid(node.value);

    // forge hands back attribute values as raw bytes; UTF8String and BMPString need decoding.
    const text = (forge, value, type) => {
        if (type === forge.asn1.Type.UTF8) return forge.util.decodeUtf8(value);
        if (type === forge.asn1.Type.BMPSTRING) {
            let out = '';
            for (let i = 0; i < value.length; i += 2) {
                out += String.fromCharCode((value.charCodeAt(i) << 8) | value.charCodeAt(i + 1));
            }
            return out;
        }
        return value;
    };

    const readName = (forge, name) => {
        const out = {};
        for (const attribute of forge.pki.RDNAttributesAsArray(name)) {
            if (attribute.shortName && !(attribute.shortName in out)) {
                out[attribute.shortName] = text(forge, attribute.value, attribute.valueTagClass);
            }
        }
        return out;
    };

    const readTime = (forge, node) => (node.type === forge.asn1.Type.UTCTIME
        ? forge.asn1.utcTimeToDate(node.value)
        : forge.asn1.generalizedTimeToDate(node.value));

    // INTEGERs carry a leading zero byte when the top bit is set; OpenSSL prints the magnitude only.
    const unsigned = (bytes) => (bytes.length > 1 && bytes.charCodeAt(0) === 0 ? bytes.slice(1) : bytes);

    const bitLength = (bytes) => {
        const magnitude = unsigned(bytes);
        return (magnitude.length - 1) * 8 + magnitude.charCodeAt(0).toString(2).length;
    };

    // SubjectPublicKeyInfo's BIT STRING after the "unused bits" byte.
    const publicKeyBytes = (spki) => spki.value[1].value.slice(1);

    // RSAPublicKey is (n, e); RSAPrivateKey is (version, n, e, ...).
    const publicModulus = (forge, spki) => parse(forge, publicKeyBytes(spki)).value[0].value;
    const privateModulus = (forge, keyBytes) => parse(forge, keyBytes).value[1].value;

    /** "RSA 2048-bit" or "EC P-256". getModulus is only called for RSA keys. */
    const keyLabel = (forge, algorithm, getModulus) => {
        const oid = oidOf(forge, algorithm.value[0]);
        if (oid === RSA_OID) return `RSA ${bitLength(getModulus())}-bit`;
        if (oid === EC_OID) return `EC ${CURVES[oidOf(forge, algorithm.value[1])] || 'curve'}`;
        return 'Unknown key type';
    };

    // Websites carry DNS names, IPs and sometimes email addresses; forge has already formatted IPs.
    const readAltNames = (extension) => (extension ? extension.altNames : [])
        .map((name) => (name.type === 7 ? name.ip : name.value))
        .filter((name) => typeof name === 'string' && name !== '');

    /**
     * @returns {{subject: object, issuer: object, notBefore: Date, notAfter: Date, altNames: string[],
     *   serial: string, sha256: string, key: string, isCA: boolean, selfSigned: boolean}}
     */
    function describeCertificate(forge, pem) {
        const der = pemBody(forge, pem);
        const asn1 = parse(forge, der);
        const fields = certificateFields(forge, asn1);
        const extensions = fields.extensions ? forge.pki.certificateExtensionsFromAsn1(fields.extensions) : [];
        const extension = (name) => extensions.find((ext) => ext.name === name);
        const spki = fields.publicKeyInfo;

        return {
            subject: readName(forge, fields.subject),
            issuer: readName(forge, fields.issuer),
            notBefore: readTime(forge, fields.validity.value[0]),
            notAfter: readTime(forge, fields.validity.value[1]),
            altNames: readAltNames(extension('subjectAltName')),
            serial: toHex(forge, unsigned(fields.serial.value)),
            sha256: toHex(forge, forge.md.sha256.create().update(der).digest().getBytes()).match(/../g).join(':'),
            key: keyLabel(forge, spki.value[0], () => publicModulus(forge, spki)),
            isCA: Boolean(extension('basicConstraints') && extension('basicConstraints').cA),
            selfSigned: isSelfSigned(forge, asn1),
        };
    }

    // PKCS#8 PrivateKeyInfo: version, AlgorithmIdentifier, OCTET STRING holding the key itself.
    const readPrivateKey = (forge, pem) => {
        const info = parse(forge, pemBody(forge, pem));
        return { algorithm: info.value[1], key: info.value[2].value };
    };

    function describePrivateKey(forge, pem) {
        const { algorithm, key } = readPrivateKey(forge, pem);
        return keyLabel(forge, algorithm, () => privateModulus(forge, key));
    }

    /**
     * True when the key is the private half of the certificate's public key.
     * Null when it cannot tell: an EC key saved without its public point.
     */
    function keyMatchesCertificate(forge, keyPem, certPem) {
        const { algorithm, key } = readPrivateKey(forge, keyPem);
        const spki = certificateFields(forge, parse(forge, pemBody(forge, certPem))).publicKeyInfo;
        const keyOid = oidOf(forge, algorithm.value[0]);
        if (keyOid !== oidOf(forge, spki.value[0].value[0])) return false;

        if (keyOid === RSA_OID) {
            const privateKey = parse(forge, key).value;
            const publicKey = parse(forge, publicKeyBytes(spki)).value;
            return privateKey[1].value === publicKey[0].value && privateKey[2].value === publicKey[1].value;
        }

        // ECPrivateKey keeps its public point in the optional [1] field.
        const publicPoint = parse(forge, key).value
            .find((el) => el.tagClass === forge.asn1.Class.CONTEXT_SPECIFIC && el.type === 1);
        return publicPoint ? publicPoint.value[0].value.slice(1) === publicKeyBytes(spki) : null;
    }

    const api = { describeCertificate, describePrivateKey, keyMatchesCertificate };
    if (typeof module === 'object' && module.exports) {
        module.exports = api;
    } else {
        root.CertDetails = api;
    }
})(typeof window !== 'undefined' ? window : globalThis);
