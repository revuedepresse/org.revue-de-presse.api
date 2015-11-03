<?php

namespace WeavingTheWeb\Bundle\DashboardBundle\Twig;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @author  Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class PerspectiveExtension extends \Twig_Extension
{
    const EXCEPTION_COLUMN_TOO_SHORT = 1;
    
    const PREFIX_ABSENT_FROM_HEADER_EXPORTABLE = 'exp_';

    const PREFIX_ABSENT_FROM_HEADER_HIDDEN = 'hid_';
    
    const PREFIX_TRANSFORMABLE_RAW = 'raw_';

    const PREFIX_TRANSFORMABLE_TRUNCATED = 'trc_';

    const PREFIX_TRANSFORMABLE_PRE_FORMATTED = 'pre_';

    const PREFIX_TRANSFORMABLE_ACTIONABLE = 'btn_';

    const PREFIX_TRANSFORMABLE_IMAGE = 'img_';
    
    const PREFIX_TRANSFORMABLE_LINK = 'lnk_';

    /**
     * @var \Symfony\Bundle\TwigBundle\TwigEngine
     */
    public $templating;

    /**
     * @var ContainerInterface
     */
    public $container;

    /**
     * @param $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function getFilters()
    {
        $htmlSafeOption = ['is_safe' => ['all']];
        $this->templating = $this->container->get('templating');

        return array(
            new \Twig_SimpleFilter('is_transformable', array($this, 'isTransformable')),
            new \Twig_SimpleFilter('absent_from_header', array($this, 'isAbsentFromHeader')),
            new \Twig_SimpleFilter('column_name', array($this, 'getColumnName')),
            new \Twig_SimpleFilter('get_button', array($this, 'getButton'), $htmlSafeOption),
            new \Twig_SimpleFilter('get_export_button', array($this, 'getExportButton'), ['is_safe' => ['all']]),
            new \Twig_SimpleFilter('get_hidden_content', array($this, 'getHiddenContent'), $htmlSafeOption),
            new \Twig_SimpleFilter('get_image', array($this, 'getImage'), $htmlSafeOption),
            new \Twig_SimpleFilter('get_link', array($this, 'getLink'), $htmlSafeOption),
            new \Twig_SimpleFilter('get_pre_formatted_text', array($this, 'getPreFormattedText'), $htmlSafeOption),
            new \Twig_SimpleFilter('get_raw_json', array($this, 'getRawJson'), $htmlSafeOption),
            new \Twig_SimpleFilter('get_truncated_text', array($this, 'getTruncatedText'), $htmlSafeOption),
        );
    }

    public function getExportButton($subject)
    {
        return $this->templating->render(
            'WeavingTheWebDashboardBundle:Perspective/Table/Body:_get_export_button.html.twig', [
                'value' => $subject
            ]
        );
    }

    public function getLink($subject, $columns)
    {
        return $this->templating->render(
            'WeavingTheWebDashboardBundle:Perspective/Table/Body:_get_link.html.twig', [
                'value' => $subject,
                'columns' => $columns
            ]
        );
    }

    public function getImage($subject)
    {
        return $this->templating->render(
            'WeavingTheWebDashboardBundle:Perspective/Table/Body:_get_image.html.twig', [
                'value' => $subject
            ]
        );
    }

    public function getTruncatedText($subject)
    {
        return $this->templating->render(
            'WeavingTheWebDashboardBundle:Perspective/Table/Body:_get_truncated_text.html.twig', [
                'value' => $subject
            ]
        );
    }

    public function getButton($subject, $columns)
    {
        return $this->templating->render(
            'WeavingTheWebDashboardBundle:Perspective/Table/Body:_get_button.html.twig', [
                'value' => $subject,
                'columns' => $columns
            ]
        );
    }

    public function getPreFormattedText($subject)
    {
        return $this->templating->render(
            'WeavingTheWebDashboardBundle:Perspective/Table/Body:_get_pre_formatted_text.html.twig', [
                'value' => $subject
            ]
        );
    }

    public function getHiddenContent($subject)
    {
        return $this->templating->render(
            'WeavingTheWebDashboardBundle:Perspective/Table/Body:_get_hidden_content.html.twig', [
                'value' => $subject
            ]
        );
    }

    public function getRawJson($subject)
    {
        return $this->templating->render(
            'WeavingTheWebDashboardBundle:Perspective/Table/Body:_get_raw_json.html.twig', [
                'value' => $subject
            ]
        );
    }

    public function getColumnName($subject)
    {
        if ($this->isTransformable($subject)) {
            $columnName = substr($subject, 4);
        } elseif ($this->isAbsentFromHeader($subject)) {
            $columnName = '';
        } else {
            $columnName = $subject;
        }

        return $columnName;
    }

    public function isTransformable($subject)
    {
        try {
            $prefix = $this->parsePrefix($subject);
            return $this->isFlaggedPrefix($prefix, 'PREFIX_TRANSFORMABLE_');
        } catch (\Exception $exception) {
            if ($exception->getCode() == self::EXCEPTION_COLUMN_TOO_SHORT) {
                return false;
            } else {
                throw $exception;
            }
        }
    }

    protected function mayHaveValidPrefix($subject)
    {
        return strlen($subject) >= 4;
    }

    public function isAbsentFromHeader($subject)
    {
        try {
            $prefix = $this->parsePrefix($subject);
            return $this->isFlaggedPrefix($prefix, 'PREFIX_ABSENT_FROM_HEADER_');
        } catch (\Exception $exception) {
            if ($exception->getCode() == self::EXCEPTION_COLUMN_TOO_SHORT) {
                return false;
            } else {
                throw $exception;
            }
        }
    }

    public function getName()
    {
        return 'perspective';
    }

    /**
     * @param $subject
     * @return string
     * @throws \Exception
     */
    protected function parsePrefix($subject)
    {
        if ($this->mayHaveValidPrefix($subject)) {
            return substr($subject, 0, 4);
        } else {
            throw new \Exception(
                'This column name is too short to contain a valid suffix',
                self::EXCEPTION_COLUMN_TOO_SHORT
            );
        }
    }

    /**
     * @return array
     */
    protected function getConstants()
    {
        $reflection = new \ReflectionClass(__NAMESPACE__ . '\PerspectiveExtension');

        return $reflection->getConstants();
    }

    /**
     * @return array
     */
    protected function getFlippedConstants()
    {
        $constants = $this->getConstants();

        return array_flip($constants);
    }

    /**
     * @param $flag
     * @return array
     */
    protected function getFlaggedPrefixes($flag)
    {
        $constants = $this->getConstants();

        $transformablePrefixes = array_filter(
            array_keys($constants),
            function ($key) use ($flag) {
                if (strlen($key) < strlen($flag)) {
                    return false;
                }

                return substr($key, 0, strlen($flag)) === $flag;
            }
        );

        return array_flip($transformablePrefixes);
    }

    /**
     * @param $prefix
     * @param $flag
     * @return bool
     */
    protected function isFlaggedPrefix($prefix, $flag)
    {
        $flaggedPrefixes = $this->getFlaggedPrefixes($flag);
        $flippedConstants = $this->getFlippedConstants();

        return (array_key_exists($prefix, $flippedConstants) &&
            array_key_exists($flippedConstants[$prefix], $flaggedPrefixes));
    }
}
