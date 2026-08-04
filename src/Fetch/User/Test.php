<?php
namespace ayhanerdm\Core\Fetch\User;
use ayhanerdm\Core\ValueObjects\UserFetchSettings;

class Test {
    public static function getTest(UserFetchSettings $test) {
        return match($test->getTable()) {
            'user_accounts' => 'User Accounts',
            'user_profiles' => 'User Profiles',
            'user_wallets' => 'User Wallets',
        };
    }
}