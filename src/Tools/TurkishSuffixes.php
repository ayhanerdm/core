<?php
namespace ayhanerdm\Core\Tools;

class TurkishSuffixes
{
    const YALIN                 = 'yalın';

    const ILGI                  = 'in';
    const AITLIK                = self::ILGI;
    const IN                    = self::ILGI;

    const BELIRTME              = 'i';
    const I                     = self::BELIRTME;

    const YONELME               = 'e';
    const E                     = self::YONELME;

    const BULUNMA               = 'de';
    const DE                    = self::BULUNMA;

    const AYRILMA               = 'den';
    const DEN                   = self::AYRILMA;
    const DAN                   = self::AYRILMA;

    const BIRLIKTELIK           = 'ile';
    const ILE                   = self::BIRLIKTELIK;

    const COKLUK                = 'cokluk';
    const LER                   = self::COKLUK;
    const LAR                   = self::COKLUK;

    const DEFAULT_APOSTROPHE         = true;
    const DEFAULT_REQUESTED_SUFFIX    = self::YALIN;

    private static array $hardConsonant = ['ç', 'f', 'h', 'k', 'p','s', 'ş', 't'];
    private static array $uppercases = ['A', 'I', 'E', 'İ', 'U','O', 'Ü', 'Ö', 'Ç', 'F', 'H', 'K', 'P', 'S', 'Ş', 'T'];
    private static array $lowercases = ['a', 'ı', 'e', 'i', 'u','o', 'ü', 'ö', 'ç', 'f', 'h', 'k', 'p', 's', 'ş', 't'];

    public static string $name;
    private static string $nameLowercase;

    private static string $requestedSuffix;
    private static string $suffix;

    private static bool $apostrophe;

    private static array $vowels;
    private static string $lastVowel;
    private static string $lastCharacter;

    public static string $result;

    public function __construct(bool $apostrophe = self::DEFAULT_APOSTROPHE) {
        foreach(get_defined_vars() as $key => $val){ self::$$key = $val; }
    }

    public static function Cekimle(string $name, string $requestedSuffix = self::DEFAULT_REQUESTED_SUFFIX, bool $apostrophe = self::DEFAULT_APOSTROPHE) :string {
        if(!$name || empty($name) || !is_string($name)) {
            throw new \Exception('First argument of '.__METHOD__.' method must be a string and cannot be empty.');
            return false;
        }

        foreach(get_defined_vars() as $key => $val){ self::$$key = $val; }

        self::$nameLowercase = trim(str_replace(self::$uppercases, self::$lowercases, self::$name));
        self::$lastCharacter  = substr(self::$name, -1);

        preg_match_all(
            '/[aeiou`]/',
            str_replace(['ı', 'ö', 'ü'], ['a', '`', '`'], self::$nameLowercase),
            $found
        );
        self::$vowels = $found[0];
        self::$lastVowel = end(self::$vowels);

        switch(self::$requestedSuffix)
        {
            case 'yalın': { self::$suffix = ''; } break;
            case 'in': {
                if(self::$lastCharacter == 'a' || self::$lastCharacter == 'ı'){ self::$suffix = 'nın'; }
                elseif(self::$lastCharacter == 'e' || self::$lastCharacter == 'i'){ self::$suffix = 'nin'; }
                elseif(self::$lastCharacter == 'u' || self::$lastCharacter == 'o'){ self::$suffix = 'nun'; }
                elseif(self::$lastCharacter == 'ü' || self::$lastCharacter == 'ö'){ self::$suffix = 'nün'; }
                elseif(self::$lastVowel == 'a' || self::$lastVowel == 'ı'){ self::$suffix = 'ın'; }
                elseif(self::$lastVowel == 'e' || self::$lastVowel == 'i'){ self::$suffix = 'in'; }
                elseif(self::$lastVowel == 'u' || self::$lastVowel == 'o'){ self::$suffix = 'un'; }
                elseif(self::$lastVowel == '`' ) { self::$suffix = 'ün'; } 
                else { self::$suffix = 'ın'; }
            } break;
            case 'e': {
                if(self::$lastCharacter == 'a' || self::$lastCharacter == 'ı' || self::$lastCharacter == 'u' || self::$lastCharacter == 'o') { self::$suffix = 'ya'; }
                elseif(self::$lastCharacter == 'e' || self::$lastCharacter == 'i' || self::$lastCharacter == 'ü' || self::$lastCharacter == 'ö') { self::$suffix = 'ye'; }
                elseif(self::$lastVowel == 'a' || self::$lastVowel == 'ı' || self::$lastVowel == 'u' || self::$lastVowel == 'o') { self::$suffix = 'a'; }
                elseif(self::$lastVowel == 'e' || self::$lastVowel == 'i' || self::$lastVowel == 'ö' || self::$lastVowel == 'ü') { self::$suffix = 'e'; }
                else { self::$suffix = 'a'; }
            } break;
            case 'i': {
                if(self::$lastCharacter == 'a' || self::$lastCharacter == 'ı') { self::$suffix = 'yı'; }
                elseif(self::$lastCharacter == 'e' || self::$lastCharacter == 'i') { self::$suffix = 'yi'; }
                elseif(self::$lastCharacter == 'u' || self::$lastCharacter == 'o') { self::$suffix = 'yu'; }
                elseif(self::$lastCharacter == 'ü' || self::$lastCharacter == 'ö') { self::$suffix = 'yü'; }
                elseif(self::$lastVowel == 'a' || self::$lastVowel == 'ı') { self::$suffix = 'ı'; }
                elseif(self::$lastVowel == 'e' || self::$lastVowel == 'i') { self::$suffix = 'i'; }
                elseif(self::$lastVowel == 'u' || self::$lastVowel == 'o') { self::$suffix = 'u'; }
                elseif(self::$lastVowel == '`') { self::$suffix = 'ü'; }
            } break;
            case 'de': {
                if(self::$lastCharacter == 'a' || self::$lastCharacter == 'ı' || self::$lastCharacter == 'u' || self::$lastCharacter == 'o') { self::$suffix = 'da'; }
                elseif(self::$lastCharacter == 'e' || self::$lastCharacter == 'i' || self::$lastCharacter == 'ü' || self::$lastCharacter == 'ö') { self::$suffix = 'de'; }
                elseif(in_array(self::$lastCharacter , self::$hardConsonant) and (self::$lastVowel == 'a' || self::$lastVowel == 'ı' || self::$lastVowel == 'u' || self::$lastVowel == 'o') ) { self::$suffix = 'ta'; }
                elseif(in_array(self::$lastCharacter , self::$hardConsonant) and (self::$lastVowel == 'e' || self::$lastVowel == 'i' || self::$lastVowel == 'ü' || self::$lastVowel == 'ö') ) { self::$suffix = 'te'; }
                elseif(self::$lastVowel == 'a' || self::$lastVowel == 'ı' || self::$lastVowel == 'u' || self::$lastVowel == 'o') { self::$suffix = 'da'; }
                elseif(self::$lastVowel == 'e' || self::$lastVowel == 'i' || self::$lastVowel == 'ü' || self::$lastVowel == 'ö') { self::$suffix = 'de'; }
                elseif(in_array(self::$lastCharacter , self::$hardConsonant)) { self::$suffix = 'ta'; }
                else { self::$suffix = 'da'; }  
            } break;        
            case 'den': {
                if(self::$lastCharacter == 'a' || self::$lastCharacter == 'ı' || self::$lastCharacter == 'u' || self::$lastCharacter == 'o') { self::$suffix = 'dan'; }
                elseif(self::$lastCharacter == 'e' || self::$lastCharacter == 'i' || self::$lastCharacter == 'ü' || self::$lastCharacter == 'ö') { self::$suffix = 'den'; }
                elseif(in_array(self::$lastCharacter , self::$hardConsonant) and (self::$lastVowel == 'a' || self::$lastVowel == 'ı' || self::$lastVowel == 'u' || self::$lastVowel == 'o') ) { self::$suffix = 'tan'; }
                elseif(in_array(self::$lastCharacter , self::$hardConsonant) and (self::$lastVowel == 'e' || self::$lastVowel == 'i' || self::$lastVowel == 'ü' || self::$lastVowel == 'ö') ) { self::$suffix = 'ten'; }
                elseif(self::$lastVowel == 'a' || self::$lastVowel == 'ı' || self::$lastVowel == 'u' || self::$lastVowel == 'o') { self::$suffix = 'dan'; }
                elseif(self::$lastVowel == 'e' || self::$lastVowel == 'i' || self::$lastVowel == 'ü' || self::$lastVowel == 'ö') { self::$suffix = 'den'; }
                elseif(in_array(self::$lastCharacter , self::$hardConsonant)) { self::$suffix = 'tan'; }
                else { self::$suffix = 'dan'; }
            } break;        
            case 'ile': {
                if(self::$lastCharacter == 'a' || self::$lastCharacter == 'ı' || self::$lastCharacter == 'u' || self::$lastCharacter == 'o') { self::$suffix = 'yla'; }
                elseif(self::$lastCharacter == 'e' || self::$lastCharacter == 'i' || self::$lastCharacter == 'ü' || self::$lastCharacter == 'ö') { self::$suffix = 'yle'; }
                elseif(self::$lastVowel == 'a' || self::$lastVowel == 'ı' || self::$lastVowel == 'u' || self::$lastVowel == 'o') { self::$suffix = 'la'; }
                elseif(self::$lastVowel == 'e' || self::$lastVowel == 'i' || self::$lastVowel == 'ö' || self::$lastVowel == 'ü') { self::$suffix = 'le'; }
                else { self::$suffix = 'la'; }
            } break;
            case 'cokluk': {
                if(self::$lastVowel == 'a' || self::$lastVowel == 'ı' || self::$lastVowel == 'o' || self::$lastVowel == 'u') { self::$suffix = 'lar'; }
                elseif(self::$lastVowel == 'e' || self::$lastVowel == 'i' || self::$lastVowel == 'ö' || self::$lastVowel == 'ü') { self::$suffix = 'ler'; }
                else { self::$suffix = 'lar'; }
            } break;
            default: { self::$suffix = ''; self::$apostrophe = false; } break;
        }

        if(ctype_upper(self::$lastCharacter)) self::$suffix = mb_strtoupper(self::$suffix, 'UTF-8');

        if(self::$requestedSuffix != self::YALIN)
        {
            if(self::$apostrophe == true) return self::$result = self::$name."'".self::$suffix;
            else return self::$result = self::$name.self::$suffix;
        }
        else return self::$name;
    }
}