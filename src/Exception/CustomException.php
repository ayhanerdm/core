<?php
namespace ayhanerdm\Core\Exception;
use \Exception;

class CustomException extends \Exception {
    private ?string $wikiUrl;
    private ?string $errorCode;

    public function __construct(string $message, ?string $wikiUrl = null, ?string $errorCode = 'undefined_error', int $code = 0, ?Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
        $this->wikiUrl = $wikiUrl;
        $this->errorCode = $errorCode;
    }

    public function getWikiUrl(): string {
        return $this->wikiUrl;
    }

    public function getErrorCode(): string {
        return $this->errorCode;
    }
}