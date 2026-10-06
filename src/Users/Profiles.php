<?php
namespace ayhanerdm\Core\Users;

use \ayhanerdm\Core\Enums\UserTables;
use \ayhanerdm\Core\Tools\SearchUserID;
use \ayhanerdm\Core\Tools\Crypto;
use \ayhanerdm\Core\Users\Accounts;
use \ayhanerdm\Core\Exception\CustomException;
use \PDO;

class Profiles {
    // Use the ConnectsDatabase trait to handle database connections
    use \ayhanerdm\Core\Traits\ConnectsDatabaseBeta;

    private static array $user_urls = [
        'profile' => null,
        'avatar' => null,
        'cover' => null,
        'gravatar' => null,
    ];

    public function __construct(?array $options = null) {
        if($options !== null) self::setOptions($options);
        self::setOption('table_name', UserTables::UserProfiles->value);
    }

    public static function Fetch(?array $options = null): bool|array|object {
        if($options !== null) self::setOptions($options);
        self::setOption('table_name', UserTables::UserProfiles->value);

        if(is_null(self::getOption('user_query'))) {
            throw new CustomException(
                message: 'First argument of '. __CLASS__ . ' must contain a key named user_query.',
                wikiUrl: 'https://github.com/ayhanerdm/core/wiki/Custom-Exceptions#user_query',
            );
        }

        $user_uuid = SearchUserID::Search(self::getOption('user_query'), self::getOption('database_connection'));

        if(!$user_uuid) return false;
        
        self::$fetch_details['sql'] = 'select * from '.self::getOption('table_name').' where user_uuid = :user_uuid limit 1';

        $prep = self::getOption('database_connection')->prepare(self::$fetch_details['sql']);
                $prep->execute(['user_uuid' => $user_uuid]);

        if($prep->rowCount() == 0) return false;

        $result = $prep->fetch(PDO::FETCH_ASSOC);

        $matched_rows = [];

        foreach($result as $columnName => $value) {
            $bindexKey = $columnName . '_bindex';

            if(self::getOption('app_secret') !== null && self::getOption('app_secret') !== '') {
                if(array_key_exists($bindexKey, $result)) {
                    if($value !== null) $result[$columnName] = Crypto::decrypt($value, self::getOption('app_secret'));
                }
            }

            unset($result[$bindexKey]);

            if($value !== null && self::getOption('time_format') !== null) {
                if(str_ends_with($columnName, '_at') && isUnixTimestamp($value)) $result[$columnName] = date(self::getOption('time_format'), $value);
            }
        }

        self::$fetch_details['result'] = $result;

        // Also populate user_urls.
        $accounts = Accounts::Fetch([
                'database_connection' => self::getOption('database_connection'),
                'app_secret' => self::getOption('app_secret') ?? $_ENV['APP_SECRET'] ?? null,
                'user_query' => self::getOption('user_query'),
            ]);

        $uuid_hash = hash('sha256', $accounts->user_uuid);
        $email_hash = hash('sha256', $accounts->email);
        $user_handle = $accounts->username ?? $uuid_hash;

        self::$user_urls = [
            'profile' => getCurrentOrigin() . '/' . $user_handle,
            'avatar' => getCurrentOrigin() . '/api/users/' . $user_handle . '/avatar',
            'cover' => getCurrentOrigin() . '/api/users/' . $user_handle . '/cover',
            'gravatar' => 'https://gravatar.com/avatar/' . $email_hash,
        ];

        return  (self::getOption('fetch_method') === PDO::FETCH_OBJ) ?
                (object) self::$fetch_details['result'] :
                self::$fetch_details['result'];
    }

    public static function getUserUrls(): array { return self::$user_urls; }
    public static function getUserUrl(string $url_name): ?string { return self::$user_urls[$url_name]; }
}