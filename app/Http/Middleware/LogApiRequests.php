<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class LogApiRequests
{
    /**
     * @var list<string>
     */
    private const SENSITIVE_KEYS = [
        'password',
        'password_confirmation',
        'current_password',
        'token',
        'access_token',
        'refresh_token',
        'authorization',
        'api_token',
    ];

    private const MAX_BODY_LENGTH = 10_000;

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        try {
            $this->writeLog($request, $response);
        } catch (Throwable) {
            // Never break the API if logging fails.
        }

        return $response;
    }

    private function writeLog(Request $request, Response $response): void
    {
        $requestBody = $this->stringify($this->requestPayload($request));
        $responseBody = $this->stringify($this->responsePayload($response));

        $requestLine = sprintf(
            '%s %s %s',
            $request->method(),
            $request->fullUrl(),
            $requestBody
        );

        $responseLine = sprintf(
            '%s %s',
            $response->getStatusCode(),
            $responseBody
        );

        $entry = sprintf(
            "[%s]\n%s\n%s\n\n",
            now()->toDateTimeString(),
            $requestLine,
            $responseLine
        );

        $path = storage_path('logs/api-'.now()->format('Y-m-d').'.log');

        file_put_contents($path, $entry, FILE_APPEND | LOCK_EX);
    }

    /**
     * @return array<string, mixed>
     */
    private function requestPayload(Request $request): array
    {
        $payload = $request->except(self::SENSITIVE_KEYS);

        foreach (self::SENSITIVE_KEYS as $key) {
            if ($request->exists($key)) {
                $payload[$key] = '[REDACTED]';
            }
        }

        foreach ($request->allFiles() as $key => $file) {
            if (is_array($file)) {
                $payload[$key] = '[FILES: '.count($file).']';

                continue;
            }

            $payload[$key] = sprintf(
                '[FILE: %s (%d bytes)]',
                $file->getClientOriginalName(),
                $file->getSize() ?? 0
            );
        }

        return $this->redact($payload);
    }

    private function responsePayload(Response $response): mixed
    {
        if ($response instanceof BinaryFileResponse || $response instanceof StreamedResponse) {
            return '[binary/streamed response]';
        }

        $content = $response->getContent();

        if ($content === false || $content === '') {
            return '-';
        }

        $decoded = json_decode($content, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            return $this->redact($decoded);
        }

        return $content;
    }

    /**
     * @param  mixed  $value
     * @return mixed
     */
    private function redact(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        $redacted = [];

        foreach ($value as $key => $item) {
            if (is_string($key) && in_array(strtolower($key), self::SENSITIVE_KEYS, true)) {
                $redacted[$key] = '[REDACTED]';

                continue;
            }

            $redacted[$key] = $this->redact($item);
        }

        return $redacted;
    }

    private function stringify(mixed $value): string
    {
        if (is_string($value)) {
            return $this->truncate($value);
        }

        $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($encoded === false) {
            return '[unencodable]';
        }

        return $this->truncate($encoded);
    }

    private function truncate(string $value): string
    {
        if (mb_strlen($value) <= self::MAX_BODY_LENGTH) {
            return $value;
        }

        return mb_substr($value, 0, self::MAX_BODY_LENGTH).'…[truncated]';
    }
}
