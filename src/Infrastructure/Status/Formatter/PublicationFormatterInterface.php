<?php
declare(strict_types=1);

namespace App\Infrastructure\Status\Formatter;

use App\Infrastructure\Operation\Collection\Collection;

interface PublicationFormatterInterface
{
    public function format(Collection $collection): Collection;
}