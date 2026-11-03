/*
 * Human-readable details for a certificate or private key in PEM: who it is for,
 * who issued it, validity, domain names, key type, serial and fingerprint.
 * Reads the ASN.1 directly, because forge only builds certificate objects for RSA.
 */
(function (root) {
    'use strict';

    const { certificateFields } = typeof module === 'object' && module.exports
        ? require('./pfx-convert.js')
        : root.PfxConvert;

    const OID = {
        rsa: '1.2.840.113549.1.1.1',
        ec: '1.2.840.10045.2.1',
        subjectAltName: '2.5.29.17',
        basicConstraints: '2.5.29.19',
    };
    const CURVES = {
        '1.2.840.10045.3.1.7': 'P-256',
        '1.3.132.0.34': 'P-384',
        '1.3.132.0.35': 'P-521',
    };
    const NAME_FIELDS = {
        '2.5.4.3': 'CN',
        '2.5.4.10': 'O',
        '2.5.4.11': 'OU',
        '2.5.4.6': 'C',
        '2.5.4.8': 'ST',
        '2.5.4.7': 'L',
        '1.2.840.113549.1.9.1': 'E',
    };

    // BIT STRINGs stay raw (leading "unused bits" byte included) so key bytes can be compared.
    const parse = (forge, der) => forge.asn1.fromDer(der, { decodeBitStrings: false });
    const pemBody = (forge, pem) => forge.pem.decode(pem)[0].body;
    const toHex = (forge, bytes) => forge.util.bytesToHex(bytes).toUpperCase();

    const text = (forge, node) => {
        if (node.type === forge.asn1.Type.UTF8) return forge.util.decodeUtf8(node.value);
        if (node.type === forge.asn1.Type.BMPSTRING) {
            let out = '';
            for (let i = 0; i < node.value.length; i += 2) {
                out += String.fromCharCode((node.value.charCodeAt(i) << 8) | node.value.charCodeAt(i + 1));
            }
            return out;
        }
        return node.value;
    };

    const readName = (forge, name) => {
        const out = {};
        for (const rdn of name.value) {
            for (const attribute of rdn.value) {
                const field = NAME_FIELDS[forge.asn1.derToOid(attribute.value[0].value)];
                if (field && !(field in out)) out[field] = text(forge, attribute.value[1]);
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

    /** "RSA 2048-bit" or "EC P-256" from an AlgorithmIdentifier plus the RSA modulus when there is one. */
    const keyLabel = (forge, algorithm, modulus) => {
        const oid = forge.asn1.derToOid(algorithm.value[0].value);
        if (oid === OID.rsa) return `RSA ${bitLength(modulus)}-bit`;
        if (oid === OID.ec) return `EC ${CURVES[forge.asn1.derToOid(algorithm.value[1].value)] || 'curve'}`;
        return 'Unknown key type';
    };

    // SubjectPublicKeyInfo's BIT STRING after the "unused bits" byte.
    const publicKeyBytes = (spki) => spki.value[1].value.slice(1);

    const rsaModulus = (forge, rsaPublicOrPrivateKey, index) => parse(forge, rsaPublicOrPrivateKey).value[index].value;

    const extensionValue = (forge, extensions, oid) => {
        if (!extensions) return null;
        for (const extension of extensions.value[0].value) {
            if (forge.asn1.derToOid(extension.value[0].value) === oid) {
                return parse(forge, extension.value[extension.value.length - 1].value);
            }
        }
        return null;
    };

    const ipAddress = (bytes) => {
        const octets = Array.from(bytes, (c) => c.charCodeAt(0));
        if (octets.length === 4) return octets.join('.');
        const groups = [];
        for (let i = 0; i < octets.length; i += 2) groups.push(((octets[i] << 8) | octets[i + 1]).toString(16));
        return groups.join(':');
    };

    // dNSName [2], iPAddress [7] and rfc822Name [1] cover what certificates for websites carry.
    const readAltNames = (forge, san) => (san ? san.value : []).flatMap((name) => {
        if (name.type === 2 || name.type === 1) return [name.value];
        if (name.type === 7) return [ipAddress(name.value)];
        return [];
    });

    /**
     * @returns {{subject: object, issuer: object, notBefore: Date, notAfter: Date, altNames: string[],
     *   serial: string, sha256: string, key: string, isCA: boolean, selfSigned: boolean}}
     */
    function describeCertificate(forge, pem) {
        const der = pemBody(forge, pem);
        const fields = certificateFields(forge, parse(forge, der));
        const spki = fields.publicKeyInfo;
        const isRsa = forge.asn1.derToOid(spki.value[0].value[0].value) === OID.rsa;
        const constraints = extensionValue(forge, fields.extensions, OID.basicConstraints);
        const firstConstraint = constraints && constraints.value[0];

        return {
            subject: readName(forge, fields.subject),
            issuer: readName(forge, fields.issuer),
            notBefore: readTime(forge, fields.validity.value[0]),
            notAfter: readTime(forge, fields.validity.value[1]),
            altNames: readAltNames(forge, extensionValue(forge, fields.extensions, OID.subjectAltName)),
            serial: toHex(forge, unsigned(fields.serial.value)),
            sha256: toHex(forge, forge.md.sha256.create().update(der).digest().getBytes()).match(/../g).join(':'),
            key: keyLabel(forge, spki.value[0], isRsa ? rsaModulus(forge, publicKeyBytes(spki), 0) : null),
            isCA: Boolean(firstConstraint && firstConstraint.type === forge.asn1.Type.BOOLEAN
                && firstConstraint.value.charCodeAt(0) !== 0),
            selfSigned: forge.asn1.toDer(fields.subject).getBytes() === forge.asn1.toDer(fields.issuer).getBytes(),
        };
    }

    // PKCS#8 PrivateKeyInfo: version, AlgorithmIdentifier, OCTET STRING holding the key itself.
    const readPrivateKey = (forge, pem) => {
        const info = parse(forge, pemBody(forge, pem));
        return { algorithm: info.value[1], key: info.value[2].value };
    };

    function describePrivateKey(forge, pem) {
        const { algorithm, key } = readPrivateKey(forge, pem);
        const isRsa = forge.asn1.derToOid(algorithm.value[0].value) === OID.rsa;
        return keyLabel(forge, algorithm, isRsa ? rsaModulus(forge, key, 1) : null);
    }

    /**
     * True when the key is the private half of the certificate's public key.
     * Null when it cannot tell: an EC key saved without its public point.
     */
    function keyMatchesCertificate(forge, keyPem, certPem) {
        const { algorithm, key } = readPrivateKey(forge, keyPem);
        const spki = certificateFields(forge, parse(forge, pemBody(forge, certPem))).publicKeyInfo;
        const keyOid = forge.asn1.derToOid(algorithm.value[0].value);
        if (keyOid !== forge.asn1.derToOid(spki.value[0].value[0].value)) return false;

        if (keyOid === OID.rsa) {
            // RSAPrivateKey is version, n, e, ...; RSAPublicKey is n, e.
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
