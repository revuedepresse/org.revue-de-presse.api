<?php

namespace WeavingTheWeb\Bundle\ApiBundle\View;

use Symfony\Bundle\FrameworkBundle\Templating\TemplateReference,
    Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use FOS\RestBundle\View\View as BaseView;

/**
 * Class View
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 * @package WeavingTheWeb\Bundle\ApiBundle\View
 */
class View extends BaseView
{
    /**
     * Sets template to use for the encoding
     *
     * @param string|TemplateReference $template template to be used in the encoding
     *
     * @throws \InvalidArgumentException if the template is neither a string nor an instance of TemplateReference
     */
    public function setTemplate($template)
    {
        if (!(is_string($template) || $template instanceof TemplateReference || $template instanceof Template)) {
            throw new \InvalidArgumentException('The template should be a string or extend TemplateReference');
        }

        if ((!is_string($template) && ($template instanceof Template))) {
            $this->setTemplate($template->getTemplate());
        } else {
            parent::setTemplate($template);
        }

        return $this;
    }
}
