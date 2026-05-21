<?php
declare(strict_types=1);

namespace App\Tests\TikTok\Infrastructure\Http;

use App\TikTok\Infrastructure\Http\HmacTikTokWebhookVerifier;
use App\TikTok\Infrastructure\Http\TikTokWebhookVerificationException;
use App\TikTok\Infrastructure\Http\UnconfiguredTikTokWebhookException;
use PHPUnit\Framework\TestCase;

/**
 * Pure unit suite for the HMAC verifier. Boots no kernel — drives
 * {@see HmacTikTokWebhookVerifier} directly with a frozen clock so we can
 * craft real signatures and assert on every rejection branch.
 *
 * @group unit
 */
class HmacTikTokWebhookVerifierTest extends TestCase
{
    private const SECRET = 'shared-secret';
    private const NOW    = 1_700_000_000;

    private HmacTikTokWebhookVerifier $verifier;

    protected function setUp(): void
    {
        $this->verifier = new HmacTikTokWebhookVerifier(
            self::SECRET,
            static fn (): int => self::NOW,
        );
    }

    public function test_returns_envelope_when_signature_matches_and_body_is_well_formed(): void
    {
        $body = (string) json_encode([
            'event'        => 'video.publish.complete',
            'client_key'   => 'client_abc',
            'create_time'  => 1_700_000_000,
            'user_openid'  => 'open-1',
            'content'      => ['video_id' => 'vid_42'],
        ]);
        $header = $this->signedHeader(self::NOW, $body);

        $envelope = $this->verifier->verifyAndParse($header, $body);

        self::assertSame('video.publish.complete', $envelope->event);
        self::assertSame('client_abc', $envelope->client_key);
        self::assertSame(1_700_000_000, $envelope->create_time);
        self::assertSame('open-1', $envelope->user_openid);
        self::assertSame(['video_id' => 'vid_42'], $envelope->content);
    }

    public function test_raises_unconfigured_exception_when_secret_is_empty(): void
    {
        $verifier = new HmacTikTokWebhookVerifier('', static fn (): int => self::NOW);

        $this->expectException(UnconfiguredTikTokWebhookException::class);
        $verifier->verifyAndParse('t=1,s=00', '{}');
    }

    public function test_raises_unconfigured_exception_when_secret_is_null(): void
    {
        $verifier = new HmacTikTokWebhookVerifier(null, static fn (): int => self::NOW);

        $this->expectException(UnconfiguredTikTokWebhookException::class);
        $verifier->verifyAndParse('t=1,s=00', '{}');
    }

    public function test_rejects_missing_signature_header(): void
    {
        $this->expectExceptionMessageMatches('/missing TikTok-Signature header/');
        $this->verifier->verifyAndParse(null, '{}');
    }

    public function test_rejects_empty_signature_header(): void
    {
        $this->expectExceptionMessageMatches('/missing TikTok-Signature header/');
        $this->verifier->verifyAndParse('', '{}');
    }

    public function test_rejects_header_missing_timestamp(): void
    {
        $this->expectException(TikTokWebhookVerificationException::class);
        $this->expectExceptionMessageMatches('/malformed TikTok-Signature header/');
        $this->verifier->verifyAndParse('s=deadbeef', '{}');
    }

    public function test_rejects_header_missing_signature_component(): void
    {
        $this->expectException(TikTokWebhookVerificationException::class);
        $this->expectExceptionMessageMatches('/malformed TikTok-Signature header/');
        $this->verifier->verifyAndParse('t=' . self::NOW, '{}');
    }

    public function test_rejects_non_numeric_timestamp(): void
    {
        $this->expectExceptionMessageMatches('/non-numeric signature timestamp/');
        $this->verifier->verifyAndParse('t=not-a-number,s=deadbeef', '{}');
    }

    public function test_rejects_timestamp_outside_replay_window(): void
    {
        $stale = self::NOW - (HmacTikTokWebhookVerifier::MAX_AGE_SECONDS + 1);
        $body  = '{"event":"e","client_key":"k","create_time":1}';
        $header = $this->signedHeader($stale, $body);

        $this->expectExceptionMessageMatches('/timestamp out of window/');
        $this->verifier->verifyAndParse($header, $body);
    }

    public function test_rejects_non_hex_signature(): void
    {
        $this->expectExceptionMessageMatches('/non-hex signature/');
        $this->verifier->verifyAndParse('t=' . self::NOW . ',s=zzzz', '{}');
    }

    public function test_rejects_signature_that_does_not_match(): void
    {
        $body = '{"event":"e","client_key":"k","create_time":1}';
        // Sign with a different secret so the bytes are well-formed but wrong.
        $wrong = hash_hmac('sha256', self::NOW . '.' . $body, 'different-secret');

        $this->expectExceptionMessageMatches('/signature mismatch/');
        $this->verifier->verifyAndParse('t=' . self::NOW . ',s=' . $wrong, $body);
    }

    public function test_rejects_body_that_is_not_valid_json(): void
    {
        $body = 'not json';
        $header = $this->signedHeader(self::NOW, $body);

        $this->expectExceptionMessageMatches('/body is not valid JSON/');
        $this->verifier->verifyAndParse($header, $body);
    }

    public function test_rejects_body_that_is_valid_json_but_not_an_object(): void
    {
        $body = '"just a string"';
        $header = $this->signedHeader(self::NOW, $body);

        $this->expectExceptionMessageMatches('/JSON object|envelope/');
        $this->verifier->verifyAndParse($header, $body);
    }

    public function test_rejects_envelope_missing_required_event_field(): void
    {
        $body = (string) json_encode(['client_key' => 'k', 'create_time' => 1]);
        $header = $this->signedHeader(self::NOW, $body);

        $this->expectExceptionMessageMatches('/malformed event envelope.*event/');
        $this->verifier->verifyAndParse($header, $body);
    }

    private function signedHeader(int $timestamp, string $body): string
    {
        $sig = hash_hmac('sha256', $timestamp . '.' . $body, self::SECRET);

        return 't=' . $timestamp . ',s=' . $sig;
    }
}
