<?php
declare (strict_types=1);

namespace App\Twitter\Domain\Api\Accessor;

use App\Twitter\Domain\Api\Resource\ResourceList;
use App\Twitter\Domain\Api\Selector\ListSelectorInterface;
use Closure;

interface ListAccessorInterface
{
    public function getListAtCursor(ListSelectorInterface $selector, Closure $onFinishCollection = null): ResourceList;
}