<?php

use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\AnnotationReader;

$loader = include __DIR__ . '/../vendor/autoload.php';

// intl
if (!function_exists('intl_get_error_code')) {
    require_once __DIR__ . '/../vendor/symfony/symfony/src/Symfony/Component/Locale/Resources/stubs/functions.php';

    $loader->add('', __DIR__ . '/../vendor/symfony/symfony/src/Symfony/Component/Locale/Resources/stubs');
}

AnnotationRegistry::registerLoader(array($loader, 'loadClass'));

# ignore behat mink annotations
AnnotationReader::addGlobalIgnoredName('BeforeScenario');
AnnotationReader::addGlobalIgnoredName('Given');
AnnotationReader::addGlobalIgnoredName('When');
AnnotationReader::addGlobalIgnoredName('Then');
AnnotationReader::addGlobalIgnoredName('BeforeSuite');
AnnotationReader::addGlobalIgnoredName('AfterScenario');

return $loader;