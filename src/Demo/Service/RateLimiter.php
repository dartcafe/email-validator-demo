<?php

declare(strict_types=1);

namespace Dartcafe\EmailValidator\Demo\Service;

/**
 * File-based token-bucket limiter (per key).
 * - capacity: max tokens in the bucket
 * - refillPerSecond: tokens added per second
 * Safe via flock() and atomic rewrite.
 */
final class RateLimiter
{
    private string $ns;
    private int $capacity;
    private float $refillPerSecond;
    private string $dir;

    /**
     * @param string $namespace   Namespace for the keys (to avoid collisions)
     * @param int    $capacity    Max tokens in the bucket
     * @param float  $refillPerSecond Tokens added per second
     * @param string $storageDir  Directory where state files are stored (will be created if not exists)
     *
     * @throws \RuntimeException if storageDir cannot be created
     */
    public function __construct(string $namespace, int $capacity, float $refillPerSecond, string $storageDir)
    {
        $this->ns = $namespace;
        $this->capacity = $capacity;
        $this->refillPerSecond = $refillPerSecond;
        $this->dir = rtrim($storageDir, DIRECTORY_SEPARATOR);
        if (!is_dir($this->dir) && !@mkdir($this->dir, 0777, true) && !is_dir($this->dir)) {
            throw new \RuntimeException('Cannot create rate storage dir: ' . $this->dir);
        }
    }

    /**
     * Consume tokens. Returns [allowed, retryAfterSeconds].
     * @return array{0:bool,1:float}
     */
    public function hit(string $key, int $cost = 1): array
    {
        $file = $this->fileForKey($key);
        $now  = microtime(true);

        $fp = @fopen($file, 'c+');
        if ($fp === false) {
            // fallback: operate without persistence
            return [true, 0.0];
        }

        try {
            @flock($fp, LOCK_EX);

            // read existing state
            $raw = stream_get_contents($fp);
            if ($raw === false) {
                $raw = '';
            }
            $state = $raw !== '' ? json_decode($raw, true) : null;
            if (!is_array($state) || !isset($state['t'], $state['tokens'])) {
                $state = ['t' => $now, 'tokens' => (float)$this->capacity];
            } else {
                $state = ['t' => (float)$state['t'], 'tokens' => (float)$state['tokens']];
            }

            // refill
            $elapsed = max(0.0, $now - $state['t']);
            $state['tokens'] = min(
                $this->capacity,
                $state['tokens'] + $elapsed * $this->refillPerSecond,
            );

            // decide
            $allowed = false;
            $retryAfter = 0.0;

            if ($state['tokens'] >= $cost) {
                $state['tokens'] -= $cost;
                $allowed = true;
            } else {
                $need = $cost - $state['tokens'];
                $retryAfter = $need / $this->refillPerSecond;
            }
            $state['t'] = $now;

            // write back atomically
            ftruncate($fp, 0);
            rewind($fp);
            $json = json_encode($state, JSON_PRESERVE_ZERO_FRACTION);
            if ($json === false) {
                // Fallback (sollte praktisch nie passieren)
                $json = sprintf('{"t":%.6F,"tokens":%.6F}', $state['t'], $state['tokens']);
            }
            fwrite($fp, $json);
            fflush($fp);
            @flock($fp, LOCK_UN);
            fclose($fp);

            return [$allowed, $retryAfter];
        } catch (\Throwable) {
            if (is_resource($fp)) {
                @flock($fp, LOCK_UN);
                fclose($fp);
            }
            return [true, 0.0]; // fail-open in demo
        }
    }

    private function fileForKey(string $key): string
    {
        $name = hash('sha256', $this->ns . ':' . $key) . '.json';
        return $this->dir . DIRECTORY_SEPARATOR . $name;
    }
}
