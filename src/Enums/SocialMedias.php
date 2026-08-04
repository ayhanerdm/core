<?php

namespace ayhanerdm\Core\Enums {
    enum SocialMedias
    {
        case Facebook;
        case Twitter;
        case X;
        case Bluesky;
        case Instagram;
        case LinkedIn;
        case Youtube;
        case Pinterest;
        case Snapchat;
        case Reddit;
        case TikTok;
        case Twitch;
        case Telegram;
        case Thread;

        /**
         * Returns the display name of the platform.
         *
         * @return string
         */
        public function getDisplayName(): string
        {
            return match ($this) {
                self::Facebook => 'Facebook',
                self::Twitter => 'Twitter',
                self::X => 'X',
                self::Bluesky => 'Bluesky',
                self::Instagram => 'Instagram',
                self::LinkedIn => 'LinkedIn',
                self::Youtube => 'YouTube',
                self::Pinterest => 'Pinterest',
                self::Snapchat => 'Snapchat',
                self::Reddit => 'Reddit',
                self::TikTok => 'TikTok',
                self::Twitch => 'Twitch',
                self::Telegram => 'Telegram',
                self::Thread => 'Thread',
            };
        }

        /**
         * Returns the URL of the platform's main website.
         *
         * @return string
         */
        public function getWebsiteUrl(): string
        {
            return match ($this) {
                self::Facebook => 'https://www.facebook.com/',
                self::Twitter => 'https://twitter.com/',
                self::X => 'https://x.com/',
                self::Bluesky => 'https://bsky.app/',
                self::Instagram => 'https://www.instagram.com/',
                self::LinkedIn => 'https://www.linkedin.com/',
                self::Youtube => 'https://www.youtube.com/',
                self::Pinterest => 'https://www.pinterest.com/',
                self::Snapchat => 'https://www.snapchat.com/',
                self::Reddit => 'https://www.reddit.com/',
                self::TikTok => 'https://www.tiktok.com/',
                self::Twitch => 'https://www.twitch.tv/',
                self::Telegram => 'https://telegram.org/',
                self::Thread => 'https://www.threads.net/',
            };
        }

        /**
         * Returns the profile URL for a given username.
         *
         * @param string $username
         * @return string
         */
        public function getProfileUrl(string $username): string
        {
            return match ($this) {
                self::Facebook => "https://www.facebook.com/{$username}",
                self::Twitter => "https://twitter.com/{$username}",
                self::X => "https://x.com/{$username}",
                self::Bluesky => "https://bsky.app/profile/{$username}",
                self::Instagram => "https://www.instagram.com/{$username}",
                self::LinkedIn => "https://www.linkedin.com/in/{$username}",
                self::Youtube => "https://www.youtube.com/{$username}",
                self::Pinterest => "https://www.pinterest.com/{$username}",
                self::Snapchat => "https://www.snapchat.com/add/{$username}",
                self::Reddit => "https://www.reddit.com/user/{$username}",
                self::TikTok => "https://www.tiktok.com/@{$username}",
                self::Twitch => "https://www.twitch.tv/{$username}",
                self::Telegram => "https://t.me/{$username}",
                self::Thread => "https://www.threads.net/@{$username}",
            };
        }
    }
}