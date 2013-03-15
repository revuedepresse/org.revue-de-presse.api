<?php

$file = dirname(__FILE__) . '/../../config/form.bidule.yaml';

// set a draw
$record = array(
    FIELD_NAME_LOGIN
);

$fields = array(
    array(
        AFFORDANCE_DISPLAY_DEFAULT_VALUE => 'Sign in',
        HTML_ATTRIBUTE_ID => FIELD_NAME_LOGIN,
        HTML_ATTRIBUTE_NAME => FIELD_NAME_LOGIN,
        HTML_ATTRIBUTE_TABINDEX => 0,
        HTML_ATTRIBUTE_TYPE => 'email'.SUFFIX_MANDATORY
    ),
    array(
        AFFORDANCE_CHECK_RECORDS => $record,
        HTML_ATTRIBUTE_ID => FIELD_NAME_RENEW_PASSWORD,												
        HTML_ATTRIBUTE_NAME => FIELD_NAME_RENEW_PASSWORD,
        HTML_ATTRIBUTE_TABINDEX => 1,
        HTML_ATTRIBUTE_TYPE => 'submit'
    )
);

try
{
    // serialize YAML contents from options
    yaml::serialize( $fields, PROPERTY_YAML_FOLDING_DEPTH, STORE_YAML, $file );
}
catch (Exception $exception)
{
    $dumper = new dumper(						
        __CLASS__, __METHOD__,
        array(
            'An exception has been caught while ' .
                'calling y a m l  : :  s e r i a l i z e:',
            $exception
        ),
        DEBUGGING_DISPLAY_EXCEPTION, AFFORDANCE_CATCH_EXCEPTION
    );
}