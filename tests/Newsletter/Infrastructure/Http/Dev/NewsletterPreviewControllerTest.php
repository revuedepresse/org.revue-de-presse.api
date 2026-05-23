<?php
declare(strict_types=1);

namespace App\Tests\Newsletter\Infrastructure\Http\Dev;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class NewsletterPreviewControllerTest extends WebTestCase
{
    public function test_all_routes_accessible_in_test_env(): void
    {
        $client = self::createClient(['environment' => 'test']);
        foreach (['/', '/daily-report', '/daily-report.txt', '/confirmed', '/confirm-failed', '/unsubscribe-confirm', '/unsubscribed'] as $path) {
            $client->request('GET', '/_dev/newsletter' . $path);
            self::assertResponseIsSuccessful('failed on ' . $path);
        }
    }
}
