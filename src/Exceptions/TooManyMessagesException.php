<?php

namespace VendorName\Conversa\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TooManyMessagesException extends Exception
{
    /**
     * Report the exception.
     */
    public function report(): void
    {
        // Log the rate limit exceeded event if needed
    }

    /**
     * Render the exception into an HTTP response.
     */
    public function render(Request $request): JsonResponse
    {
        $retryAfter = $request->header('Retry-After', 60);
        
        return response()->json([
            'message' => $this->getMessage(),
            'errors' => [
                'rate_limit' => [
                    'Too many messages sent. Please wait before sending more messages.',
                ],
            ],
            'retry_after' => $retryAfter,
            'retry_after_seconds' => (int) $retryAfter,
        ], 429)->withHeaders([
            'Retry-After' => $retryAfter,
            'X-RateLimit-Reset' => now()->addSeconds($retryAfter)->getTimestamp(),
        ]);
    }

    /**
     * Get the HTTP status code for the exception.
     */
    public function getStatusCode(): int
    {
        return 429;
    }

    /**
     * Get the error type for the exception.
     */
    public function getErrorType(): string
    {
        return 'rate_limit_exceeded';
    }

    /**
     * Get additional context for the exception.
     *
     * @return array<string, mixed>
     */
    public function context(): array
    {
        return [
            'error_type' => $this->getErrorType(),
            'status_code' => $this->getStatusCode(),
        ];
    }

    /**
     * Create a new instance for messages rate limit.
     */
    public static function forMessages(int $maxAttempts, int $retryAfter): self
    {
        return new static(
            sprintf(
                'Too many messages sent. Maximum of %d messages allowed per minute. Please wait %d seconds before trying again.',
                $maxAttempts,
                $retryAfter
            )
        );
    }

    /**
     * Create a new instance for reactions rate limit.
     */
    public static function forReactions(int $maxAttempts, int $retryAfter): self
    {
        return new static(
            sprintf(
                'Too many reactions added. Maximum of %d reactions allowed per minute. Please wait %d seconds before trying again.',
                $maxAttempts,
                $retryAfter
            )
        );
    }

    /**
     * Create a new instance for attachments rate limit.
     */
    public static function forAttachments(int $maxAttempts, int $retryAfter): self
    {
        return new static(
            sprintf(
                'Too many attachments uploaded. Maximum of %d uploads allowed per minute. Please wait %d seconds before trying again.',
                $maxAttempts,
                $retryAfter
            )
        );
    }
}
