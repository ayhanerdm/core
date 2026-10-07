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
        string $type,
        string $name,
        mixed $value,
    ): bool {
        if(!isLoggedIn()) return false;

        $attr = new \ayhanerdm\Core\Users\Attributes([
            'database_connection' => db(),
            'user_query' => getUserUUID(),
        ]);

        return $attr::setAttr([
            'type' => $type,
            'name' => $name,
            'value' => $value,
        ]);
    }

    function getAttr(
        string $type,
        string $name,
        ?string $return_type = null,
        ?bool $decode_json_value = null,
    ): mixed {
        if(!isLoggedIn()) return false;

        $attr = new \ayhanerdm\Core\Users\Attributes([
            'database_connection' => db(),
            'user_query' => getUserUUID(),
        ]);
        
        return $attr::getAttr([
            'name' => $name,
            'type' => $type,
            'return_type' => $return_type ?? 'value',
            'decode_json_value' => $decode_json_value,
        ]);
    }

    function deleteAttr(
        string $type,
        string $name,
        string $delete_mode = 'soft_delete',
    ): mixed {
        if(!isLoggedIn()) return false;

        $attr = new \ayhanerdm\Core\Users\Attributes([
            'database_connection' => db(),
            'user_query' => getUserUUID(),
        ]);

        return $attr::deleteAttr([
            'name' => $name,
            'type' => $type,
            'delete_mode' => $delete_mode,
        ]);
    }