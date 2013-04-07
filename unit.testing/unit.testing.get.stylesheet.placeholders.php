<?php

$class_arc = $class_application::getArcClass();

$class_dumper = $class_application::getDumperClass();

$class_edge = $class_application::getEdgeClass();

$class_stylesheet = $class_application::getStylesheetClass();

$stylesheet_id = $class_stylesheet::getByConditions(
	array( PROPERTY_NAME => 'main' ),
	ENTITY_STYLESHEET
)->{PROPERTY_ID};

$class_dumper::log(
	__METHOD__,
	array(
		'[stylesheet id]',
		$stylesheet_id
	),
	$verbose_mode
);

$class_dumper::log(
	__METHOD__,
	array(
		'[Stylesheet Entity id]',
		$class_edge::getByName(
			ENTITY_STYLESHEET,
			NULL,
			CLASS_ENTITY
		)->{PROPERTY_ID}				
	),
	$verbose_mode
);

$class_dumper::log(
	__METHOD__,
	array(
		'[Id of an instance of Edge Entity retrieved by providing conditions]',
		$class_edge::getByConditions(
			array(
				PROPERTY_KEY => $stylesheet_id,
				PROPERTY_FOREIGN_KEY => array(
					PREFIX_TABLE_COLUMN_ENTITY.PROPERTY_ID =>
						$class_edge::getByName(
							ENTITY_STYLESHEET,
							NULL,
							CLASS_ENTITY
						)->{PROPERTY_ID}
				)
			)
		)
	),
	$verbose_mode
);
	
$class_dumper::log(
    __METHOD__,
    array(
		'[Arc retrieved by providing its destination edge]',
        $class_arc::getByDestination(
            $class_edge::getByConditions(
                array(
                    PROPERTY_KEY => $stylesheet_id,
                    PROPERTY_FOREIGN_KEY => array(
                        PREFIX_TABLE_COLUMN_ENTITY.PROPERTY_ID =>
                            $class_edge::getByName(
								ENTITY_STYLESHEET,
								NULL,
								CLASS_ENTITY
							)->{PROPERTY_ID}
                    )
                )
            ),
			ARC_TYPE_ENCAPSULATION
        ),
		'$class_arc::getByDestinationKey(
			$stylesheet_id,
			ENTITY_STYLESHEET,
			PROPERTY_ENCAPSULATION
		)',
        $class_arc::getByDestinationKey(
			$stylesheet_id,
			ENTITY_STYLESHEET,
			PROPERTY_ENCAPSULATION
		),
		'$class_stylesheet::getPlaceholders()',
        $class_stylesheet::getPlaceholders()
    ),
    $verbose_mode
);
