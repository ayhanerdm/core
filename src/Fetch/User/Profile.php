<?php

namespace ayhanerdm\Core\Fetch\User;

use ayhanerdm\Core\Enums\{ UserTables, Domains, DateTimeFormats };
use ayhanerdm\Core\Fetch\User\{ Emails, Usernames };
use ayhanerdm\Core\Tools\SearchUserID;

use PDO, Exception, stdClass;

class Profile
{
    public static string $sql;
    public static UserTables $table = UserTables::UserProfiles;
    public static string $userQuery;
    public static int $userID;
    public static bool $https = true;
    public static int $fetchMethod = PDO::FETCH_OBJ;
    private static PDO $pdo;
    private static bool $settingsCalled = false;

    /**
     * Settings method to set up the class with user query, fetch method, and PDO instance
     *
     * @param string|int $userQuery The username or user ID to search for
     * @param bool $https Whether to use HTTPS for URLs
     * @param int $fetchMethod The fetch method to use (default is PDO::FETCH_OBJ)
     * @param PDO|null $pdo The PDO instance to use (default is null)
     * @return self
     * @throws Exception If any of the parameters are null or invalid
     */
    public static function Settings(
        string|int $userQuery,
        bool $https = true,
        int $fetchMethod = PDO::FETCH_OBJ,
        ?PDO $pdo = null
    ): self {
        if (is_null($userQuery)) {
            throw new Exception('Settings(userQuery: $userQuery) is null!');
        }

        self::$userQuery = $userQuery;

        if ((self::$userID = SearchUserID::Search($userQuery, $pdo)) === false) {
            throw new Exception('Settings(userQuery: $userQuery) couldn\'t be found in database!');
        }

        if (!is_null($https)) {
            self::$https = $https;
        }

        if (is_null($fetchMethod)) {
            throw new Exception('Settings(fetchMethod: $fetchMethod) is null!');
        }

        self::$fetchMethod = $fetchMethod;

        if (is_null($pdo)) {
            throw new Exception('Settings(pdo: $pdo) is null!');
        }

        self::$pdo = $pdo;
        self::$settingsCalled = true;

        return new self();
    }

    public static function getRow(): null|object|array
    {
        if (!self::$settingsCalled || is_null(self::$userQuery) || is_null(self::$pdo)) {
            throw new Exception('Settings() method must be called before ' . __METHOD__ . ' and it\'s arguments must be set!');
            return false;
        }

        self::$sql = 'select * from ' . self::$table->value . ' where user_id = :user_id limit 1';

        $stmt = self::$pdo->prepare(self::$sql);
        $stmt->bindValue(':user_id', self::$userID, PDO::PARAM_INT);
        $stmt->execute();

        $result = $stmt->fetch(self::$fetchMethod) ?: null;

        if(!is_null($result)) {
            $names = [];
            if (!empty($result->first_name)) {
                $names[] = $result->first_name;
            }
            if (!empty($result->middle_name)) {
                $names[] = $result->middle_name;
            }
            if (!empty($result->last_name)) {
                $names[] = $result->last_name;
            }
            $result->display_name = implode(' ', $names);
        }

        return $result;
    }

    public static function getDefaultUsername(): object|array
    {
        return Usernames::Settings(userQuery: self::$userQuery, fetchMethod: self::$fetchMethod, pdo: self::$pdo)::getDefaultRow();
    }

    public static function getProfileUrl(?string $url = null): string|false
    {
        if (!self::$settingsCalled || is_null(self::$userQuery) || is_null(self::$pdo)) {
            throw new Exception('Settings() method must be called before ' . __METHOD__ . ' and it\'s arguments must be set!');
            return false;
        }

        if (is_null($url)) {
            return Domains::Main->getUrl(https: self::$https, type: 'normal') . '/' . self::getDefaultUsername()->username;
        }

        return $url . '/' . self::getDefaultUsername()->username;
    }

    public static function getAvatarUrl(?string $url = null): string|false
    {
        if (!self::$settingsCalled || is_null(self::$userQuery) || is_null(self::$pdo)) {
            throw new Exception('Settings() method must be called before ' . __METHOD__ . ' and it\'s arguments must be set!');
            return false;
        }

        if (is_null($url)) {
            return Domains::Api->getUrl(https: self::$https, type: 'normal') . '/user/' . md5(self::getRow()->user_id) . '/avatar';
        }

        return $url . '/user/' . md5(self::getRow()->user_id) . '/avatar';
    }

    public static function getCoverUrl(?string $url = null): string|false
    {
        if (!self::$settingsCalled || is_null(self::$userQuery) || is_null(self::$pdo)) {
            throw new Exception('Settings() method must be called before ' . __METHOD__ . ' and it\'s arguments must be set!');
            return false;
        }

        if (is_null($url)) {
            return Domains::Api->getUrl(https: self::$https, type: 'normal') . '/user/' . md5(self::getRow()->user_id) . '/cover';
        }

        return $url . '/user/' . md5(self::getRow()->user_id) . '/cover';
    }

    public static function getGravatarUrl(?int $size = null, ?string $default = null): string|false
    {
        if (!self::$settingsCalled || is_null(self::$userQuery) || is_null(self::$pdo)) {
            throw new Exception('Settings() method must be called before ' . __METHOD__ . ' and it\'s arguments must be set!');
            return false;
        }

        $url = Domains::Api->getGravatarUrl(https: true);
        $url .= md5(Emails::Settings(userQuery: self::$userID, pdo: self::$pdo)::getDefaultRow()->email);

        $params = [];
        if ($size) {
            $params['s'] = intval($size);
        }
        if ($default) {
            $params['d'] = urlencode($default);
        }
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        return $url;
    }
}
