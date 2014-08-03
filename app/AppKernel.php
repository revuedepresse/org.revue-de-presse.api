<?php

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\Loader;

/**
 * Registers bundles
 *
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class AppKernel extends Kernel
{
    /**
     * @return array|\Symfony\Component\HttpKernel\Bundle\BundleInterface[]
     */
    public function registerBundles()
    {
        $bundles = array(
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new Symfony\Bundle\SecurityBundle\SecurityBundle(),
            new Symfony\Bundle\TwigBundle\TwigBundle(),
            new Symfony\Bundle\MonologBundle\MonologBundle(),
            new Symfony\Bundle\AsseticBundle\AsseticBundle(),
            new FOS\ElasticaBundle\FOSElasticaBundle(),
            new FOS\JsRoutingBundle\FOSJsRoutingBundle(),
            new FOS\RestBundle\FOSRestBundle(),
            new FOS\UserBundle\FOSUserBundle(),
            new FOS\TwitterBundle\FOSTwitterBundle(),
            new JMS\AopBundle\JMSAopBundle(),
            new JMS\DiExtraBundle\JMSDiExtraBundle($this),
            new JMS\SecurityExtraBundle\JMSSecurityExtraBundle(),
            new JMS\SerializerBundle\JMSSerializerBundle(),
            new Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
            new Doctrine\Bundle\FixturesBundle\DoctrineFixturesBundle(),
            new Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle(),
            new Knp\Bundle\MarkdownBundle\KnpMarkdownBundle(),
            new OldSound\RabbitMqBundle\OldSoundRabbitMqBundle(),
            new Ornicar\ApcBundle\OrnicarApcBundle(),
            new Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle(),
            new Snc\RedisBundle\SncRedisBundle(),
            new Sp\BowerBundle\SpBowerBundle(),
            new Braincrafted\Bundle\BootstrapBundle\BraincraftedBootstrapBundle(),
            new WTW\CodeGeneration\QualityAssuranceBundle\WTWCodeGenerationQualityAssuranceBundle(),
            new WTW\CodeGeneration\AnalysisBundle\WTWCodeGenerationAnalysisBundle(),
            new WTW\UserBundle\WTWUserBundle(),
            new WeavingTheWeb\Bundle\ApiBundle\WeavingTheWebApiBundle(),
            new WeavingTheWeb\Bundle\AmqpBundle\WeavingTheWebAmqpBundle(),
            new WeavingTheWeb\Bundle\DashboardBundle\WeavingTheWebDashboardBundle(),
            new WeavingTheWeb\Bundle\DataMiningBundle\WeavingTheWebDataMiningBundle(),
            new WeavingTheWeb\Bundle\Documentation\MarkdownBundle\WeavingTheWebDocumentationMarkdownBundle(),
            new WeavingTheWeb\Bundle\Legacy\ProviderBundle\WeavingTheWebLegacyProviderBundle(),
            new WeavingTheWeb\Bundle\MappingBundle\WeavingTheWebMappingBundle(),
            new WeavingTheWeb\Bundle\MailBundle\WeavingTheWebMailBundle(),
            new WeavingTheWeb\Bundle\TwitterBundle\WeavingTheWebTwitterBundle(),
            new WeavingTheWeb\Bundle\UserBundle\WeavingTheWebUserBundle(),
        );

        if (in_array($this->getEnvironment(), array('dev', 'test', 'bdd', 'box', 'cache'))) {
            $bundles[] = new Symfony\Bundle\WebProfilerBundle\WebProfilerBundle();
            $bundles[] = new Sensio\Bundle\DistributionBundle\SensioDistributionBundle();
            $bundles[] = new Sensio\Bundle\GeneratorBundle\SensioGeneratorBundle();
            $bundles[] = new Elao\WebProfilerExtraBundle\WebProfilerExtraBundle();
        }

        return $bundles;
    }

    /**
     * @return string
     */
    public function getCacheDir()
    {
        if (in_array($this->environment, array('box'))) {
            return '/dev/shm/weaving-the-web/cache/' .  $this->environment;
        }

        return parent::getCacheDir();
    }

    /**
     * @return string
     */
    public function getLogDir()
    {
        if (in_array($this->environment, array('box'))) {
            return '/dev/shm/weaving-the-web/logs';
        }

        return parent::getLogDir();
    }

    /**
     * @param LoaderInterface $loader
     */
    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(__DIR__ . '/config/environment/config_' . $this->getEnvironment() . '.yml');
        if (in_array($this->getEnvironment(), array('test'))) {
            $loader->load(__DIR__ . '/config/environment/services_' . $this->getEnvironment() . '.xml', 'xml');
        }
    }
}
