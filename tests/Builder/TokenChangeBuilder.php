<?php
declare(strict_types=1);

namespace App\Tests\Builder;

use App\Api\AccessToken\TokenChangeInterface;
use App\Api\Entity\TokenInterface;
use App\Twitter\Api\ApiAccessorInterface;
use Prophecy\Argument;
use Prophecy\Prophet;

class TokenChangeBuilder
{
    private $prophecy;

    public function __construct()
    {
        $prophet = new Prophet();
        $this->prophecy = $prophet->prophesize(TokenChangeInterface::class);
    }

    public function willReplaceAccessToken(TokenInterface $token): self {
        $this->prophecy->replaceAccessToken(
            Argument::type(TokenInterface::class),
            Argument::type(ApiAccessorInterface::class)
        )->willReturn($token);

        return $this;
    }

    public function build(): TokenChangeInterface
    {
        return $this->prophecy->reveal();
    }
}