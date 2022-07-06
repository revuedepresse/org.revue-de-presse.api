<?php
declare (strict_types=1);

namespace App\Twitter\Domain\Http\Client;

use App\Twitter\Domain\Http\Resource\ResourceList;
use App\Twitter\Domain\Http\Selector\ListSelectorInterface;
use Closure;

interface CursorAwareHttpClientInterface
{
    public function getListAtCursor(ListSelectorInterface $selector, Closure $onFinishCollection = null): ResourceList;
}