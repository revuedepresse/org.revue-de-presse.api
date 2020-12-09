<?php
declare (strict_types=1);

namespace App\Infrastructure\Twitter\Api\Accessor;

use App\Infrastructure\Twitter\Api\Resource\ResourceList;
use Closure;

interface ListAccessorInterface
{
    public function getListAtCursor(string $screenName, string $cursor, Closure $onFinishCollection = null): ResourceList;

    public function getListAtDefaultCursor(string $screenName, Closure $onFinishCollection = null): ResourceList;
}