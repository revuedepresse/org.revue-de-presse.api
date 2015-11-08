<?php

namespace WeavingTheWeb\Bundle\ApiBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;

use Symfony\Component\HttpKernel\Bundle\Bundle;

use WeavingTheWeb\Bundle\ApiBundle\DependencyInjection\CompilerPass\JobCompilerPass;

/**
 * @author  Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class WeavingTheWebApiBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new JobCompilerPass());
    }
}
