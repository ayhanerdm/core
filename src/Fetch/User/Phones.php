<?php
namespace ayhanerdm\Core\Fetch\User;

use ayhanerdm\Core\Enums\UserTables;
use PDO;

class Phones {
    public string $sql;
    public UserTables $table = UserTables::UserPhones;

    public function __construct(
        public ?int $user_id = null,
        private ?\PDO $pdo = null,
    ) {}

    /**
     * Fetch all rows with the same user_id from the phones table
     * @param int $returnType The fetch style to use (default is PDO::FETCH_OBJ)
     * @return mixed The fetched rows or null if no rows are found
     * @throws \Exception If PDO or user_id is null
     */
    public function getRows(int $returnType = PDO::FETCH_OBJ): mixed {
        if($this->pdo == null) {
            throw new \Exception('__construct(pdo: $pdo) is null!');
        }

        if($this->user_id == null) {
            throw new \Exception('__construct(user_id: $user_id) is null!');
        }

        $this->sql = 'select * from '. $this->table->value .' where user_id = :user_id';

        $stmt = $this->pdo->prepare($this->sql);
        $stmt->bindValue(':user_id', $this->user_id, PDO::PARAM_INT);

        $stmt->execute();

        $result = $stmt->fetchAll($returnType) ?: null;

        if(is_null($result)) {
            return null;
        }

        // Iterate through the result if it is an array of objects
        if (is_array($result)) {
            foreach ($result as $row) {
                if (is_object($row)) {
                    $row->number = self::makeNumber($row);
                } elseif (is_array($row)) {
                    $row['number'] = self::makeNumber($row);
                }
            }
        }

        // Return the result
        return $result;
    }

    /**
     * Fetch a single row with is_default = 1 from the phones table
     * @param int $returnType The fetch style to use (default is PDO::FETCH_OBJ)
     * @return mixed The fetched row or null if no row is found
     * @throws \Exception If PDO or user_id is null
     */
    public function getDefaultRow(int $returnType = PDO::FETCH_OBJ): mixed {
        if($this->pdo == null) {
            throw new \Exception('__construct(pdo: $pdo) is null!');
        }

        if($this->user_id == null) {
            throw new \Exception('__construct(user_id: $user_id) is null!');
        }

        $this->sql = 'select * from '. $this->table->value .' where user_id = :user_id and is_default = 1 limit 1';

        $stmt = $this->pdo->prepare($this->sql);
        $stmt->bindValue(':user_id', $this->user_id, PDO::PARAM_INT);

        $stmt->execute();

        $result = $stmt->fetch($returnType) ?: null;

        if(is_null($result)) {
            return null;
        }
        
        // Check if the result is an object and set phone_number to $this->makeNumber($result)
        if(is_object($result)) {
            $result->phone = self::makeNumber($result);
        }

        // Check if the result is an array and set phone_number to $this->makeNumber($result)
        if(is_array($result)) {
            $result['phone'] = self::makeNumber($result);
        }

        // Return the result
        return $result;
    }

    public function getDefaultPhoneNumber(): string {
        return self::makeNumber(self::getDefaultRow()) ?: null;
    }

    public static function makeNumber(object|array $phone): string {
        if(!is_object($phone) && !is_array($phone)) {
            throw new \Exception('makeNumber($phone) is not an object or array!');
        }

        if(is_array($phone)) {
            return $phone['country_code'] . $phone['subscriber_number'] . $phone['phone_number'];
        }

        if(is_object($phone)) {
            return $phone->country_code . $phone->subscriber_number . $phone->phone_number;
        }
    }
}
