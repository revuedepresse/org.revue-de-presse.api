<?php
declare(strict_types=1);

namespace App\Tests\Twitter\Infrastructure\Http\AccessToken\Builder\Repository;

use App\Twitter\Domain\Http\AccessToken\Repository\TokenRepositoryInterface;
use App\Twitter\Domain\Http\Model\TokenInterface;
use Prophecy\Prophet;

class TokenRepositoryBuilder
{
    public static function newTokenRepositoryBuilder()
    {
        return new self();
    }

    private $prophecy;

    public function __construct()
    {
        $prophet = new Prophet();
        $this->prophecy = $prophet->prophesize(TokenRepositoryInterface::class);
    }

    public function build(): TokenRepositoryInterface
    {
        return $this->prophecy->reveal();
    }

    public function willFindATokenOtherThan($excludedToken, $returnedToken): TokenRepositoryBuilder
    {
        $this->prophecy
            ->findTokenOtherThan($excludedToken)
            ->willReturn($returnedToken);

        return $this;
    }

    public function willFindFirstFrozenToken(TokenInterface $token): TokenRepositoryBuilder
    {
        $this->prophecy->findFirstFrozenToken()
             ->willReturn($token);

         return $this;
    }

    public function willReturnTheCountOfUnfrozenTokens($count): TokenRepositoryBuilder
    {
        $this->prophecy
            ->howManyUnfrozenTokenAreThere()
            ->willReturn($count);

        return $this;
    }

    public function willReturnTheCountOfUnfrozenTokensExceptFrom(TokenInterface $token, int $count): TokenRepositoryBuilder
    {
        $this->prophecy
            ->howManyUnfrozenTokenAreThereExceptFrom($token)
            ->willReturn($count);

        return $this;
    }
}