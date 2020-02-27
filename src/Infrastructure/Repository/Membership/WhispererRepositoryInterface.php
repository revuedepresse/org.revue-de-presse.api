<?php

namespace App\Domain\Membership;

use App\Api\Entity\Whisperer;

interface WhispererRepositoryInterface
{
    public function declareWhisperer(Whisperer $whisperer): Whisperer;
    public function saveWhisperer(Whisperer $whisperer): Whisperer;
    public function forgetAboutWhisperer(Whisperer $whisperer): void;
}