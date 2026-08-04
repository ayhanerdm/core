<?php
namespace ayhanerdm\Core\Tools;

class NamingStyle {

    public const CASES = [
        'SCREAMING_SNAKE_CASE' => 'Screaming Snake Case',
        'PascalCase' => 'Pascal Case',
        'camelCase' => 'Camel Case',
        'snake_case' => 'Snake Case',
        'kebab-case' => 'Kebab Case',
        'Unknown' => 'Unknown Naming Style',
    ];

    public static function determineNamingStyle(string $name): string
    {
        return match (true) {
            // SCREAMING_SNAKE_CASE: Sadece büyük harfler, sayılar ve alt çizgiler, en az bir alt çizgi içermeli
            // Veya tamamen büyük harf ve sayıdan oluşup alt çizgi içermeyen ama sabit olması beklenen durumlar
            preg_match('/^[A-Z0-9]+(?:_[A-Z0-9]+)*$/', $name) && str_contains($name, '_') => self::CASES['SCREAMING_SNAKE_CASE'],

            // PascalCase: Büyük harfle başlar, sonrasında harfler ve sayılar olabilir, alt çizgi içermez.
            // Genellikle sınıflar, traitler, interfaceler için.
            preg_match('/^[A-Z][a-zA-Z0-9]*$/', $name) => self::CASES['PascalCase'],

            // camelCase: Küçük harfle başlar, sonrasında harfler ve sayılar olabilir, alt çizgi içermez.
            // Genellikle değişkenler, metotlar için.
            preg_match('/^[a-z][a-zA-Z0-9]*$/', $name) => self::CASES['camelCase'],

            // snake_case: Tamamen küçük harflerden ve sayılardan oluşur, alt çizgi ile ayrılır.
            // Genellikle veritabanı sütunları, bazı eski fonksiyonlar veya dosya adları için.
            preg_match('/^[a-z0-9]+(?:_[a-z0-9]+)*$/', $name) && str_contains($name, '_') => self::CASES['snake_case'],

            // kebab-case: Tamamen küçük harflerden ve sayılardan oluşur, tire ile ayrılır.
            // Genellikle URL'ler, bazı dosya adları için.
            preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $name) && str_contains($name, '-') => self::CASES['kebab-case'],

            // Unknown: Yukarıdaki hiçbir duruma uymayan adlar için
            default => self::CASES['Unknown'],
        };
    }
}