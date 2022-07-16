<?php
declare (strict_types=1);

namespace App\Tests\Twitter\Infrastructure\Http\Builder\Client;

use App\Twitter\Infrastructure\Http\Client\FollowersBatchAwareHttpClient;
use App\Twitter\Domain\Http\Client\CursorAwareHttpClientInterface;
use App\Twitter\Domain\Http\Client\HttpClientInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophet;
use Psr\Log\NullLogger;

class FollowersBatchAwareHttpClientBuilder extends TestCase
{
    use ProphecyTrait;

    private $prophet;

    public function __construct()
    {
        $this->prophet = $this->getProphet();
    }

    public function prophet(): Prophet
    {
        return $this->prophet;
    }

    /**
     * @return CursorAwareHttpClientInterface
     */
    public static function build(): CursorAwareHttpClientInterface
    {
        $testCase = new self();

        /** @var HttpClientInterface $httpClient */
        $httpClient = $testCase->prophet()->prophesize(HttpClientInterface::class);
        $httpClient->getApiBaseUrl()->willReturn('https://twitter.api');
        $httpClient->contactEndpoint(Argument::any())
            ->will(function ($arguments) {
                $endpoint = $arguments[0];

                if (strpos($endpoint, 'cursor=-1') !== false) {
                    $resourcePath = '../../../../../Resources/FollowersList-1-2.b64';
                } else if (strpos($endpoint, 'cursor=1645049967345751374') !== false) {
                    $resourcePath = '../../../../../Resources/FollowersList-2-2.b64';
                } else {
                    return [];
                }

                $resourceFilePath = __DIR__ . '/' .$resourcePath;

                return unserialize(
                    base64_decode(
                        file_get_contents($resourceFilePath)
                    )
                );
            });

        return new FollowersBatchAwareHttpClient(
            $httpClient->reveal(),
            new NullLogger()
        );
    }
}
