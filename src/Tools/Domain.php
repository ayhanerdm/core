<?php
namespace ayhanerdm\Core\Tools;

class Domain {
    public static function getMainUrl(?bool $https = true, bool $endWithSlash = false): ?string {
        return self::getUrl(null, $https, $endWithSlash);
    }

    public static function getApiUrl(?bool $https = true, bool $endWithSlash = false): ?string {
        return self::getUrl('api', $https, $endWithSlash);
    }

    public static function getCDNUrl(?bool $https = true, bool $endWithSlash = false): ?string {
        return self::getUrl('cdn', $https, $endWithSlash);
    }

    public static function getAuthUrl(?bool $https = true, bool $endWithSlash = false): ?string {
        return self::getUrl('auth', $https, $endWithSlash);
    }

    public static function getAdminUrl(?bool $https = true, bool $endWithSlash = false): ?string {
        return self::getUrl('admin', $https, $endWithSlash);
    }

    public static function getAppsUrl(?bool $https = true, bool $endWithSlash = false): ?string {
        return self::getUrl('apps', $https, $endWithSlash);
    }

    public static function getDriveUrl(?bool $https = true, bool $endWithSlash = false): ?string {
        return self::getUrl('drive', $https, $endWithSlash);
    }

    public static function getUrl(?string $subdomain = null, ?bool $https = true, bool $endWithSlash = false): ?string {
        $protocol = $https ? 'https://' : 'http://';
        $domain = self::getDomain($https);
        
        if($subdomain !== null && $subdomain !== '') {
            return $protocol . $subdomain . '.' . $domain . ($endWithSlash ? '/' : '');
        }
        return $protocol . $domain . ($endWithSlash ? '/' : '');
    }
    
    public static function getDomain(?bool $https = true): ?string {
        $host = parse_url($_SERVER['HTTP_HOST'] ?? '', PHP_URL_HOST) ?: ($_SERVER['HTTP_HOST'] ?? '');
        $domain = null;
        
        if (str_ends_with($host, 'ayhanerdm.me')) $domain = 'ayhanerdm.me';
        elseif (str_ends_with($host, 'ayhanerdm.dynu.net')) $domain = 'ayhanerdm.dynu.net';
        
        return $domain ?: $host;
    }
}