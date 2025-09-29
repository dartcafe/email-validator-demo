<?php

declare(strict_types=1);

namespace Dartcafe\EmailValidator\Demo\Http;

/**
 * Represents an HTTP response.
 */
final class Response
{
    /**
     * Create a new Response instance
     *
     * @param int $status The HTTP status code (e.g., 200, 404)
     * @param string $body The response body content
     * @param array<string,string> $headers Associative array of HTTP headers
     *
     * */
    public function __construct(
        public int $status = 200,
        public string $body = '',
        public array $headers = [],
    ) {
    }

    /**
     * Create a JSON response
     *
     * @param int                     $status The HTTP status code
     * @param array|\JsonSerializable $data   The data to be JSON-encoded
     * @return self
     */
    public static function json(int $status, array|\JsonSerializable $data): self
    {
        return new self(
            $status,
            json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ['Content-Type' => 'application/json; charset=utf-8'],
        );
    }

    /**
     * Send the response to the client
     */
    public function send(): void
    {
        http_response_code($this->status);
        foreach ($this->headers as $k => $v) {
            header($k . ': ' . $v);
        }
        echo $this->body;
    }
}
