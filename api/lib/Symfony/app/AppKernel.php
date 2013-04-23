<?php

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;

class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = array(
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new Symfony\Bundle\SecurityBundle\SecurityBundle(),
            new Symfony\Bundle\TwigBundle\TwigBundle(),
            new Symfony\Bundle\MonologBundle\MonologBundle(),
            new Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle(),
            new Symfony\Bundle\AsseticBundle\AsseticBundle(),
            new JMS\AopBundle\JMSAopBundle(),
            new JMS\DiExtraBundle\JMSDiExtraBundle($this),
            new JMS\SecurityExtraBundle\JMSSecurityExtraBundle(),
            new JMS\SerializerBundle\JMSSerializerBundle(),
            new Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
            new Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle(),
            new FOS\JsRoutingBundle\FOSJsRoutingBundle(),
            new FOS\RestBundle\FOSRestBundle(),
            new Propel\PropelBundle\PropelBundle(),
            new Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle(),
            new Sp\BowerBundle\SpBowerBundle(),
            new Knp\Bundle\MarkdownBundle\KnpMarkdownBundle(),
            new WTW\API\FacebookBundle\WTWAPIFacebookBundle(),
            new WTW\API\DataMiningBundle\WTWAPIDataMiningBundle(),
            new WTW\API\GithubBundle\WTWAPIGithubBundle(),
            new WTW\API\TwitterBundle\WTWAPITwitterBundle(),
            new WTW\CodeGeneration\QualityAssuranceBundle\WTWCodeGenerationQualityAssuranceBundle(),
            new WTW\Documentation\MarkdownBundle\WTWDocumentationMarkdownBundle(),
            new WTW\Legacy\ProviderBundle\WTWLegacyProviderBundle(),
            new WTW\CodeGeneration\AnalysisBundle\WTWCodeGenerationAnalysisBundle(),
            new WTW\API\GoogleDriveBundle\WTWAPIGoogleDriveBundle(),
            new WTW\DashboardBundle\WTWDashboardBundle(),
        );

        if (in_array($this->getEnvironment(), array('dev', 'test'))) {
            $bundles[] = new Symfony\Bundle\WebProfilerBundle\WebProfilerBundle();
            $bundles[] = new Sensio\Bundle\DistributionBundle\SensioDistributionBundle();
            $bundles[] = new Sensio\Bundle\GeneratorBundle\SensioGeneratorBundle();
        }

        return $bundles;
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(__DIR__ . '/config/config_' . $this->getEnvironment() . '.yml');
    }
}
