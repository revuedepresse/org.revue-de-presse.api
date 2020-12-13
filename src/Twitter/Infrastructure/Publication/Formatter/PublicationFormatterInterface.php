<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Publication\Formatter;

use App\Twitter\Infrastructure\Operation\Collection\Collection;

interface PublicationFormatterInterface
{
    public function format(Collection $collection): Collection;
}