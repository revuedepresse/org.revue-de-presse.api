<?php

namespace WeavingTheWeb\Bundle\ApiBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class WeavingTheWebApiExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $this->processConfiguration($configuration, $configs);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.xml');

        $environment = $container->getParameter('kernel.environment');
        if (
            $environment === 'bdd' ||
            $environment === 'lightweight' ||
            $environment === 'test'
        )
        {
            $searchIndex = 'twitter_test';
        } else {
            $searchIndex = 'twitter';
        }

        $definitionDecorator = new DefinitionDecorator('fos_elastica.provider.prototype.orm');
        $container->setDefinition('weaving_the_web.search_provider.user_status', $definitionDecorator)
            ->setClass('%weaving_the_web.api.search_provider.user_status.class%')
            ->replaceArgument(
                0, new Reference('fos_elastica.object_persister.' . $searchIndex . '.user_status')
            )->replaceArgument(
                1, '%weaving_the_web.api.user_status%'
            )->replaceArgument(
                2, ['query_builder_method' => 'createRemainingUserStatusQueryBuilder']
            )
            ->addTag('fos_elastica.provider', ['index' => 'twitter', 'type' => 'user_status']);
    }
}
