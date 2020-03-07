<?php
declare(strict_types=1);

namespace App\Infrastructure\Status\Formatter;

use App\Operation\Collection\Collection;

interface PublicationFormatterInterface
{
    public function format(Collection $collection): Collection;
}