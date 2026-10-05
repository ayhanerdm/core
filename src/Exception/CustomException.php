<?php
namespace ayhanerdm\Core\Exception;

class CustomException extends Exception {
    private string $wikiUrl;

    public function __construct(string $message, string $wikiUrl, int $code = 0, Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
        $this->wikiUrl = $wikiUrl;
    }

    public function getWikiUrl(): string {
        return $this->wikiUrl;
    }
}