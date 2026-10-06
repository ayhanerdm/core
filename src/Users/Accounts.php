<?php
namespace ayhanerdm\Core\Users;

use \ayhanerdm\Core\Enums\UserTables;
use \ayhanerdm\Core\Tools\SearchUserID;
use \ayhanerdm\Core\Tools\Crypto;
use \ayhanerdm\Core\Exception\CustomException;
use \PDO;

class Accounts {
    // Use the ConnectsDatabase trait to handle database connections
    use \ayhanerdm\Core\Traits\ConnectsDatabaseBeta;

    public function __construct(?array $options = null) {
        if($options !== null) self::setOptions($options);
        self::setOption('table_name', UserTables::UserAccounts->value);
    }

    public static function Fetch(?array $options = null) {
        if($options !== null) self::setOptions($options);
        self::setOption('table_name', UserTables::UserAccounts->value);

        if(is_null(self::getOption('user_query'))) {
            throw new CustomException(
                message: 'First argument of '. __CLASS__ . ' must contain a key named user_query.',
                wikiUrl: 'https://github.com/ayhanerdm/core/wiki/Custom-Exceptions#user_query',
                errorCode: 'missing_user_query',
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

        return  (self::getOption('fetch_method') === PDO::FETCH_OBJ) ?
                (object) self::$fetch_details['result'] :
                self::$fetch_details['result'];
    }

    public static function verifyPassword(#[\Sensitive] string $password): bool {
        if(!self::$fetch_details['result'] || is_null(self::$fetch_details['result'])) {
            throw new CustomException(
                message: 'self::$fetch_details[\'result\'] must not be null for ' . __METHOD__ . ' to work.',
                wikiUrl: 'https://github.com/ayhanerdm/core/wiki/Custom-Exceptions',
                errorCode: 'fetch_details_result_missing',
            );
        }

        return  (is_array(self::$fetch_details['result'])) ?
                password_verify($password, self::$fetch_details['result']['password']) :
                password_verify($password, self::$fetch_details['result']->password);
    }
}