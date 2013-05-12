<?php

namespace WTW\DashboardBundle\CacheWarmer;

use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

class OfflineCacheWarmer implements CacheWarmerInterface
{
    public function isOptional()
    {
        return false;
    }

    public function warmUp($cacheDir)
    {
        // do nothing whenever offline mode is enabled
    }
}