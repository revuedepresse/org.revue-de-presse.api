<?php
declare(strict_types=1);

namespace App\Tests\Healthcheck\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @group http
 */
class HealthcheckControllerTest extends WebTestCase
{
    public function test_get_returns_empty_json_payload_with_200(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/healthcheck');

        $response = $client->getResponse();

        self::assertSame(200, $response->getStatusCode());
        self::assertJson($response->getContent());
        self::assertSame('[]', $response->getContent());
    }

    public function test_options_preflight_returns_cors_response(): void
    {
        $client = static::createClient();
        $client->request('OPTIONS', '/api/healthcheck');

        self::assertSame(200, $client->getResponse()->getStatusCode());
    }
}
