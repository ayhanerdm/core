<?php
namespace ayhanerdm\Core\Traits;

use ayhanerdm\Core\User\{ Session, Account, Profile, Wallet };
use ayhanerdm\Core\Tools\Domain;
use PDO, Exception;

trait ViewManager {
    public static ?string $viewPath = null;
    private static ?PDO $pdo = null;

    public static function setDatabase(PDO $pdo): void {
        self::$pdo = $pdo;
    }

    public static function setViewPath(string $path): bool {

        if(empty($path) || is_null($path)) {
            throw new \InvalidArgumentException('View path cannot be empty or null.');
            return false;
        }

        // Check if the path exists and is a directory
        if(!is_dir($path)) {
            throw new \InvalidArgumentException('The specified view path does not exist or is not a directory: '. $path);
            return false;
        }

        $path = realpath($path);
        
        if(substr($path, -1) !== DIRECTORY_SEPARATOR) {
            $path .= DIRECTORY_SEPARATOR;
        }

        if($path === false) {
            throw new \RuntimeException('Failed to resolve the real path for: ' .$path);
            return false;
        }

        self::$viewPath = $path;

        return true;
    }

    public static function getViewPath(): false|string {
        if (is_null(self::$viewPath) || empty(self::$viewPath)) {
            throw new \RuntimeException('View path has not been set or empty, please set the view path using setViewPath() method.');
            return false;
        }

        return self::$viewPath;
    }

    public static function resolveViewPath(): ?string {
        if (is_null(self::$viewPath) || empty(self::$viewPath)) {
            self::$viewPath = realpath(dirname(__DIR__, 1) . '/views/');
        }

        return self::$viewPath;
    }

    public static function MustLogin() {
        extract(self::includeVariables());

        $_SESSION['redirect_url'] = $currentUrl;

        // Check session and redirect if not logged in
        if (!$Session::checkSession(self::$pdo)) {
            header('Location: '. 'https://auth.'.Domain::getDomain().'/login');
            exit;
        }
    }

    public static function includeVariables(null|int|string $userHandle = null): array {
        $db = self::$pdo;
        $Session = new Session;
        $user_id = $Session::checkSession($db);

        // Logged in user
        $userAccount = Account::Fetch(
            userQuery: $user_id,
            fetchMethod: PDO::FETCH_OBJ,
            pdo: $db,
        );
 
        $userProfile = Profile::Fetch(
            userQuery: $user_id,
            fetchMethod: PDO::FETCH_OBJ,
            pdo: $db,
        );

        $userWallet = Wallet::Fetch(
            userQuery: $user_id,
            fetchMethod: PDO::FETCH_OBJ,
            pdo: $db,
        );

        // The user that the logged in user is viewing
        if(!is_null($userHandle)) $viewUserID = $userHandle;
        else $viewUserID = $user_id;

        $viewUserAccount = Account::Fetch(
            userQuery: $viewUserID,
            pdo: self::$pdo,
        );

        $viewUserProfile = Profile::Fetch(
            userQuery: $viewUserID,
            pdo: self::$pdo,
        );

        $viewUserWallet = Wallet::Fetch(
            userQuery: $viewUserID,
            pdo: self::$pdo,
        );

        $currentUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        if(!isset($_SESSION['redirect_url'])) $_SESSION['redirect_url'] = $currentUrl;

        return [
            'db' => $db,
            'Session' => $Session,
            
            'user_id' => $user_id,
            'userAccount' => $userAccount,
            'userProfile' => $userProfile,
            'userWallet' => $userWallet,

            'viewUserID' => $viewUserID,
            'viewUserAccount' => $viewUserAccount,
            'viewUserProfile' => $viewUserProfile,
            'viewUserWallet' => $viewUserWallet,

            'currentUrl' => $currentUrl,
            'site_id' => $_ENV['SITE_ID'],
        ];
    }
}