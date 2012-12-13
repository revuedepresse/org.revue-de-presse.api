<?php

$class_dumper = $class_application::getDumperClass();

$class_query = $class_application::getQueryClass();

$query = '
	SELECT
		'.TABLE_ALIAS_STORE.'.'.PREFIX_TABLE_COLUMN_STORE.PROPERTY_ID.'
			'.PROPERTY_ID.',
		'.TABLE_ALIAS_FORM.'.'.
			PREFIX_TABLE_COLUMN_FORM.PROPERTY_ID.'
				'.NAMESPACE_SEMANTIC_FIDELITY.'__'.CLASS_FORM.',
		'.TABLE_ALIAS_STORE.'.'.
			PREFIX_TABLE_COLUMN_STORE.PROPERTY_ID.' '.CLASS_STORE.' ,
		'.TABLE_ALIAS_STORE_ITEM.'.'.
			PREFIX_TABLE_COLUMN_STORE_ITEM.PROPERTY_ID.' '.CLASS_STORE_ITEM.' 
	FROM
		'.TABLE_FORM.' '.TABLE_ALIAS_FORM.'
	LEFT JOIN
		'.TABLE_STORE.' '.TABLE_ALIAS_STORE.'
	USING
		( '.PREFIX_TABLE_COLUMN_STORE.PROPERTY_ID.' )
	LEFT JOIN
		'.TABLE_STORE_ITEM.' '.TABLE_ALIAS_STORE_ITEM.'
	USING
		( '.PREFIX_TABLE_COLUMN_STORE.PROPERTY_ID.' )
	WHERE
		'.TABLE_ALIAS_STORE.'.'.PREFIX_TABLE_COLUMN_STORE.PROPERTY_ID.' = 1
';

$objects = $class_query::parse($query);

$class_dumper::log(
	__METHOD__,
	array(
		'Results for executing "'.$query.'"',
		$objects
	),
	$verbose_mode
);