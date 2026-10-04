<?php
namespace ayhanerdm\Core\Tools;

class Crypto
{
    // Endüstri standardı şifreleme yöntemi
    private const CIPHER_METHOD = 'aes-256-cbc';

    /**
     * Düz metni şifreler ve URL/JSON uyumlu güvenli bir string döner.
     */
    public static function encrypt(string $plainText, string $secretKey): string
    {
        // 1. Algoritmanın gerektirdiği IV uzunluğunu al (AES-256-CBC için 16 bayt)
        $ivLength = openssl_cipher_iv_length(self::CIPHER_METHOD);

        // 2. Kriptografik olarak güvenli rastgele bir IV üret
        $iv = random_bytes($ivLength);

        // 3. Veriyi şifrele
        $encryptedRaw = openssl_encrypt(
            $plainText,
            self::CIPHER_METHOD,
            $secretKey,
            OPENSSL_RAW_DATA,
            $iv
        );

        if ($encryptedRaw === false) {
            throw new RuntimeException('Encryption failed.');
        }

        // 4. IV ile şifreli metni birleştir ve URL uyumlu (url-safe) Base64 yap
        // Böylece tek bir token içinde hem IV hem de veri taşınmış olur
        $payload = $iv . $encryptedRaw;

        return rtrim(strtr(base64_encode($payload), '+/', '-_'), '=');
    }

    /**
     * Şifrelenmiş token'ı çözer ve orijinal düz metni döner.
     * Değiştirilmişse veya anahtar yanlışsa false döner.
     */
    public static function decrypt(string $encryptedToken, string $secretKey): string|false
    {
        // 1. URL-Safe Base64'ü standart Base64 formatına çevir ve decode et
        $normalized = strtr($encryptedToken, '-_', '+/');
        $data = base64_decode($normalized, true);

        if ($data === false) {
            return false;
        }

        // 2. IV uzunluğunu belirle
        $ivLength = openssl_cipher_iv_length(self::CIPHER_METHOD);

        if (strlen($data) <= $ivLength) {
            return false; // Bozuk veya eksik veri
        }

        // 3. IV'yi ve asıl şifreli metni birbirinden ayır
        $iv = substr($data, 0, $ivLength);
        $cipherText = substr($data, $ivLength);

        // 4. Şifreyi çöz
        $decrypted = openssl_decrypt(
            $cipherText,
            self::CIPHER_METHOD,
            $secretKey,
            OPENSSL_RAW_DATA,
            $iv
        );

        return $decrypted; // Başarısız olursa false döner
    }
}