<?php

namespace ayhanerdm\Core\Tools;

use \RuntimeException;

class Crypto {
    private const string CIPHER_METHOD = 'aes-256-gcm';
    private const string MAGIC_HEADER = 'AYC';
    private const int VERSION = 1;
    private const int NONCE_LENGTH = 12;
    private const int TAG_LENGTH = 16;

    /**
     * Veriyi AES-256-GCM ile şifreler.
     *
     * Token biçimi:
     * [HEADER:3][VERSION:1][NONCE:12][TAG:16][CIPHERTEXT:N]
     *
     * Sonuç padding içermeyen URL-safe Base64 biçimindedir.
     *
     * @throws RuntimeException Gizli anahtar boşsa veya şifreleme başarısızsa.
     * @throws \Random\RandomException Güvenli rastgele veri üretilemezse.
     */
    public static function encrypt(string $plainText, string $secretKey): string
    {
        $key = self::deriveKey($secretKey, $_ENV['APP_SECRET_FORMAT'] ?? 'auto');

        $header = self::MAGIC_HEADER . chr(self::VERSION);
        $nonce = random_bytes(self::NONCE_LENGTH);
        $tag = '';

        $cipherText = openssl_encrypt(
            $plainText,
            self::CIPHER_METHOD,
            $key,
            OPENSSL_RAW_DATA,
            $nonce,
            $tag,
            $header,
            self::TAG_LENGTH
        );

        if($cipherText === false || strlen($tag) !== self::TAG_LENGTH) {
            throw new RuntimeException('Encryption failed.');
        }

        $payload = $header . $nonce . $tag . $cipherText;

        return rtrim(
            strtr(base64_encode($payload), '+/', '-_'),
            '='
        );
    }

    /**
     * Token'ın desteklenen zarf biçiminde olup olmadığını kontrol eder.
     *
     * Kriptografik doğrulama yapmaz.
     * $version yalnızca desteklenen bir sürüm tanınırsa doldurulur.
     */
    public static function hasEnvelope(string $token, ?int &$version = null): bool
    {
        $version = null;

        $data = self::decodeToken($token);

        if($data === false) return false;

        $magicLength = strlen(self::MAGIC_HEADER);
        $headerLength = $magicLength + 1;

        $minimumLength = $headerLength
            + self::NONCE_LENGTH
            + self::TAG_LENGTH;

        if(strlen($data) < $minimumLength) return false;

        if(substr($data, 0, $magicLength) !== self::MAGIC_HEADER) return false;

        $detectedVersion = ord($data[$magicLength]);

        if($detectedVersion !== self::VERSION) return false;

        $version = $detectedVersion;

        return true;
    }

    /**
     * Token'ın AYC başlığını taşıyıp taşımadığını kontrol eder.
     *
     * Sürümün desteklenip desteklenmediğini veya token'ın
     * kriptografik olarak geçerli olup olmadığını doğrulamaz.
     */
    public static function hasMagicHeader(string $token): bool
    {
        $data = self::decodeToken($token);

        return $data !== false
            && str_starts_with($data, self::MAGIC_HEADER);
    }

    /**
     * Şifreli token'ı çözer.
     *
     * Token tanınmıyorsa, sürüm desteklenmiyorsa,
     * anahtar boşsa veya GCM doğrulaması başarısızsa false döndürür.
     *
     * Boş metnin başarılı çözümleme sonucu '' olabilir.
     */
    public static function decrypt(string $encryptedToken, string $secretKey): string|false
    {
        $data = self::decodeToken($encryptedToken);

        if($data === false) return false;

        $magicLength = strlen(self::MAGIC_HEADER);
        $headerLength = $magicLength + 1;

        $minimumLength = $headerLength
            + self::NONCE_LENGTH
            + self::TAG_LENGTH;

        if(strlen($data) < $minimumLength) return false;

        if(substr($data, 0, $magicLength) !== self::MAGIC_HEADER) return false;

        $version = ord($data[$magicLength]);

        if($version !== self::VERSION) return false;

        try {
            $key = self::deriveKey($secretKey, $_ENV['APP_SECRET_FORMAT'] ?? 'auto');
        } catch(RuntimeException $e) {
            return false;
        }

        $header = substr($data, 0, $headerLength);
        $offset = $headerLength;

        $nonce = substr($data, $offset, self::NONCE_LENGTH);
        $offset += self::NONCE_LENGTH;

        $tag = substr($data, $offset, self::TAG_LENGTH);
        $offset += self::TAG_LENGTH;

        $cipherText = substr($data, $offset);

        return openssl_decrypt(
            $cipherText,
            self::CIPHER_METHOD,
            $key,
            OPENSSL_RAW_DATA,
            $nonce,
            $tag,
            $header
        );
    }

    /**
     * Gizli değerden 32 baytlık AES anahtarı türetir.
     *
     * Bu bir parola güçlendirme algoritması değildir.
     * secretKey yüksek entropili ve gizli bir değer olmalıdır.
     */
    private static function deriveKey(string $secretKey, string $format = 'auto'): string {
        if($secretKey === '') {
            throw new RuntimeException('The encryption secret must not be empty.');
        }

        if($format === 'hex') {
            if(strlen($secretKey) !== 64 || !ctype_xdigit($secretKey)) {
                throw new RuntimeException('The hexadecimal encryption secret must contain exactly 64 hexadecimal characters.');
            }

            return hex2bin($secretKey);
        }

        if($format === 'binary') {
            if(strlen($secretKey) !== 32) {
                throw new RuntimeException('The binary encryption secret must contain exactly 32 bytes.');
            }

            return $secretKey;
        }

        if($format === 'raw') {
            return hash('sha256', $secretKey, true);
        }

        if($format === 'auto') {
            if(strlen($secretKey) === 64 && ctype_xdigit($secretKey)) {
                return hex2bin($secretKey);
            }

            return hash('sha256', $secretKey, true);
        }

        throw new RuntimeException('Unsupported encryption secret format.');
    }


    /**
     * URL-safe Base64 token'ını doğrular ve çözer.
     *
     * Yalnızca padding içermeyen, kanonik Base64URL biçimini kabul eder.
     */
    private static function decodeToken(string $token): string|false
    {
        if($token === '') return false;

        if(!preg_match('/\A[A-Za-z0-9_-]+\z/', $token)) return false;

        $normalized = strtr($token, '-_', '+/');
        $remainder = strlen($normalized) % 4;

        if($remainder === 1) return false;

        if($remainder !== 0) {
            $normalized .= str_repeat('=', 4 - $remainder);
        }

        $data = base64_decode($normalized, true);

        if($data === false) return false;

        $canonical = rtrim(
            strtr(base64_encode($data), '+/', '-_'),
            '='
        );

        if($canonical !== $token) return false;

        return $data;
    }
}