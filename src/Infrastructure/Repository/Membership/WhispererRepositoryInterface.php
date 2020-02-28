<?php
declare(strict_types=1);

namespace App\Infrastructure\Repository\Membership;

use App\Api\Entity\Whisperer;

interface WhispererRepositoryInterface
{
    public function declareWhisperer(Whisperer $whisperer): Whisperer;
    public function saveWhisperer(Whisperer $whisperer): Whisperer;
    public function forgetAboutWhisperer(Whisperer $whisperer): void;
}