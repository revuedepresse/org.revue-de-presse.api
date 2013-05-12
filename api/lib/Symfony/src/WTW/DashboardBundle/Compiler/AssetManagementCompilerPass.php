<?php

namespace WTW\DashboardBundle\Compiler;

use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class AssetManagementCompilerPass
 *
 * @package WTW\DashboardBundle\Compiler
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class AssetManagementCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        /**
         * @var $configuration ConfigurationInterface
         */
        $configuration = $container->getExtensionConfig('wtw_dashboard');
        $offlineMode = $container->getParameter(trim($configuration[0]['offline_mode'], '%'));

        if ($offlineMode) {
            $service = [
                [
                    'id'       => 'sp_bower.dependency_cache_warmer',
                    'substitute' => 'wtw.dashboard.offline_cache_warmer.class'
                ],
                [
                    'id'       => 'sp_bower.assetic.bower_resource',
                    'substitute' => 'wtw.dashboard.offline_configuration_resource.class'
                ]
            ];

            foreach ($service as $service) {
                $container->removeDefinition($service['id']);

                $class = $container->getParameter($service['substitute']);
                $definition = new Definition($class);
                $container->addDefinitions([$service['id'] => $definition]);
            }
        }
    }
}