<?php

namespace App\Twitter\Domain\Http\Client\Fallback;

use App\Membership\Domain\Repository\MemberRepositoryInterface;
use App\Twitter\Domain\Http\Client\TwitterAPIEndpointsAwareInterface;
use App\Twitter\Domain\Http\Selector\ListSelectorInterface;
use App\Twitter\Domain\Operation\Collection\CollectionInterface;
use App\Twitter\Infrastructure\Http\Resource\MemberIdentity;
use App\Twitter\Infrastructure\Http\Resource\OwnershipCollectionInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

interface TwitterHttpApiClientInterface extends TwitterAPIEndpointsAwareInterface
{
    public function get(string $endpoint): ResponseInterface;

    public function getMemberTimeline(MemberIdentity $memberIdentity, MemberRepositoryInterface $memberRepository): CollectionInterface;

    public function getMemberProfile(MemberIdentity $memberIdentity): \stdClass;

    public function getMemberOwnerships(ListSelectorInterface $selector): OwnershipCollectionInterface;
}