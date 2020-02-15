<?php

namespace App\Api\View;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

/**
 * ViewInterface
 *
 * @author Jordi Boggiano <j.boggiano@seld.be>
 * @author Lukas K. Smith <smith@pooteeweet.org>
 */
interface ViewHandlerInterface
{
    /**
     * Verifies whether the given format is supported by this view
     *
     * @param string $format format name
     *
     * @return Boolean
     */
    public function supports($format);

    /**
     * Registers a custom handler
     *
     * The handler must have the following signature: handler($viewObject, $request, $response)
     * It can use the methods of this class to retrieve the needed data and return a
     * Response object ready to be sent.
     *
     * @param string   $format   the format that is handled
     * @param callable $callable callable that can handle the given format
     */
    public function registerHandler($format, $callable);

    /**
     * If the given format uses the templating system for rendering
     *
     * @param string $format
     *
     * @return Boolean
     */
    public function isFormatTemplating($format);

    /**
     * Handles a request with the proper handler
     *
     * Decides on which handler to use based on the request format
     *
     * @param View    $view
     * @param Request $request Request object
     *
     * @return Response
     */
    public function handle($view, Request $request = null);

    /**
     * Create the Response from the view
     *
     * @param View   $view
     * @param string $location
     * @param string $format
     *
     * @return Response
     */
    public function createRedirectResponse($view, $location, $format);

    /**
     * Render the view data with the given template
     *
     * @param View   $view
     * @param string $format
     *
     * @return string
     */
    public function renderTemplate($view, $format);

    /**
     * Prepare view data for use by templating engine.
     *
     * @param View $view
     *
     * @return array
     */
    public function prepareTemplateParameters($view);

    /**
     * Handles creation of a Response using either redirection or the templating/serializer service
     *
     * @param View    $view
     * @param Request $request
     * @param string  $format
     *
     * @return Response
     */
    public function createResponse($view, Request $request, $format);
}
