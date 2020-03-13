<?php

declare(strict_types=1);

namespace App\Infrastructure\Collection\Repository;

use App\Twitter\Api\ApiAccessorInterface;

interface MemberProfileCollectedEventRepositoryInterface
{
    public const OPTION_SCREEN_NAME = 'screen_name';

    public function collectedMemberProfile(
        ApiAccessorInterface $accessor,
        array $options
    );
}