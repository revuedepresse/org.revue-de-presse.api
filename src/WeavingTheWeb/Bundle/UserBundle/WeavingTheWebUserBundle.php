<?php

namespace WeavingTheWeb\Bundle\UserBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;

use Symfony\Component\HttpKernel\Bundle\Bundle;

use WeavingTheWeb\Bundle\UserBundle\DependencyInjection\CompilerPass\InMemoryCompilerPass;

class WeavingTheWebUserBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new InMemoryCompilerPass());
    }
}
