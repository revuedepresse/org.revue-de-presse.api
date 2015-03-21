<?php

namespace WeavingTheWeb\Bundle\DashboardBundle\Twig;

class EmailExtension extends \Twig_Extension
{
    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('quoted_printable_decode', array($this, 'quotedPrintableDecode')),
        );
    }

    public function quotedPrintableDecode($content)
    {
        return quoted_printable_decode($content);
    }

    public function getName()
    {
        return 'email';
    }
}