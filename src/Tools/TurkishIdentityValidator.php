<?php
namespace ayhanerdm\Core\Tools;

class TurkishIdentityValidator {
    public static function isValidFormat($tckn) {
        if(strlen($tckn) != 11) return false;

        if(!ctype_digit($tckn)) return false;

        if($tckn[0] == '0') return false;

        $totalOdd = $tckn[0] + $tckn[2] + $tckn[4] + $tckn[6] + $tckn[8];
        $totalEven = $tckn[1] + $tckn[3] + $tckn[5] + $tckn[7];

        $checkSum10 = (($totalOdd * 7) - $totalEven) % 10;
        if($checkSum10 != $tckn[9]) return false;

        $totalAll = $totalOdd + $totalEven + $tckn[9];
        $checkSum11 = $totalAll % 10;
        if($checkSum11 != $tckn[10]) return false;

        return true;
    }
    public static function isValidRemote(string $tckn, string $firstName, string $lastName, int $birthYear): bool {
        try {
            $client = new \SoapClient('https://tckimlik.nvi.gov.tr/Service/KPSPublic.asmx?WSDL');
            $result = $client->TCKimlikNoDogrula([
                'TCKimlikNo' => $tckn,
                'Ad' => mb_strtoupper($firstName, 'UTF-8'),
                'Soyad' => mb_strtoupper($lastName, 'UTF-8'),
                'DogumYili' => $birthYear
            ]);
            return $result->TCKimlikNoDogrulaResult === true;
        } catch(\Exception $e) {
            return false;
        }
    }
}