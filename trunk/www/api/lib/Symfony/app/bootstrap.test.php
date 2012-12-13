<?php

$legacyBootstrap = '## FILL ABSOLUTE PATH ##';

if (file_exists($legacyBootstrap)) {
    require($legacyBootstrap);
}

require('bootstrap.php.cache');