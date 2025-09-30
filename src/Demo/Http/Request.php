<?php

declare(strict_types=1);

namespace Dartcafe\EmailValidator\Demo\Http;

/**
 * Represents an HTTP request.
 */
final class Request
{
    /**
     * @param string $method The HTTP method (e.g., 'GET', 'POST')
     * @param string $path The request path (e.g., '/validate')
     * @param array<string,string> $query Associative array of query parameters
     * @param string|null $rawBody The raw request body, or null if none
     * @param array<string,string> $headers Associative array of HTTP headers
     * @param array<array-key,mixed>|null $json The parsed JSON body, or null if not JSON
     * @param string $ip The client's IP address
     */
    public function __construct(
        public readonly string $method,
        public readonly string $path,
        /** @var array<string,string> */
        public readonly array $query = [],
        public readonly ?string $rawBody = null,
        /** @var array<string,string> */
        public readonly array $headers = [],
        /** @var array<string,mixed>|null */
        public readonly ?array $json = null,
        public readonly string $ip = '0.0.0.0',
    ) {
    }

    /**
     * Create a Request object from PHP global variables
     *
     * @return self
     * @psalm-suppress PossiblyUnusedMethod
     */
    public static function fromGlobals(): self
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';


        /** @var array<string,string> $headers */
        $headers = [];

        /** @var array<string, mixed> $server */
        $server = $_SERVER;

        /** @var mixed $v */
        foreach ($server as $k => $v) {
            if (!str_starts_with($k, 'HTTP_')) {
                continue;
            }
            $name = strtolower(str_replace('_', '-', substr($k, 5)));
            if (is_array($v)) {
                /** @var list<mixed> $v */
                $headers[$name] = implode(',', array_map(static fn ($x): string => (string)$x, $v));
            } elseif (is_scalar($v)) {
                $headers[$name] = (string)$v;
            }
        }
        if (isset($server['CONTENT_TYPE'])) {
            /** @var mixed $ctVal */
            $ctVal = $server['CONTENT_TYPE'];
            if (is_array($ctVal)) {
                /** @var list<mixed> $ctVal */
                $headers['content-type'] = implode(',', array_map(static fn ($x): string => (string)$x, $ctVal));
            } else {
                $headers['content-type'] = (string)$ctVal;
            }
        }
        $raw = file_get_contents('php://input') ?: null;
        $json = null;
        $ct = $headers['content-type'] ?? '';
        if ($raw !== null && $ct !== '' && stripos($ct, 'application/json') !== false) {

            $tmp = json_decode($raw, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($tmp)) {
                $json = $tmp;
            }
        }

        /** @var array<string,string> $query */
        $query = [];
        foreach ($_GET as $k => $v) {
            $query[(string)$k] = is_string($v) ? $v : '';
        }

        $ip = '0.0.0.0';
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // take first IP from XFF
            $ip = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        return new self($method, $path, $query, $raw, $headers, $json, $ip);
    }
}
