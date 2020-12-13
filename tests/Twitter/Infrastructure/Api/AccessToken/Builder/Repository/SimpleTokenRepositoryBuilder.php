<?php
declare (strict_types=1);

namespace App\Tests\Twitter\Infrastructure\Api\AccessToken\Builder\Repository;

use App\Twitter\Infrastructure\Api\AccessToken\Repository\TokenRepositoryInterface;
use PHPUnit\Framework\TestCase;

class SimpleTokenRepositoryBuilder extends TestCase
{
    public static function make(): TokenRepositoryInterface
    {
        $testCase = new self();

        $tokenRepository = $testCase->prophesize(TokenRepositoryInterface::class);

        return $tokenRepository->reveal();
    }
}