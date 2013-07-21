<?php

namespace WeavingTheWeb\Bundle\UserBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder,
    Symfony\Component\Config\FileLocator,
    Symfony\Component\HttpKernel\DependencyInjection\Extension,
    Symfony\Component\DependencyInjection\Loader\XmlFileLoader,
    Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class WeavingTheWebUserExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $fileLocator = new FileLocator(__DIR__ . '/../Resources/config');
        $loader = new XmlFileLoader($container, $fileLocator);
        $loader->load('services.xml');

        $formFileLocator = new FileLocator(__DIR__ . '/../Resources/config/Form');
        $phpLoader = new PhpFileLoader($container, $formFileLocator);
        $phpLoader->load('types.php');
    }
}
