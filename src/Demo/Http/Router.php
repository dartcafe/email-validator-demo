<?php

declare(strict_types=1);

namespace Dartcafe\EmailValidator\Demo\Http;

/**
 * A simple router for handling HTTP requests.
 */
final class Router
{
    /** @var array<string, callable(Request):Response> */
    private array $get = [];
    /** @var array<string, callable(Request):Response> */
    private array $post = [];

    /**
     * Register a GET route
     *
     * @param string   $path    The request path (e.g. "/validate")
     * @param callable $handler The handler function, which takes a Request and returns a Response
     */
    public function get(string $path, callable $handler): void
    {
        $this->get[$path] = $handler;
    }

    /**
     * Register a POST route
     *
     * @param string   $path    The request path (e.g. "/validate")
     * @param callable $handler The handler function, which takes a Request and returns a Response
     */
    public function post(string $path, callable $handler): void
    {
        $this->post[$path] = $handler;
    }

    /**
     * Dispatch the request to the appropriate handler
     *
     * @param Request $r The HTTP request
     * @return Response The HTTP response
     */
    public function dispatch(Request $r): Response
    {
        $map = $r->method === 'POST' ? $this->post : $this->get;
        if (isset($map[$r->path])) {
            return $map[$r->path]($r);
        }
        return new Response(404, "Not found\n", ['Content-Type' => 'text/plain; charset=utf-8']);
    }
}
