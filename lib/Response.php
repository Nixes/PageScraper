<?php


namespace Nixes\Pagescraper;


class Response
{
    /**
     * @var string|null
     */
    public $body;
    /**
     * @var array same as the output of $http_response_header
     */
    public $header;

    public function __construct(?string $body, array $header)
    {
        $this->body = $body;
        $this->header = $header;
    }
}