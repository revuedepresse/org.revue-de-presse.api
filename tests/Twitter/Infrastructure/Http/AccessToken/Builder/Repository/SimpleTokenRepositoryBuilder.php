<?php
declare (strict_types=1);

namespace App\Tests\Twitter\Infrastructure\Http\AccessToken\Builder\Repository;

use App\Twitter\Domain\Http\AccessToken\Repository\TokenRepositoryInterface;
use PHPUnit\Framework\TestCase;

class SimpleTokenRepositoryBuilder extends TestCase
{
    public static function build(): TokenRepositoryInterface
    {
        $testCase = new self();

        $tokenRepository = $testCase->prophesize(TokenRepositoryInterface::class);

        return $tokenRepository->reveal();
    }
}