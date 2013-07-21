<?php

use Symfony\Component\DependencyInjection\Definition;

$container->register(
        'weaving_the_web_user_form_type_user',
        'WeavingTheWeb\Bundle\UserBundle\Form\Type\UserType'
    )->addTag('form.type', ['alias' => 'user'])
; // TODO Send a PR to fix error message when numeric value passed instead of 'alias'