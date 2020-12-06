<?php
declare (strict_types=1);

namespace App\Tests\Builder\Api\AccessToken\Repository;

use App\Api\AccessToken\Repository\TokenRepositoryInterface;
use PHPUnit\Framework\TestCase;

class TokenRepositoryBuilder extends TestCase
{
    public static function make(): TokenRepositoryInterface
    {
        $testCase = new self();

        $tokenRepository = $testCase->prophesize(TokenRepositoryInterface::class);

        return $tokenRepository->reveal();
    }
}