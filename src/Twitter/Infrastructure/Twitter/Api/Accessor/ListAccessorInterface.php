<?php
declare (strict_types=1);

namespace App\Twitter\Infrastructure\Twitter\Api\Accessor;

use App\Twitter\Infrastructure\Twitter\Api\Resource\ResourceList;
use App\Twitter\Infrastructure\Twitter\Api\Selector\ListSelector;
use Closure;

interface ListAccessorInterface
{
    public function getListAtCursor(ListSelector $selector, Closure $onFinishCollection = null): ResourceList;
}