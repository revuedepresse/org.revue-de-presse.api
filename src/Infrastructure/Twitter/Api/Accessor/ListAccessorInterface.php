<?php
declare (strict_types=1);

namespace App\Infrastructure\Twitter\Api\Accessor;

use App\Infrastructure\Twitter\Api\Resource\ResourceList;
use App\Infrastructure\Twitter\Api\Selector\ListSelector;
use Closure;

interface ListAccessorInterface
{
    public function getListAtCursor(ListSelector $selector, Closure $onFinishCollection = null): ResourceList;
}