<?php

/**
* Processor class
*
* @package  sefi
*/
class Processor extends Controller
{
	/**
	* Generate a random string
	*
	* @param	array	$fixes	fixes
	* @return	string	
	*/	
	public static function randomString($fixes = null)
	{
		$unique_id =
			(isset($fixes['prefix']) ? sha1($fixes['prefix']) : '').
			uniqid().
			(isset($fixes['suffix']) ? sha1($fixes['suffix']) : '')
		;

		$split = rand(1, strlen($unique_id));

		return array(
			'sha1' => sha1(substr($unique_id, 0, $split).'!'.substr($unique_id, $split + 1)),
			'plain' => substr($unique_id, 0, $split).'!'.substr($unique_id, $split + 1)
		);
	}

	/**
	* Generate a confirmation link
	*
	* @param	array	$fixes	fixes
	* @return	string	link
	*/	
	public static function generateLink($fixes)
	{
		$generated_key = self::randomString($fixes);

		if (isset($generated_key['sha1']))

			return URI_AFFORDANCE_CONFIRM.'?'.GET_PROFILE_CHANGES.'='.$generated_key['sha1'];
		else
		
			throw new Exception(EXCEPTION_INVALID_ARGUMENT);
	}

	/**
	* Get a signature
	*
	* @param	boolean	$namespace	namespace flag
	* @return	string	signature
	*/
	public static function getSignature($namespace = TRUE)
	{
		$_class = __CLASS__;

		if (!$namespace)

			list($_namespace, $_class) = explode('\\', __CLASS__);

		return $_class;
	}

	/**
	* Take action
	*
	* @param	mixed	$actions	actions to be taken
	* @param	mixed	$feedback	feedback items
	* @param	mixed	$context	context parameters
	* @return	mixed
	*/
	public static function takeAction( $actions, &$feedback, $context = NULL )
	{
		global $class_application, $verbose_mode;

		// set the data fetcher class name
		$class_data_fetcher = $class_application::getDataFetcherClass();

		$class_dumper = $class_application::getDumperClass();

		// set the member class name
		$class_member = $class_application::getMemberClass();

		// set the serializer class name
		$class_serializer = $class_application::getSerializerClass();

		// check the context
		if ( is_object( $context[PROPERTY_HANDLER] ) && get_class( $context[PROPERTY_HANDLER] ) )
		{

			$field_handler = $context;

			// get the feedback events
			$events_feedback = $field_handler->getProperty( PROPERTY_FEEDBACK );
		}

		if ( ! isset( $context[PROPERTY_KEY] ) )
		{
			$email = $class_member::getEmail();
	
			$identifier = $class_member::getIdentifier();
	
			$user_name = $class_member::getUserName();
		}
		else
		{
			$qualities = $class_member::fetchQualities(
				array( $context[PROPERTY_KEY] => $context[PROPERTY_IDENTIFIER] )
			);

			if (!empty($qualities->email))

				$email = $qualities->email;

			if (!empty($qualities->id))

				$identifier = $qualities->id;

			if (!empty($qualities->user_name))

				$user_name = $qualities->user_name;
		}

		if ( empty( $user_name ) )
		
			unset( $actions[AFFORDANCE_SEND] );

		if ( ! isset( $feedback[AFFORDANCE_DISPLAY] ) )
		
			$feedback[AFFORDANCE_DISPLAY] = array();

		if ( ! isset($feedback[AFFORDANCE_SEND] ) )
		
			$feedback[AFFORDANCE_SEND] = array();

		while ( list( $action_type, $action ) = each( $actions ) )
		{
			switch ( $action_type )
			{
				case AFFORDANCE_DISPLAY:

					list( $template_type, $template_block ) =
						explode( '.', $action );

					if ( ! in_array( $template_block, $feedback[AFFORDANCE_DISPLAY] ) )

						$feedback[AFFORDANCE_DISPLAY][$template_type] =
							$class_data_fetcher::getTemplate( $action );

						break;

				case AFFORDANCE_SAVE_TO:

					if ( ! isset( $feedback[AFFORDANCE_SAVE_TO] ) )

						$feedback[AFFORDANCE_SAVE_TO] = array();

					list( $storage_type, $storage_name ) = explode( '.', $action );

					if (
						$storage_type == SHORTHAND_DATABASE &&
						defined( strtoupper( PREFIX_TABLE.$storage_name ) )
					)
					{
					
						$table = constant( strtoupper( PREFIX_TABLE.$storage_name ) );

						if ( isset( $_POST[$context[PROPERTY_TARGET]] ) )
						{
							// insert a new contact into the database
							$contact_id = $class_serializer::saveContact(
								array(
									PROPERTY_COLUMN =>
										array(
											'usr_id' => $identifier,
											'cnt_type' => constant(
												'CONTACT_TYPE_'.strtoupper(
													$context[PROPERTY_TARGET]
												)
											),
											'cnt_value' => $_POST[$context[PROPERTY_TARGET]]
										),
									PROPERTY_STORE => STORE_DATABASE,
									PROPERTY_TABLE => $table
								)
							);

							if ( $contact_id )
							
								$feedback[AFFORDANCE_SAVE_TO][] = $contact_id;
						}
					}

						break;
						
				case AFFORDANCE_SEND:

					if ( ! isset( $feedback[AFFORDANCE_SEND]['parameters'] ) )

						$feedback[AFFORDANCE_SEND]['parameters'] = array();

					list( $template_type, $template_block ) = explode( '.', $action );

					if ( ! isset( $feedback[AFFORDANCE_SEND]['link_ids'] ) )
					
						$feedback[AFFORDANCE_SEND]['link_ids'] = array();

					// each link embedded in the message body is replaced with the appropriate a tag
					if ( ! isset( $feedback[AFFORDANCE_SEND]['parameters']['link'] ) )

						$feedback[AFFORDANCE_SEND]['parameters']['link'] =
							(
								isset($_SERVER['HTTP_HOST'])
							?
								'http://'.$_SERVER['HTTP_HOST']
							:
								''
							).
							self::generateLink(
								array(
									'prefix' => $user_name,
									'suffix' => $identifier
								)
							)
						;

					$link_properties = array(
						'lnk_value' => $feedback[AFFORDANCE_SEND]['parameters']['link'],
						'usr_id' => $identifier
					);

					if ( isset( $actions[ACTION_BIND] ) )
					
						$link_properties['qry_id'] = $actions[ACTION_BIND];

					// record a link and store a primary key into the feedback store
					$feedback[AFFORDANCE_SEND]['link_ids'][$context[PROPERTY_TARGET]] =
						$class_serializer::recordLink( $link_properties )
					;

					if ( ! isset($feedback[AFFORDANCE_SEND]['template'] ) )

						$feedback[AFFORDANCE_SEND]['template'] = array(
							'contents' => $class_data_fetcher::getTemplate( $action ),
							'name' => $template_block 
						);

					if ( ! isset( $feedback[AFFORDANCE_SEND]['name'] ) )

						$feedback[AFFORDANCE_SEND]['parameters']['name'] = $user_name;

					if (
						! isset( $feedback[AFFORDANCE_SEND]['parameters']['password'] ) &&
						isset($context[PROPERTY_VALUE] )
					)

						$feedback[AFFORDANCE_SEND]['parameters']['password'] =
							$context[PROPERTY_VALUE];

					if ( ! isset( $feedback[AFFORDANCE_SEND]['parameters']['signature'] ) )

						$feedback[AFFORDANCE_SEND]['parameters']['signature'] =
							CONTENT_SIGNATURE_EMAIL;

					if ( ! isset( $feedback[AFFORDANCE_SEND]['parameters']['to'] ) )

						$feedback[AFFORDANCE_SEND]['parameters']['to'] = $email;

					if ( ! isset( $feedback[AFFORDANCE_SEND]['parameters']['updates'] ) )

						$feedback[AFFORDANCE_SEND]['parameters']['updates'] = array();

					// a list of the latest updates is built
					if (
						defined(
							strtoupper(
								LANGUAGE_PREFIX_FORM.PREFIX_LABEL.
									$class_application::translate_entity(
										$context[PROPERTY_HANDLER]
											->getProperty(
												PROPERTY_FORM_IDENTIFIER
											),
										ENTITY_CONSTANT
									).
										'_'.$context[PROPERTY_TARGET]
							)
						)
					)

						$feedback[AFFORDANCE_SEND]['parameters']['updates'][] = 
							strtolower(
								constant(
									strtoupper(
										LANGUAGE_PREFIX_FORM.PREFIX_LABEL.
											$class_application::translate_entity(
												$context[PROPERTY_HANDLER]
													->getProperty(
														PROPERTY_FORM_IDENTIFIER
													),
												ENTITY_CONSTANT
											).
												'_'.$context[PROPERTY_TARGET]
									)
								)
							).': '.$_POST[$context[PROPERTY_TARGET]];

						break;
			}
		}
	}

	/**
	* Process a request
	*
	* @param	mixed	$request 	request
	* @param	mixed	$context	context
	* @return 	mixed
	*/
	public static function processRequest($request, $context)
	{
		global $class_application;
		
		$class_dumper = $class_application::getDumperClass();

		$callback_parameters = array();

		if (isset($context->{PROPERTY_ENTITY}))
		{
			$entities = self::fetchInstances($context->{PROPERTY_ENTITY}, CLASS_ENTITY);

			if (count($entities))
			{
				if (count($entities) == 1)
				{
					list(, $entity) = each($entities);
	
					$callback_parameters = $entity->processRequest($request, $context);
				}
				else

					while (list($index, $entity) = each($entities))

						$callback_parameters[$index] = $entity->processRequest($request, $context);
			}
		}

		return $callback_parameters;
	}
}
?>