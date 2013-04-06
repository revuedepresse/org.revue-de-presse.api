<?php

set_include_path(
  dirname(__FILE__).'/../../lib/'.
  PATH_SEPARATOR.
  get_include_path()
);

define('INSIGHT_CONFIG_PATH', dirname(__FILE__).'/../../config/package.json');

require_once(dirname(__FILE__).'/../../lib/FirePHP/Init.php');
