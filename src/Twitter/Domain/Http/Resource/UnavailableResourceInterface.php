<?php
declare(strict_types=1);

namespace App\Twitter\Domain\Http\Resource;

use App\Twitter\Domain\Http\TwitterAPIAwareInterface;

interface UnavailableResourceInterface extends TwitterAPIAwareInterface
{
    public function getType(): int;
}
