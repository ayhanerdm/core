<?php
namespace ayhanerdm\Core\Users\Accounts;

use \ayhanerdm\Core\Enums\UserTables;
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
                'First argument of '. __CLASS__ . ' must contain a key named user_query.',
                ''
            );
        }

        $prep = $pdo->prepare('select * from '.self::$userTable.' where '.$where.' limit 1');
        $prep->execute(['query' => $query]);
        if($prep->rowCount() == 0) return false;
        return $prep->fetch($fetchMethod);
    }
}