<?php
namespace ayhanerdm\Core\Enums {
    Enum UserAttrTypes {
        case Setting;

        public function getUserAttrType(): string
        {
            return match ($this) {
                self::Setting => 'setting',
            };
        }
    }
}