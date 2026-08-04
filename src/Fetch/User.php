<?php
namespace ayhanerdm\Core\Fetch;

use \ayhanerdm\Core\Fetch\User\Account;
use \ayhanerdm\Core\Fetch\User\Profile;
use \ayhanerdm\Core\Fetch\User\Wallet;
use \PDO;

class User {
    /**
     * Constructor to initialize the User object
     * @param int|null $user_id The user ID to fetch the profile for
     * @param PDO|null $pdo The PDO instance for database connection
     */
    public function __construct(
        public ?int $user_id = null,
        private ?\PDO $pdo = null,
    ) {}

    public function getAccount(int $returnType = PDO::FETCH_OBJ): mixed {
        // Check if PDO is null and throw an exception if it is
        if($this->pdo == null) {
            throw new \Exception('__construct(pdo: $pdo) is null!');
            return false;
        }

        // Check if user_id is null and throw an exception if it is
        if($this->user_id == null) {
            throw new \Exception('__construct(user_id: $user_id) is null!');
            return false;
        }

        return new Account(
            user_id: $this->user_id,
            pdo: $this->pdo
        )->getRow($returnType);
    }

    public function getProfile(int $returnType = PDO::FETCH_OBJ): mixed {
        // Check if PDO is null and throw an exception if it is
        if($this->pdo == null) {
            throw new \Exception('__construct(pdo: $pdo) is null!');
            return false;
        }

        // Check if user_id is null and throw an exception if it is
        if($this->user_id == null) {
            throw new \Exception('__construct(user_id: $user_id) is null!');
            return false;
        }

        return new Profile(
            user_id: $this->user_id,
            pdo: $this->pdo
        )->getRow($returnType);
    }

    public function getWallet(int $returnType = PDO::FETCH_OBJ): mixed {
        // Check if PDO is null and throw an exception if it is
        if($this->pdo == null) {
            throw new \Exception('__construct(pdo: $pdo) is null!');
            return false;
        }

        // Check if user_id is null and throw an exception if it is
        if($this->user_id == null) {
            throw new \Exception('__construct(user_id: $user_id) is null!');
            return false;
        }

        return new Wallet(
            user_id: $this->user_id,
            pdo: $this->pdo
        )->getRow($returnType);
    }
}
