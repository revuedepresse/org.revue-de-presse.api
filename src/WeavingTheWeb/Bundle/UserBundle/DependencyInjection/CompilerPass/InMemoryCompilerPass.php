<?php

namespace WeavingTheWeb\Bundle\UserBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface,
    Symfony\Component\DependencyInjection\ContainerBuilder,
    Symfony\Component\DependencyInjection\Reference;

/**
 * @author  Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class InMemoryCompilerPass implements CompilerPassInterface
{
    /**
     * Inject object manager before calling method on instance of InMemoryUserProvider
     *
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        $inMemoryUserProviderId = 'security.user.provider.concrete.in_memory';
        $environment = $container->getParameter('kernel.environment');

        if (
            $container->hasDefinition($inMemoryUserProviderId) &&
            ($environment === 'test' || $environment == 'lightweight' || $environment == 'bdd')
        ) {
            $inMemoryUserProviderDefinition = $container->getDefinition($inMemoryUserProviderId);

            $methodCalls = $inMemoryUserProviderDefinition->getMethodCalls();
            foreach ($methodCalls as $call) {
                $method = $call[0];
                $inMemoryUserProviderDefinition->removeMethodCall($method);
            }

            $inMemoryUserProviderDefinition->addMethodCall(
                'setObjectManager',
                [new Reference('doctrine.orm.default_entity_manager')]
            );

            foreach ($methodCalls as $call) {
                $method = $call[0];
                $arguments = $call[1];
                $inMemoryUserProviderDefinition->addMethodCall($method, $arguments);
            }

            $container->setDefinition($inMemoryUserProviderId, $inMemoryUserProviderDefinition);
        }
    }
}
