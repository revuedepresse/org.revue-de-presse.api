<?php

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\Loader;

require_once __DIR__ . '/AppKernel.php';

class _Kernel extends AppKernel
{
    /**
     * @var \Closure
     */
    private $kernelModifier = null;

    public function boot()
    {
        parent::boot();

        if ($kernelModifier = $this->kernelModifier) {
            $kernelModifier($this);
            $this->kernelModifier = null;
        };
    }

    public function setKernelModifier(\Closure $kernelModifier)
    {
        $this->kernelModifier = $kernelModifier;

        // We force the kernel to shutdown to be sure the next request will boot it
        $this->shutdown();
    }
}
