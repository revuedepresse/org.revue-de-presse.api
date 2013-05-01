<?php

namespace WTW\DashboardBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Sp\BowerBundle\Bower\BowerEvent,
    Sp\BowerBundle\Bower\BowerEvents;

/**
 * Class BowerExecutionListener
 *
 * @package WTW\DashboardBundle\Listener
 */
class BowerEventSubscriber implements EventSubscriberInterface
{
    protected $configDir;

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            BowerEvents::PRE_EXEC => array('onPreExec', 10),
        );
    }

    public function setConfigurationDir($dir)
    {
        $this->configDir = $dir;
    }
    public function onPreExec(BowerEvent $event)
    {
        $configuration = $event->getConfiguration();
//        $configuration->setDirectory($this->configDir);
        $event->setConfiguration($configuration);
    }
}