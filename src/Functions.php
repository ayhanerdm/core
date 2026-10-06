<?php

    function isBase64(?string $string): bool {
        if($string === '' || is_null($string)) return false;

        $normalized = strtr($string, '-_', '+/');

        if(strlen($normalized) % 4 !== 0) return false;

        // Regex ile karakter kümesi ve '=' dolgu (padding) kontrolü
        // İzin verilen: A-Z, a-z, 0-9, +, / ve sonda 0, 1 veya 2 adet '='
        if(!preg_match('/^[a-zA-Z0-9\/+]+={0,2}$/', $normalized)) return false;

        $decoded = base64_decode($normalized, true);

        if($decoded === false) return false;

        return base64_encode($decoded) === $normalized;
    }

    function isUnixTimestamp(?string $string): bool {
        if(is_null($string)) return false;

        if($string === '' || strlen($string) > 11) return false;

        if(!preg_match('/^-?\d+$/', $string)) return false;

        $date = DateTime::createFromFormat('U', $string);
        
        return $date && $date->format('U') === $string;
    }

    function getUserIp() {
        // 1. Check if the request went through a proxy
        if(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // X-Forwarded-For can contain multiple IPs separated by commas
            $ipList = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $clientIp = trim($ipList[0]);
            
            // 2. Validate it is a properly formed IP address
            if(filter_var($clientIp, FILTER_VALIDATE_IP)) {
                return $clientIp;
            }
        }
        
        // Fallback to the direct connection address
        return $_SERVER['REMOTE_ADDR'];
    }