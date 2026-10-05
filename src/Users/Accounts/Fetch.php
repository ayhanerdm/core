<?php
namespace ayhanerdm\Core\Users\Accounts;

use \ayhanerdm\Core\Enums\UserTables;
use \ayhanerdm\Core\Tools\SearchUserID;
use \ayhanerdm\Core\Exception\CustomException;
use \PDO;

class Fetch {
    // Use the ConnectsDatabase trait to handle database connections
    use \ayhanerdm\Core\Traits\ConnectsDatabase;

    // Set the table name here.
    private static string $userTable = UserTables::UserAccounts->value;

    public function __construct(array $options) {
        $pdo = self::getDatabase();
        
        if(!isset($options['user_query'])) {
            throw new CustomException(
                message: 'First argument of '. __CLASS__ . ' must contain a key named user_query.',
                wikiUrl: 'https://github.com/ayhanerdm/core/wiki/Custom-Exceptions#user_query',
            );
        }

        $user_uuid = SearchUserID::Search($options['user_query'], $pdo);

        $prep = $pdo->prepare('select * from '.self::$userTable.' where user_uuid = :user_uuid limit 1');
                $prep->execute(['user_uuid' => $user_uuid]);

        if($prep->rowCount() == 0) return false;

        $fetchMethod = $options['fetch_method'] ?? self::$fetchMethod;

        return $prep->fetch($fetchMethod);
    }
}