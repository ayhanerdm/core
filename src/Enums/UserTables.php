<?php
namespace ayhanerdm\Core\Enums;
Enum UserTables: string {
    case UserAccounts = 'user_accounts';
    case UserProfiles = 'user_profiles';
    case UserEmails = 'user_emails';
    case UserPhones = 'user_phones';
    case UserAddresses = 'user_addresses';
    case UserAttributes = 'user_attributes';
    case UserSocials = 'user_socials';
    case UserUsernames = 'user_usernames';
    case UserWallets = 'user_wallets';
    case UserPosts = 'user_posts';
    case UserPostImages = 'user_post_images';
    case UserComments = 'user_comments';
    case UserLikes = 'user_likes';
    case UserFollowers = 'user_followers';
    case UserNotifications = 'user_notifications';

    public function getUserTable(): string {
        return $this->value;
    }
}