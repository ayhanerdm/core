<?php

    function isBase64(string $string): bool {
        // 1. Boşluk kontrolü
        if ($string === '') {
            return false;
        }

        // 2. URL-Safe Base64 karakterlerini standart Base64'e çevir (- -> +, _ -> /)
        $normalized = strtr($string, '-_', '+/');

        // 3. Uzunluk mutlaka 4'ün tam katı olmalıdır
        if (strlen($normalized) % 4 !== 0) {
            return false;
        }

        // 4. Regex ile karakter kümesi ve '=' dolgu (padding) kontrolü
        // İzin verilen: A-Z, a-z, 0-9, +, / ve sonda 0, 1 veya 2 adet '='
        if (!preg_match('/^[a-zA-Z0-9\/+]+={0,2}$/', $normalized)) {
            return false;
        }

        // 5. Strict modda decode et (geçersiz bayt veya dolgu hatasında false döner)
        $decoded = base64_decode($normalized, true);
        if ($decoded === false) {
            return false;
        }

        // 6. Çözülen veriyi tekrar kodlayarak orijinaliyle tam uyuştuğunu doğrula
        return base64_encode($decoded) === $normalized;
    }