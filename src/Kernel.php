<?php
declare(strict_types=1);

namespace App;

use App\Membership\Infrastructure\Console\AddMemberToPublishersListCommand;
use App\Twitter\Infrastructure\Amqp\Console\DispatchFetchTweetsMessages;
use App\Twitter\Infrastructure\Http\Security\Authorization\Console\AuthorizeApplicationCommand;
use App\Twitter\Infrastructure\PublishersList\Console\ImportMemberPublishersListsCommand;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

/**
 * @author revue-de-presse.org <thierrymarianne@users.noreply.github.com>
 */
class Kernel extends BaseKernel implements CompilerPassInterface
{
    private const CONFIG_EXTS = '.{yaml,xml}';

    use MicroKernelTrait;

    public function process(ContainerBuilder $container): void
    {
        if ($container->getParameter('kernel.environment') === 'test') {
            return;
        }

        $taggedServiceIds = $container->findTaggedServiceIds('console.command');

        array_walk(
            $taggedServiceIds,
            function ($_, $id) use ($container) {
                $definition = $container->findDefinition($id);


                if (!in_array(
                    $id,
                    [
                        AddMemberToPublishersListCommand::class,
                        AuthorizeApplicationCommand::class,
                        DispatchFetchTweetsMessages::class,
                        ImportMemberPublishersListsCommand::class
                    ],
                    true
                )) {
                    $definition->addMethodCall('setHidden', [true]);
                }
            }
        );
    }

    public function registerBundles(): iterable
    {
        $contents = require $this->getProjectDir().'/config/bundles.php';
        foreach ($contents as $class => $envs) {
            if ($envs[$this->environment] ?? $envs['all'] ?? false) {
                yield new $class();
            }
        }
    }

    public function getProjectDir(): string
    {
        return \dirname(__DIR__);
    }

    /**
     * @return string
     */
    public function getCacheDir(): string
    {
        return dirname(__DIR__).'/var/cache/'.$this->getEnvironment();
    }

    /**
     * @return string
     */
    public function getLogDir(): string
    {
        return dirname(__DIR__).'/var/log';
    }

    protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader): void
    {
        $container->addResource(new FileResource($this->getProjectDir().'/config/bundles.php'));
        $container->setParameter('container.dumper.inline_class_loader', true);
        $confDir = $this->getProjectDir().'/config';

        $loader->load($confDir.'/{packages}/*'.self::CONFIG_EXTS, 'glob');
        $loader->load($confDir.'/{packages}/'.$this->environment.'/**/*'.self::CONFIG_EXTS, 'glob');
        $loader->load($confDir.'/{services}'.self::CONFIG_EXTS, 'glob');
        $loader->load($confDir.'/{services}_'.$this->environment.self::CONFIG_EXTS, 'glob');
    }
}
