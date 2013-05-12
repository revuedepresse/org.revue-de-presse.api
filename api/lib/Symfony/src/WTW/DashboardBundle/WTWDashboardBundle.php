<?php

namespace WTW\DashboardBundle;

use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder,
    Symfony\Component\HttpKernel\Bundle\Bundle;
use WTW\DashboardBundle\Compiler\AssetManagementCompilerPass;

class WTWDashboardBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new AssetManagementCompilerPass());
    }
}
