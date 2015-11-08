<?php

namespace WeavingTheWeb\Bundle\ApiBundle\EventListener;

use FOS\RestBundle\EventListener\ViewResponseListener as BaseListener;

use JMS\Serializer\SerializationContext;

use Symfony\Component\HttpKernel\Event\FilterControllerEvent,
    Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent,
    Symfony\Component\DependencyInjection\ContainerInterface,
    Symfony\Bundle\FrameworkBundle\Templating\TemplateReference;

use WeavingTheWeb\Bundle\ApiBundle\View\View;

/**
 * The ViewResponseListener class handles the View core event as well as the "@extra:Template" annotation.
 */
class ViewResponseListener extends BaseListener
{
    /**
     * Renders the parameters and template and initializes a new response object with the
     * rendered content.
     *
     * @param GetResponseForControllerResultEvent $event
     * @return array|mixed
     */
    public function onKernelView(GetResponseForControllerResultEvent $event)
    {
        $request = $event->getRequest();
        $configuration = $request->attributes->get('_view');

        $view = $event->getControllerResult();
        if (!$view instanceOf View) {
            if (!$configuration && !$this->container->getParameter('fos_rest.view_response_listener.force_view')) {
                return parent::onKernelView($event);
            }

            $view = new View($view);
        }

        if ($configuration) {
            if ($configuration->getTemplateVar()) {
                $view->setTemplateVar($configuration->getTemplateVar());
            }
            if (null === $view->getStatusCode() && $configuration->getStatusCode()) {
                $view->setStatusCode($configuration->getStatusCode());
            }
            if ($configuration->getSerializerGroups()) {
                $context = $view->getSerializationContext() ?: new SerializationContext();
                $context->setGroups($configuration->getSerializerGroups());
                $view->setSerializationContext($context);
            }
        }

        if (null === $view->getFormat()) {
            $view->setFormat($request->getRequestFormat());
        }

        $vars = $request->attributes->get('_template_vars');
        if (!$vars) {
            $vars = $request->attributes->get('_template_default_vars');
        }

        $viewHandler = $this->container->get('fos_rest.view_handler');

        if ($viewHandler->isFormatTemplating($view->getFormat())) {
            if (!empty($vars)) {
                $parameters = (array) $viewHandler->prepareTemplateParameters($view);
                foreach ($vars as $var) {
                    if (!array_key_exists($var, $parameters)) {
                        $parameters[$var] = $request->attributes->get($var);
                    }
                }
                $view->setData($parameters);
            }

            $template = $request->attributes->get('_template');
            if ($template) {
                if ($template instanceof TemplateReference) {
                    $template->set('format', null);
                }

                $view->setTemplate($template);
            }
        }

        $response = $viewHandler->handle($view, $request);

        $event->setResponse($response);
    }
}
