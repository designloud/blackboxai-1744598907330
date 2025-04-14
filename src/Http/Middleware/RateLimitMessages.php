<?php

namespace VendorName\Conversa\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Cache\RateLimiter;
use Symfony\Component\HttpFoundation\Response;
use VendorName\Conversa\Exceptions\TooManyMessagesException;

class RateLimitMessages
{
    /**
     * The rate limiter instance.
     *
     * @var \Illuminate\Cache\RateLimiter
     */
    protected $limiter;

    /**
     * Create a new middleware instance.
     */
    public function __construct(RateLimiter $limiter)
    {
        $this->limiter = $limiter;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $key = $this->resolveRequestSignature($request);

        // Get rate limit configuration
        $maxAttempts = config('conversa.rate_limits.messages_per_minute', 30);
        $decayMinutes = 1;

        if ($this->limiter->tooManyAttempts($key, $maxAttempts)) {
            throw new TooManyMessagesException(
                'Too many messages sent. Please wait before sending more messages.',
                Response::HTTP_TOO_MANY_REQUESTS
            );
        }

        $this->limiter->hit($key, $decayMinutes * 60);

        $response = $next($request);

        return $this->addHeaders(
            $response,
            $maxAttempts,
            $this->calculateRemainingAttempts($key, $maxAttempts)
        );
    }

    /**
     * Resolve request signature.
     */
    protected function resolveRequestSignature(Request $request): string
    {
        $user = $request->user();

        if (! $user) {
            return sha1($request->ip());
        }

        $workspace = $request->workspace_id ?? $user->workspace_id;

        return sha1($user->id . '|' . $workspace . '|' . $request->ip());
    }

    /**
     * Add the limit header information to the given response.
     */
    protected function addHeaders(Response $response, int $maxAttempts, int $remainingAttempts): Response
    {
        return $response->withHeaders([
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => $remainingAttempts,
        ]);
    }

    /**
     * Calculate the number of remaining attempts.
     */
    protected function calculateRemainingAttempts(string $key, int $maxAttempts): int
    {
        return $maxAttempts - $this->limiter->attempts($key);
    }

    /**
     * Get the number of attempts for the given key.
     */
    public function attempts(string $key): int
    {
        return $this->limiter->attempts($key);
    }

    /**
     * Get the number of remaining attempts for the given key.
     */
    public function remaining(string $key, int $maxAttempts): int
    {
        return $this->calculateRemainingAttempts($key, $maxAttempts);
    }

    /**
     * Clear the number of attempts for the given key.
     */
    public function clear(string $key): void
    {
        $this->limiter->clear($key);
    }

    /**
     * Get the number of retries left for the given key.
     */
    public function retriesLeft(string $key, int $maxAttempts): int
    {
        return $maxAttempts - $this->attempts($key);
    }

    /**
     * Determine if the given key has been "accessed" too many times.
     */
    public function tooManyAttempts(string $key, int $maxAttempts): bool
    {
        return $this->limiter->tooManyAttempts($key, $maxAttempts);
    }

    /**
     * Get the timestamp of the next available retry.
     */
    public function availableAt(string $key): int
    {
        return $this->limiter->availableAt($key);
    }
}
