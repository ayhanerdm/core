<?php
namespace ayhanerdm\Core\Models\Main;

use ayhanerdm\Core\Tools\SearchUserID;
use PDO;

class Home {
    use \ayhanerdm\Core\Traits\ViewManager;

    public static function Header(): void {
        extract(self::includeVariables());

        // This method can be used to render the header or perform any other logic
        include self::resolveViewPath() . 'Header.php';
    }

    public static function Footer(): void {
        extract(self::includeVariables());

        // This method can be used to render the footer or perform any other logic
        include self::resolveViewPath() . 'Footer.php';
    }

    public static function index() {
        extract(self::includeVariables());
        
        // This method can be used to render the home page or perform any other logic
        include self::resolveViewPath() . 'Home.php';
    }

    public static function userProfile(null|int|string $userQuery = null) {
        extract(self::includeVariables());

        self::MustLogin();

        if(!is_null($userQuery) && ( is_int($userQuery) || is_string($userQuery) )) {
            // Fetch user profile based on the provided userQuery
            $user_id = SearchUserID::Search(
                userQuery: $userQuery,
                pdo: self::$pdo,
            );
        }

        if(!isset($user_id) || $user_id === false) {
            $user_id = $Session::checkSession(self::$pdo);
        }

        // This method can be used to render the user profile page or perform any other logic
        include self::resolveViewPath() . 'Profile/Home.php';
    }

    public static function userProfileEditPage() {
        extract(self::includeVariables());

        self::MustLogin();

        // This method can be used to render the user profile edit page or perform any other logic
        include self::resolveViewPath() . 'Profile/EditProfile.php';
    }
}