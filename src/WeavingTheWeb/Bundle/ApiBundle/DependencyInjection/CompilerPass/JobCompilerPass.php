<?php

namespace WeavingTheWeb\Bundle\ApiBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface,
    Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @author  Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class JobCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->has('weaving_the_web_api.repository.job')) {
            return;
        }

        $definition = $container->findDefinition('weaving_the_web_api.repository.job');
        $taggedServices = $container->findTaggedServiceIds('weaving_the_web_api.job_aware');

        foreach ($taggedServices as $id => $tags) {
            $taggedServiceDefinition = $container->findDefinition($id);
            $taggedServiceDefinition->setProperty('jobRepository', $definition);
        }
    }
}
