<?php

namespace WeavingTheWeb\Bundle\DashboardBundle\Twig;

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
     * @var \Symfony\Component\DependencyInjection\Container
     */
    public $container;

    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('is_transformable', array($this, 'isTransformable')),
            new \Twig_SimpleFilter('absent_from_header', array($this, 'isAbsentFromHeader')),
            new \Twig_SimpleFilter('column_name', array($this, 'getColumnName')),
            new \Twig_SimpleFilter('get_export_button', array($this, 'getExportButton'), ['is_safe' => ['all']]),
        );
    }

    public function getExportButton($subject)
    {
        return $this->container->get('templating')->render(
            'WeavingTheWebDashboardBundle:Perspective/Table/Body:_exp.html.twig', [
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
