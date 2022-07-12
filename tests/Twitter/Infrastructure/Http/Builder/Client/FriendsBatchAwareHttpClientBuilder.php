<?php
declare (strict_types=1);

namespace App\Tests\Twitter\Infrastructure\Http\Builder\Client;

use App\Twitter\Domain\Http\Client\HttpClientInterface;
use App\Twitter\Infrastructure\Http\Client\FriendsBatchAwareHttpClient;
use App\Twitter\Domain\Http\Client\CursorAwareHttpClientInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Prophecy\Prophet;
use Psr\Log\NullLogger;

class FriendsBatchAwareHttpClientBuilder extends TestCase
{
    public static function build(): CursorAwareHttpClientInterface
    {
        $testCase = new class() extends TestCase {
            use ProphecyTrait;

            public function __construct()
            {
                $this->prophet = $this->getProphet();
            }

            public function prophesize(?string $classOrInterface = null): ObjectProphecy {
                return $this->prophet->prophesize($classOrInterface);
            }
        };

        /** @var \App\Twitter\Domain\Http\Client\HttpClientInterface $httpClient */
        $httpClient = $testCase->prophesize(HttpClientInterface::class);
        $httpClient->getApiBaseUrl()->willReturn('https://twitter.api');
        $httpClient->contactEndpoint(Argument::any())
            ->will(function ($arguments) {
                $endpoint = $arguments[0];

                if (strpos($endpoint, 'cursor=-1') !== false) {
                    $resourcePath = '../../../../../Resources/FriendsList-1-2.b64';
                } else if (strpos($endpoint, 'cursor=1558953799112594071') !== false) {
                    $resourcePath = '../../../../../Resources/FriendsList-2-2.b64';
                } else {
                    return [];
                }

                return unserialize(
                    base64_decode(
                        file_get_contents(__DIR__ . '/' .$resourcePath)
                    )
                );
            });

        return new FriendsBatchAwareHttpClient(
            $httpClient->reveal(),
            new NullLogger()
        );
    }
}
