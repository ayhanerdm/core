<?php

namespace ayhanerdm\Core\Enums;

enum Domains: string
{
    // Sub or main domains
    case Main = 'ayhanerdm';
    case Api = 'api.ayhanerdm';
    case Auth = 'auth.ayhanerdm';
    case CDN = 'cdn.ayhanerdm';
    case Short = 'er.dm';

    public function getDomain(): string
    {
        return $this->value;
    }

    public function getUrl(
        $https = true,
        $type = 'normal', // 'normal' or 'ddns'
        $path = null,
    ): string {
        
        if($type == 'normal') {
            $url = match($this) {
                self::Main => ($https ? 'https://' : 'http://') . $this->value . '.me',
                self::Api => ($https ? 'https://' : 'http://') . $this->value . '.me',
                self::Auth => ($https ? 'https://' : 'http://') . $this->value . '.me',
                self::CDN => ($https ? 'https://' : 'http://') . $this->value . '.me',
                self::Short => ($https ? 'https://' : 'http://') . $this->value,
            };
        }

        if($type == 'ddns') {
            $url = match($this) {
                self::Main => ($https ? 'https://' : 'http://') . $this->value . '.dynu.net',
                self::Api => ($https ? 'https://' : 'http://') . $this->value . '.dynu.net',
                self::Auth => ($https ? 'https://' : 'http://') . $this->value . '.dynu.net',
                self::CDN => ($https ? 'https://' : 'http://') . $this->value . '.dynu.net',
                self::Short => ($https ? 'https://' : 'http://') . $this->value . '.dynu.net',
                default => throw new \InvalidArgumentException("Invalid domain: ". $this->value),
            };
        }

        if($path) $url .= $path;

        return $url;
    }

    public function getProfileUrl(
        $userHandle,
        $type = 'normal', // 'normal' or 'ddns'
        $https = true,
    ): string {
        return $this->getUrl($https, $type, '/' . $userHandle);
    }

    public function getAvatarUrl(
        $userHandle,
        $type = 'normal', // 'normal' or 'ddns'
        $https = true,
    ): string {
        return $this->getUrl($https, $type, '/user/' . $userHandle . '/avatar');
    }

    public function getCoverUrl(
        $userHandle,
        $type = 'normal', // 'normal' or 'ddns'
        $https = true,
    ): string {
        return $this->getUrl($https, $type, '/user/' . $userHandle . '/cover');
    }

    public function getGravatarUrl(
        $email,
        $size = 200,
        $default = null, // 'mp', 'identicon', 'monsterid', 'wavatar', 'retro', 'robohash', 'blank', or 'url'
    ): string {
        $gravatarUrl = 'https://www.gravatar.com/avatar/';

        // Is $email a valid email address?
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException("Invalid email address: $email");
            return $gravatarUrl . 'default' . '?s=' . $size . '&d=' . urlencode($default);
        }

        // Is it already a hash?
        if (preg_match('/^[a-f0-9]{32}$/', $email)) $emailHash = $email;
        else $emailHash = md5(strtolower(trim($email)));


        $params = [];
        if($size)$params[] = 's=' . $size;
        if($default)$params[] = 'd=' . urlencode($default);
        $query = $params ? '?' . implode('&', $params) : '';
        return $gravatarUrl . $emailHash . $query;
    }
}