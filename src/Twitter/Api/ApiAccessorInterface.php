<?php
declare(strict_types=1);

namespace App\Twitter\Api;

use App\Api\Entity\TokenInterface;

interface ApiAccessorInterface
{
    public function setAccessToken(TokenInterface $token);
}