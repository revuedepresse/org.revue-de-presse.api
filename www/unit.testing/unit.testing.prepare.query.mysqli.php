<?php

$class_db = $class_application::getDbClass();

// get a mysqli link
$link = $class_db::getLink();

$class_dumper = $class_application::getDumperClass();

$expression = 'a:21:{s:10:"expression";a:1:{s:1:"/";a:2:{s:12:"left.operand";O:8:"stdClass":2:{s:5:"table";s:12:"weaving_user";s:6:"column";s:6:"usr_id";}s:13:"right.operand";a:1:{s:1:"=";a:3:{s:12:"left.operand";O:8:"stdClass":1:{s:5:"value";s:26:"## FILL USERNAME ##@## FILL HOSTNAME ##";}s:15:"right.operand|0";O:8:"stdClass":2:{s:5:"table";s:12:"weaving_user";s:6:"column";s:13:"usr_user_name";}s:15:"right.operand|1";O:8:"stdClass":2:{s:5:"table";s:12:"weaving_user";s:6:"column";s:9:"usr_email";}}}}}s:11:"sup_operand";s:3:"≔";s:8:"checksum";N;s:17:"class_application";s:11:"Application";s:8:"class_db";s:2:"DB";s:18:"class_data_fetcher";s:12:"Data_Fetcher";s:12:"class_dumper";s:6:"Dumper";s:23:"class_exception_handler";s:17:"Exception_Handler";s:7:"globals";a:0:{}s:7:"pattern";s:42:"/(left\.operand|right\.operand)(.)?(.*)?/u";s:9:"predicate";O:8:"stdClass":2:{s:12:"left.operand";r:4;s:13:"right.operand";O:8:"stdClass":2:{s:12:"clause.where";s:13:"usr_email = ?";s:11:"param.where";s:26:"## FILL USERNAME ##@## FILL HOSTNAME ##";}}s:9:"connector";s:1:"/";s:8:"operands";a:2:{s:12:"left.operand";r:4;s:13:"right.operand";a:1:{s:1:"=";a:3:{s:12:"left.operand";r:9;s:15:"right.operand|0";r:11;s:15:"right.operand|1";r:14;}}}s:12:"operand_type";N;s:7:"operand";N;s:5:"match";i:1;s:7:"matches";a:4:{i:0;s:13:"right.operand";i:1;s:13:"right.operand";i:2;s:0:"";i:3;s:0:"";}s:10:"parameters";O:8:"stdClass":1:{s:12:"clause.where";s:13:"usr_email = ?";}s:4:"link";O:6:"mysqli":17:{s:13:"affected_rows";N;s:11:"client_info";N;s:14:"client_version";N;s:13:"connect_errno";N;s:13:"connect_error";N;s:5:"errno";N;s:5:"error";N;s:11:"field_count";N;s:9:"host_info";N;s:4:"info";N;s:9:"insert_id";N;s:11:"server_info";N;s:14:"server_version";N;s:8:"sqlstate";N;s:16:"protocol_version";N;s:9:"thread_id";N;s:13:"warning_count";N;}s:11:"where_param";s:26:"## FILL USERNAME ##@## FILL HOSTNAME ##";s:14:"prepared_query";s:76:"
			SELECT
				usr_id
			FROM
				weaving_user
			WHERE
				usr_email = ?
		";}'
;

$arguments = unserialize($expression);

$statement = $link->prepare($arguments['prepared_query']);

$class_dumper::log(
	__METHOD__,
	array($statement),
	$verbose_mode
);