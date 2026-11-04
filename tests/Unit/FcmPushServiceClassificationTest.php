<?php

namespace Tests\Unit;

use App\Services\Notifications\FcmPushService;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class FcmPushServiceClassificationTest extends TestCase
{
    #[DataProvider('failureProvider')]
    public function test_classifies_fcm_failures(int $status, ?string $errorCode, string $expected): void
    {
        $service = app(FcmPushService::class);

        $this->assertSame($expected, $service->classifyFailure($status, $errorCode));
    }

    /**
     * @return array<string, array{0: int, 1: ?string, 2: string}>
     */
    public static function failureProvider(): array
    {
        return [
            'unregistered' => [404, 'UNREGISTERED', 'invalidate_token'],
            'not_found_status' => [404, 'NOT_FOUND', 'invalidate_token'],
            'invalid_argument' => [400, 'INVALID_ARGUMENT', 'invalidate_token'],
            'sender_mismatch' => [403, 'SENDER_ID_MISMATCH', 'invalidate_token'],
            'bare_404' => [404, null, 'invalidate_token'],
            'auth_401' => [401, 'UNAUTHENTICATED', 'preserve_config'],
            'permission' => [403, 'PERMISSION_DENIED', 'preserve_config'],
            'quota' => [429, 'QUOTA_EXCEEDED', 'preserve_retry'],
            'unavailable' => [503, 'UNAVAILABLE', 'preserve_retry'],
            'internal' => [500, 'INTERNAL', 'preserve_retry'],
            'ambiguous_400' => [400, null, 'preserve_app_error'],
            'payload_422' => [400, 'SOME_OTHER_CODE', 'preserve_app_error'],
        ];
    }

    public function test_token_fingerprint_is_stable_and_short(): void
    {
        $service = app(FcmPushService::class);
        $fp = $service->tokenFingerprint('demo-token-value');

        $this->assertSame(12, strlen($fp));
        $this->assertSame($fp, $service->tokenFingerprint('demo-token-value'));
        $this->assertNotSame($fp, $service->tokenFingerprint('other-token'));
    }
}
