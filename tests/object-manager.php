<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$kernel = new App\Kernel('test', true);
$kernel->boot();

return $kernel->getContainer()->get('doctrine')->getManager();
