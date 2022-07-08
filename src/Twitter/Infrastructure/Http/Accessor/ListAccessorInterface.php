<?php
declare (strict_types=1);

namespace App\Twitter\Infrastructure\Http\Accessor;

use App\Twitter\Infrastructure\Http\Resource\ResourceList;
use App\Twitter\Infrastructure\Http\Selector\ListSelector;
use Closure;

interface ListAccessorInterface
{
    public function getListAtCursor(ListSelector $selector, Closure $onFinishCollection = null): ResourceList;
}
