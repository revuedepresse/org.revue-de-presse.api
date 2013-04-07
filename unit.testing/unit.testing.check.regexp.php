<?php

$class_dumper = $class_application::getDumperClass();

$operand_pattern =
	REGEXP_OPEN.
		$class_application::translate_entity(PROPERTY_LEFT_OPERAND, ENTITY_PATTERN).
		SYMBOL_TRIPLE_EQUAL.
		REGEXP_CATCH_START.
			REGEXP_WILDCARD.REGEXP_FACULTATIVE.
		REGEXP_CATCH_END.
		REGEXP_CATCH_START.
			PROPERTY_OPERAND.
			REGEXP_ESCAPE.SYMBOL_MEMBER_ATTRIBUTE.
			REGEXP_CATCH_START.
				REGEXP_EXPRESSION_START.
					REGEXP_NOT.SYMBOL_TRIPLE_EQUAL.
				REGEXP_EXPRESSION_END.REGEXP_ANY.
			REGEXP_CATCH_END.
			SYMBOL_TRIPLE_EQUAL.REGEXP_FACULTATIVE.
			REGEXP_CATCH_START.
				REGEXP_EXPRESSION_START.
					REGEXP_NOT.SYMBOL_TRIPLE_EQUAL.
				REGEXP_EXPRESSION_END.REGEXP_ANY.
			REGEXP_CATCH_END.REGEXP_FACULTATIVE.
		REGEXP_CATCH_END.REGEXP_FACULTATIVE.
		REGEXP_OR.
		$class_application::translate_entity(PROPERTY_RIGHT_OPERAND, ENTITY_PATTERN).
		SYMBOL_TRIPLE_EQUAL.
		REGEXP_CATCH_START.
			REGEXP_WILDCARD.REGEXP_FACULTATIVE.
		REGEXP_CATCH_END.
		REGEXP_CATCH_START.
			PROPERTY_OPERAND.
			REGEXP_ESCAPE.SYMBOL_MEMBER_ATTRIBUTE.
			REGEXP_CATCH_START.
				REGEXP_EXPRESSION_START.
					REGEXP_NOT.SYMBOL_TRIPLE_EQUAL.
				REGEXP_EXPRESSION_END.REGEXP_ANY.
			REGEXP_CATCH_END.
			SYMBOL_TRIPLE_EQUAL.REGEXP_FACULTATIVE.
			REGEXP_CATCH_START.
				REGEXP_EXPRESSION_START.
					REGEXP_NOT.SYMBOL_TRIPLE_EQUAL.
				REGEXP_EXPRESSION_END.REGEXP_ANY.
			REGEXP_CATCH_END.REGEXP_FACULTATIVE.
		REGEXP_CATCH_END.REGEXP_FACULTATIVE.					
	REGEXP_CLOSE.REGEXP_MODIFIER_UNICODE
;

$symbol = "\xE2\x89\xA1";

$operands = 'a:3:{i:0;s:115:"/left\.operand'.$symbol.'(.?)(operand\.([^'.$symbol.']*)'.$symbol.'?([^'.$symbol.']*)?)?|right\.operand'.$symbol.'(.?)(operand\.([^'.$symbol.']*)'.$symbol.'?([^'.$symbol.']*)?)?/u";i:1;s:18:"left.operand'.$symbol.'⇒";i:2;N;}';

$op = unserialize( $operands );

$operand_pattern = $op[0];

$operand_index = $op[1];

$operand_matches = $op[2];

$operand_match = preg_match( $operand_pattern, $operand_index, $operand_matches );

$class_dumper::log(
	__METHOD__,
	array(
		'[pattern]',
		$operand_pattern,
		'[index]',
		$operand_index,
		'[matches]',
		$operand_matches
	),
	$verbose_mode
);