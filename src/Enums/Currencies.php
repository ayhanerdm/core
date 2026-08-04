<?php
namespace ayhanerdm\Core\Enums {
    Enum Currencies {
        case TRY;
        case EUR;
        case USD;

        public function getUserType(): string
        {
            return match ($this) {
                self::TRY => 'TRY',
                self::EUR => 'EUR',
                self::USD => 'USD',
            };
        }
    }
}