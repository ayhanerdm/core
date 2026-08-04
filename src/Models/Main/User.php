<?php
namespace ayhanerdm\Core\Models\Main;

use ayhanerdm\Core\User\{ Account, Profile, Wallet };

class User {
    use \ayhanerdm\Core\Traits\ViewManager;

    public static function Home(null|int|string $userHandle = null) {
        extract(self::includeVariables($userHandle));
        self::MustLogin();

        include self::resolveViewPath() . 'User/Home.php';
    }

    public static function Account(null|int|string $userHandle = null) {
        extract(self::includeVariables($userHandle));
        self::MustLogin();

        include self::resolveViewPath() . 'User/Account.php';
    }

    public static function Profile(null|int|string $userHandle = null) {
        extract(self::includeVariables($userHandle));
        self::MustLogin();

        include self::resolveViewPath() . 'User/Profile.php';
    }

    public static function Wallet(null|int|string $userHandle = null) {
        extract(self::includeVariables($userHandle));
        self::MustLogin();

        include self::resolveViewPath() . 'User/Wallet.php';
    }
}