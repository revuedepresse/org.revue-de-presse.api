<?php
$exceptions = null;

$tifa = new Extjs_Generator('tifa.js');

$unit_testing = <<< EOD
{$tifa->ExtJsAnomFunction(array('ct', 'item', 'data'), 'bookmark', 'root', 'grid', '')}
<br />
{$tifa->ExtJsInstantiateClass('myClass', 'myObject', '', false)}
<br />
{$tifa->ExtJsSetObjectMember('myObject', 'attribute', 'value')}
<br />
{$tifa->ExtJsAssignVariableValue('body', 'message')}
<br />
{$tifa->ExtJsSetObjectMember('Xeon.module', 'df', 'body', JS_CLASS_SERIALIZED_XEON_MODULE)}
EOD;

$firephp = FirePHP::getInstance(true);
$firephp->log($tifa->getObjects());

/*

try {
    echo $tifa->ExtJsAnomFunction(array('ct', 'item', 'data'), 'bookmark', 'root', 'grid', '');
} catch (Exception $object_instantiating) {
    $exceptions .= $object_instantiating;
}

*/
/*
try {
    echo $tifa->ExtJsInstantiateClass('Array', 'training_grid');
} catch (Exception $object_instantiating) {
    $exceptions .= $object_instantiating;
}
*/
?>