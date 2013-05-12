<?php

namespace WTW\DashboardBundle\Assetic;

use Symfony\Bundle\AsseticBundle\Factory\Resource\ConfigurationResource;

/**
 * Class OfflineConfigurationLoader
 *
 * @package WTW\DashboardBundle\Assetic
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class OfflineConfigurationLoader extends ConfigurationResource
{
    public function __construct()
    {
    }

    public function getContent()
    {
        return unserialize('a:8:{s:13:"bootstrap_css";a:3:{i:0;a:2:{i:0;s:11:"@jquery_css";i:1;s:143:"/Users/labodev/repositories/wtw.git/api/lib/Symfony/src/WTW/DashboardBundle/Resources/public/components/bootstrap/docs/assets/css/bootstrap.css";}i:1;a:0:{}i:2;a:0:{}}s:12:"bootstrap_js";a:3:{i:0;a:2:{i:0;s:10:"@jquery_js";i:1;s:141:"/Users/labodev/repositories/wtw.git/api/lib/Symfony/src/WTW/DashboardBundle/Resources/public/components/bootstrap/docs/assets/js/bootstrap.js";}i:1;a:0:{}i:2;a:0:{}}s:10:"jquery_css";a:3:{i:0;a:0:{}i:1;a:0:{}i:2;a:0:{}}s:9:"jquery_js";a:3:{i:0;a:1:{i:0;s:120:"/Users/labodev/repositories/wtw.git/api/lib/Symfony/src/WTW/DashboardBundle/Resources/public/components/jquery/jquery.js";}i:1;a:0:{}i:2;a:0:{}}s:11:"angular_css";a:3:{i:0;a:0:{}i:1;a:0:{}i:2;a:0:{}}s:10:"angular_js";a:3:{i:0;a:1:{i:0;s:122:"/Users/labodev/repositories/wtw.git/api/lib/Symfony/src/WTW/DashboardBundle/Resources/public/components/angular/angular.js";}i:1;a:0:{}i:2;a:0:{}}s:16:"font_awesome_css";a:3:{i:0;a:0:{}i:1;a:0:{}i:2;a:0:{}}s:15:"font_awesome_js";a:3:{i:0;a:0:{}i:1;a:0:{}i:2;a:0:{}}}');
    }
}