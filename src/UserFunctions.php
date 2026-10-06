<?php
    namespace InstantUser;

    function isLoggedIn(): bool {
        if(
            isset($_SESSION['user_uuid']) &&
            !empty($_SESSION['user_uuid']) &&
            $_SESSION['user_uuid'] !== null
        ) return true;

        return false;
    }

    function getUserUUID() {
        if(isset($_SESSION['user_uuid'])) return base64_decode($_SESSION['user_uuid']);
        
        return false;
    }

    function setAttr(
        string $name,
        mixed $value,
        ?string $type = null,
    ): bool {
        if(!isLoggedIn()) return false;

        $attr = new \ayhanerdm\Core\Users\Attributes([
            'database_connection' => db(),
            'user_query' => getUserUUID(),
        ]);

        return $attr::setAttr([
            'name' => $name,
            'type' => $type,
            'value' => $value,
        ]);
    }

    function getAttr(
        string $name,
        ?string $type = null,
    ): mixed {
        if(!isLoggedIn()) return false;

        $attr = new \ayhanerdm\Core\Users\Attributes([
            'database_connection' => db(),
            'user_query' => getUserUUID(),
        ]);

        return $attr::getAttr([
            'name' => $name,
            'type' => $type,
            'return_type' => value,
        ]);
    }