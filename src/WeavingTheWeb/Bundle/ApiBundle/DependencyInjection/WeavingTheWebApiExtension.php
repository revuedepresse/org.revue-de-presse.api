<?php

namespace WeavingTheWeb\Bundle\ApiBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;

use Symfony\Component\Config\FileLocator;

use Symfony\Component\DependencyInjection\Definition,
    Symfony\Component\DependencyInjection\DefinitionDecorator,
    Symfony\Component\DependencyInjection\Reference,
    Symfony\Component\DependencyInjection\Loader;

use Symfony\Component\HttpKernel\DependencyInjection\Extension;

use WeavingTheWeb\Bundle\SearchBundle\DependencyInjection\AbstractSearchProviderAware;

/**
 * @author  Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class WeavingTheWebApiExtension extends AbstractSearchProviderAware
{
    /**
     * @param array $configs
     * @param ContainerBuilder $container
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $this->processConfiguration($configuration, $configs);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.xml');

        $searchIndex  = $container->getParameter('twitter_search_index');

        $this->defineSearchProvider($container, $searchIndex, 'user_status');
    }

    /**
     * @param ContainerBuilder $container
     * @param $searchIndex
     * @param $type
     */
    public function defineSearchProvider(ContainerBuilder $container, $searchIndex, $type)
    {
        $definitionDecorator = new DefinitionDecorator('fos_elastica.provider.prototype.orm');

        $container->setDefinition('weaving_the_web_api.search_provider.' . $type, $definitionDecorator)
            ->setClass('%weaving_the_web.api.search_provider.' . $type . '.class%')
            ->replaceArgument(
                0, new Reference('fos_elastica.object_persister.' . $searchIndex . '.' . $type)
            )->replaceArgument(
                1, new Reference('fos_elastica.indexable')
            )->replaceArgument(
                2, '%weaving_the_web.api.entity.' . $type . '.class%'
            )->replaceArgument(
                3, [
                    'query_builder_method' => 'createRemainingUserStatusQueryBuilder',
                    'indexName' => $searchIndex,
                    'typeName' => $type
                ]
            )->replaceArgument(
                4, new Reference('doctrine')
            )->setProperty('moderator', new Reference('weaving_the_web_supervision.moderator.memory_usage'))
            ->setProperty('translator', new Reference('translator'))
            ->setProperty('logger', new Reference('logger'))
            ->addTag('fos_elastica.provider', ['index' => $searchIndex, 'type' => $type]);
    }
}
