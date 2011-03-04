<?php

$class_messenger = $class_application::getMessengerClass();

$class_messenger::checkOutbox();
