<?php

$class_query = $class_application::getQueryClass();

$class_dumper = $class_application::getDumperClass();

// fetch the default query type: select
$default_type = $class_query::fetchDefaultType()->value;

$query_select_user = '
	SELECT
		usr_id,
		grp_id,
		usr_status,
		usr_user_name,
		usr_avatar,
		usr_first_name,
		usr_last_name,
		usr_middle_name,
		usr_email
	FROM
		'.TABLE_USER.'
';

$properties = array(
	PROPERTY_STATUS => QUERY_STATUS_ACTIVE,
	PROPERTY_TYPE => $default_type,
	PROPERTY_VALUE => $query_select_user
);

$query_id = $class_query::add($properties);

$class_dumper::log(
	__METHOD__,
	array($query_id),
	$verbose_mode
);