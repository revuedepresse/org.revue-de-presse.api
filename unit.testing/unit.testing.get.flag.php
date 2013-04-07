<?php

$class_dumper = $class_application::getDumperClass();

// $flags = Flag_Manager::getFlags(array('usr_id' => 1, 'flg_target' => 32), 2);

$flags = Flag_Manager::getFlags(array('usr_id' => 1));

$class_dumper::log(
    __METHOD__,
    array($flags),
    $verbose_mode
);