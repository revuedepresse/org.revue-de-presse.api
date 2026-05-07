<?php
declare(strict_types=1);

namespace App\Tests\Trends\Infrastructure\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @group controller
 */
class TrendsControllerTest extends WebTestCase
{
    public function test_callback_returns_acknowledgement_payload(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/callback');

        $response = $client->getResponse();

        self::assertSame(200, $response->getStatusCode());
        self::assertJson($response->getContent());

        $body = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertIsString($body);
        self::assertStringContainsString("That's all folks!", $body);
    }

    public function test_get_highlights_returns_collection_shape_on_cache_miss(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/twitter/highlights', [
            'startDate'       => '2024-01-01 00:00:00',
            'endDate'         => '2024-01-01 23:59:59',
            'includeRetweets' => '0',
        ]);

        $response = $client->getResponse();

        self::assertSame(200, $response->getStatusCode());
        self::assertTrue($response->headers->has('x-total-pages'));
        self::assertTrue($response->headers->has('x-page-index'));

        $body = json_decode($response->getContent(), true);
        self::assertIsArray($body);
        self::assertArrayHasKey('aggregates', $body);
        self::assertArrayHasKey('statuses', $body);
    }

    public function test_get_highlights_rejects_request_without_required_params(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/twitter/highlights');

        $response = $client->getResponse();

        self::assertContains(
            $response->getStatusCode(),
            [403, 404],
            'Highlights endpoint must reject requests missing required search params'
        );
    }
}
