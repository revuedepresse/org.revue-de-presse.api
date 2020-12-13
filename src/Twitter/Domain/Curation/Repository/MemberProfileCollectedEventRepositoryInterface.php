<?php

declare(strict_types=1);

namespace App\Twitter\Domain\Curation\Repository;

use App\Twitter\Domain\Api\ApiAccessorInterface;

interface MemberProfileCollectedEventRepositoryInterface
{
    public const OPTION_SCREEN_NAME = 'screen_name';

    public function collectedMemberProfile(
        ApiAccessorInterface $accessor,
        array $options
    );
}