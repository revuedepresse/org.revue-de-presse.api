<?php
declare (strict_types=1);

namespace App\Tests\Builder\Twitter\Api\Accessor;

use App\Infrastructure\Twitter\Api\Accessor\FriendsAccessor;
use App\Infrastructure\Twitter\Api\Accessor\FriendsAccessorInterface;
use App\Twitter\Api\ApiAccessorInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Log\NullLogger;

class FriendsAccessorBuilder extends TestCase
{
    /**
     * @return FriendsAccessorInterface
     */
    public static function make(): FriendsAccessorInterface
    {
        $testCase = new self();

        /** @var ApiAccessorInterface $apiAccessor */
        $apiAccessor = $testCase->prophesize(ApiAccessorInterface::class);
        $apiAccessor->getApiBaseUrl()->willReturn('https://twitter.api');
        $apiAccessor->contactEndpoint(Argument::any())
            ->will(function ($arguments) {
                $endpoint = $arguments[0];

                if (strpos($endpoint, 'cursor=-1') !== false) {
                    $resourcePath = '../../../../Resources/FriendsList-1-2.b64';
                } else if (strpos($endpoint, 'cursor=1558953799112594071') !== false) {
                    $resourcePath = '../../../../Resources/FriendsList-2-2.b64';
                } else {
                    return [];
                }

                return unserialize(
                    base64_decode(
                        file_get_contents(__DIR__ . 'FriendsAccessorBuilder.php/' .$resourcePath)
                    )
                );
            });

        return new FriendsAccessor(
            $apiAccessor->reveal(),
            new NullLogger()
        );
    }
}