<?php
/**
 * Minimal WebAuthn (FIDO2) implementation for PHP.
 * Supports ES256 (P-256 ECDSA) platform authenticators.
 * No external dependencies required.
 */

function base64url_encode(string $data): string {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64url_decode(string $data): string {
    $pad = str_repeat('=', (4 - strlen($data) % 4) % 4);
    return base64_decode(strtr($data, '-_', '+/') . $pad);
}

/**
 * Minimal CBOR decoder.
 * Returns [decoded_value, next_offset].
 */
function cbor_decode(string $data, int $offset = 0): array {
    if ($offset >= strlen($data)) {
        throw new Exception('CBOR: unexpected end of data');
    }

    $byte          = ord($data[$offset]);
    $majorType     = ($byte & 0xe0) >> 5;
    $additionalInfo = $byte & 0x1f;
    $offset++;

    // Determine the integer value from the additional info field
    if ($additionalInfo < 24) {
        $value = $additionalInfo;
    } elseif ($additionalInfo === 24) {
        $value = ord($data[$offset]);
        $offset++;
    } elseif ($additionalInfo === 25) {
        $value = (ord($data[$offset]) << 8) | ord($data[$offset + 1]);
        $offset += 2;
    } elseif ($additionalInfo === 26) {
        $arr   = unpack('N', substr($data, $offset, 4));
        $value = $arr[1];
        $offset += 4;
    } elseif ($additionalInfo === 27) {
        // 8-byte unsigned — handle as two 32-bit halves (sufficient for our sizes)
        $hi = unpack('N', substr($data, $offset, 4))[1];
        $lo = unpack('N', substr($data, $offset + 4, 4))[1];
        $value = ($hi << 32) | $lo;
        $offset += 8;
    } else {
        throw new Exception('Unsupported CBOR additional info: ' . $additionalInfo);
    }

    switch ($majorType) {
        case 0: // Positive integer
            return [$value, $offset];

        case 1: // Negative integer: -1 - value
            return [-1 - $value, $offset];

        case 2: // Byte string
            $bytes = substr($data, $offset, $value);
            return [$bytes, $offset + $value];

        case 3: // Text string
            $str = substr($data, $offset, $value);
            return [$str, $offset + $value];

        case 4: // Array
            $arr = [];
            for ($i = 0; $i < $value; $i++) {
                [$item, $offset] = cbor_decode($data, $offset);
                $arr[] = $item;
            }
            return [$arr, $offset];

        case 5: // Map
            $map = [];
            for ($i = 0; $i < $value; $i++) {
                [$key,  $offset] = cbor_decode($data, $offset);
                [$val,  $offset] = cbor_decode($data, $offset);
                $map[$key] = $val;
            }
            return [$map, $offset];

        default:
            throw new Exception('Unsupported CBOR major type: ' . $majorType);
    }
}

/**
 * Parse the binary authenticatorData blob.
 * Returns an array with: rpIdHash, flags, signCount, credentialId, publicKeyCbor.
 */
function parseAuthenticatorData(string $authData): array {
    if (strlen($authData) < 37) {
        throw new Exception('authenticatorData is too short');
    }

    $rpIdHash  = substr($authData, 0, 32);
    $flags     = ord($authData[32]);
    $signCount = unpack('N', substr($authData, 33, 4))[1];

    $result = [
        'rpIdHash'      => $rpIdHash,
        'flags'         => $flags,
        'signCount'     => $signCount,
        'credentialId'  => null,
        'publicKeyCbor' => null,
    ];

    // Bit 6 (AT flag) = attested credential data included
    if ($flags & 0x40) {
        if (strlen($authData) < 55) {
            throw new Exception('authenticatorData missing attested credential data');
        }
        // bytes 37-52: AAGUID (16 bytes) — not needed, skip
        $credIdLen = unpack('n', substr($authData, 53, 2))[1];
        $result['credentialId']  = substr($authData, 55, $credIdLen);
        $result['publicKeyCbor'] = substr($authData, 55 + $credIdLen);
    }

    return $result;
}

/**
 * Convert a COSE EC2 P-256 public key (CBOR-encoded) to a PEM string
 * suitable for openssl_verify().
 */
function coseKeyToPem(string $cborKey): string {
    [$coseKey] = cbor_decode($cborKey);

    if (!is_array($coseKey)) {
        throw new Exception('Invalid COSE key: not a map');
    }

    $kty = $coseKey[1]  ?? null;   // 2 = EC2
    $crv = $coseKey[-1] ?? null;   // 1 = P-256
    $x   = $coseKey[-2] ?? null;   // x coordinate (32 bytes)
    $y   = $coseKey[-3] ?? null;   // y coordinate (32 bytes)

    if ($kty !== 2 || $crv !== 1 || strlen((string)$x) !== 32 || strlen((string)$y) !== 32) {
        throw new Exception('Only P-256 EC keys (kty=2, crv=1) are supported');
    }

    // Build SubjectPublicKeyInfo DER for P-256:
    //   SEQUENCE {
    //     SEQUENCE { OID ecPublicKey, OID P-256 }
    //     BIT STRING { 0x04 || x || y }
    //   }
    $der = hex2bin('3059301306072a8648ce3d020106082a8648ce3d030107034200') . "\x04" . $x . $y;

    return "-----BEGIN PUBLIC KEY-----\n"
        . chunk_split(base64_encode($der), 64, "\n")
        . "-----END PUBLIC KEY-----\n";
}

/**
 * Verify clientDataJSON fields against expected values.
 * $expectedChallenge: raw bytes
 * $expectedOrigin:    string, e.g. "https://example.com"
 * $expectedType:      "webauthn.create" or "webauthn.get"
 */
function verifyClientData(string $clientDataJSON, string $expectedChallenge, string $expectedOrigin, string $expectedType): void {
    $data = json_decode($clientDataJSON, true);
    if (!is_array($data)) {
        throw new Exception('clientDataJSON is not valid JSON');
    }
    if (($data['type'] ?? '') !== $expectedType) {
        throw new Exception('clientData type mismatch');
    }
    if (($data['origin'] ?? '') !== $expectedOrigin) {
        throw new Exception('clientData origin mismatch (expected: ' . $expectedOrigin . ', got: ' . ($data['origin'] ?? '') . ')');
    }
    $receivedChallenge = base64url_decode($data['challenge'] ?? '');
    if (!hash_equals($expectedChallenge, $receivedChallenge)) {
        throw new Exception('clientData challenge mismatch');
    }
}

/**
 * Verify a WebAuthn assertion signature.
 * $authDataRaw:    raw authenticatorData bytes
 * $clientDataJSON: raw clientDataJSON bytes
 * $signatureDer:  raw DER-encoded ECDSA signature bytes
 * $publicKeyPem:  PEM public key string
 */
function verifyAssertionSignature(string $authDataRaw, string $clientDataJSON, string $signatureDer, string $publicKeyPem): void {
    $clientDataHash = hash('sha256', $clientDataJSON, true);
    $verifyData     = $authDataRaw . $clientDataHash;
    $result         = openssl_verify($verifyData, $signatureDer, $publicKeyPem, OPENSSL_ALGO_SHA256);
    if ($result !== 1) {
        $err = openssl_error_string();
        throw new Exception('Signature verification failed' . ($err ? ': ' . $err : ''));
    }
}
