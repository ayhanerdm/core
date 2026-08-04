<?php
namespace ayhanerdm\Core\Tools;

class CSRFToken {

    /**
     * CSRF Token Yönetimi İçin Yardımcı Fonksiyonlar
     *
     * Bu fonksiyonlar, zaman kontrollü ve oturuma bağlı CSRF tokenları oluşturur,
     * doğrular ve yönetir.
     *
     * Kullanım:
     * 1. Dosyanın en başında session_start() fonksiyonunu çağırın.
     * 2. Formları gösterdiğiniz sayfada getCSRFToken() ile token'ı alın ve
     *    formunuza gizli bir input alanı olarak ekleyin.
     * 3. Form gönderildiğinde, işlemi yapmadan önce validateCSRFToken()
     *    ile token'ı doğrulayın.
     */

    // CSRF token'ının oturumda saklanacağı anahtar
    const CSRF_TOKEN_SESSION_KEY = 'csrf_token_data';

    // CSRF token'ının saniye cinsinden ömrü (varsayılan: 10 dakika)
    const CSRF_TOKEN_LIFETIME = 600; // 600 saniye = 10 dakika

    /**
     * Geçerli bir CSRF token'ı alır.
     * Eğer oturumda geçerli (süresi dolmamış) bir token yoksa yeni bir tane oluşturur.
     *
     * @return string Geçerli CSRF token değeri.
     * @throws Exception Kriptografik olarak güvenli rastgele bayt üretilemezse.
     */
    public static function getCSRFToken(): string
    {
        // Oturumda token verisi var mı kontrol et
        if (isset($_SESSION[self::CSRF_TOKEN_SESSION_KEY])) {
            $token_data = $_SESSION[self::CSRF_TOKEN_SESSION_KEY];

            // Token'ın süresi dolmuş mu kontrol et
            if (isset($token_data['timestamp']) && (time() - $token_data['timestamp']) < self::CSRF_TOKEN_LIFETIME) {
                // Süresi dolmamış geçerli bir token var, onu döndür
                return $token_data['token'];
            } else {
                // Süresi dolmuş, oturumdaki eski token'ı temizle
                unset($_SESSION[self::CSRF_TOKEN_SESSION_KEY]);
            }
        }

        // Yeni bir token oluştur
        try {
            // Kriptografik olarak güvenli rastgele bayt üret
            $token = bin2hex(random_bytes(32)); // 32 bayt = 64 hex karakter

            // Token'ı ve oluşturulma zamanını oturuma kaydet
            $_SESSION[self::CSRF_TOKEN_SESSION_KEY] = [
                'token' => $token,
                'timestamp' => time()
            ];

            // Yeni token'ı döndür
            return $token;
        } catch (Exception $e) {
            // Güvenli rastgele bayt üretilemedi, bu ciddi bir hata.
            // Üretim ortamında loglama yapılmalı ve kullanıcıya genel bir hata mesajı gösterilmeli.
            error_log('CSRF Token üretilirken hata oluştu: ' . $e->getMessage());
            // Güvenlik nedeniyle, bu durumda bir istisna fırlatmak veya işlemi durdurmak en iyisidir.
            throw new Exception("CSRF token oluşturulamadı.", 0, $e);
        }
    }

    /**
     * Bir HTML formu içine CSRF token'ı için gizli bir input alanı ekler.
     * getCSRFToken() fonksiyonunu çağırarak mevcut geçerli token'ı kullanır.
     *
     * @return string HTML input alanı etiketi.
     */
    public static function CSRFInput(): string
    {
        try {
            $token = self::getCSRFToken();
            return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
        } catch (Exception $e) {
            // Token oluşturulamazsa formu göstermemek veya hata mesajı vermek daha güvenli olabilir.
            // Bu örnekte boş string dönerek hata mesajını yakalayana bırakıyoruz.
            error_log('CSRF field oluşturulurken hata: ' . $e->getMessage());
            return '';
        }
    }

    /**
     * Gönderilen isteğin (genellikle POST) bir CSRF token'ı içerip içermediğini,
     * bu token'ın oturumdakiyle eşleşip eşleşmediğini ve süresinin dolup
     * dolmadığını kontrol eder.
     *
     * Başarılı doğrulama durumunda, token oturumdan kaldırılır (tek kullanımlık).
     *
     * @param string $request_method İsteğin metodunu belirtir (örneğin 'POST'). Sadece belirtilen metodlar için doğrulamayı zorunlu kılar.
     * @param string $token_field_name İsteğin içinde token'ın beklendiği alan adı (varsayılan 'csrf_token').
     * @return bool Token geçerliyse true, aksi takdirde false döner.
     */
    public static function validateCSRFToken(string $request_method = 'POST', string $token_field_name = 'csrf_token'): bool
    {
        // Sadece belirli HTTP metodları için doğrulama yap (GET için genellikle gerekmez)
        if ($_SERVER['REQUEST_METHOD'] !== strtoupper($request_method)) {
            // Doğrulama yapılması gerekmeyen bir metod. Güvenlik için bunu dikkatli kullanın.
            // Genellikle sadece POST, PUT, DELETE gibi durum değiştirici metodlar için kontrol yapılır.
            return true;
        }

        // İsteğin içinde token var mı?
        $request_token = $_REQUEST[$token_field_name] ?? null; // Hem POST hem GET'i $_REQUEST ile kontrol edebiliriz, ancak POST daha güvenlidir.
                                                            // Genellikle sadece $_POST kullanılır. Örnekte esneklik için $_REQUEST kullanıldı.

        // Oturumda token verisi var mı?
        $session_data = $_SESSION[self::CSRF_TOKEN_SESSION_KEY] ?? null;

        // 1. Token istekle birlikte gönderilmemişse VEYA oturumda token yoksa
        if ($request_token === null || $session_data === null || !isset($session_data['token'], $session_data['timestamp'])) {
            // Oturumdaki olası token verisini temizle (güvenlik önlemi)
            unset($_SESSION[self::CSRF_TOKEN_SESSION_KEY]);
            return false;
        }

        // 2. Token'ın süresi dolmuş mu?
        if ((time() - $session_data['timestamp']) >= self::CSRF_TOKEN_LIFETIME) {
            // Süresi dolmuş, oturumdaki token'ı temizle
            unset($_SESSION[self::CSRF_TOKEN_SESSION_KEY]);
            return false;
        }

        // 3. İsteğin içindeki token oturumdakiyle eşleşiyor mu? (Zamanlama saldırılarına karşı güvenli karşılaştırma)
        if (!hash_equals($request_token, $session_data['token'])) {
            // Tokenlar eşleşmiyor, oturumdaki token'ı temizle
            unset($_SESSION[self::CSRF_TOKEN_SESSION_KEY]);
            return false;
        }

        // Doğrulama BAŞARILI: Token geçerli, süresi dolmamış ve eşleşiyor.
        // Güvenlik nedeniyle (tek kullanımlık hale getirmek için), token'ı oturumdan kaldır.
        unset($_SESSION[self::CSRF_TOKEN_SESSION_KEY]);

        return true; // Doğrulama başarılı
    }
}