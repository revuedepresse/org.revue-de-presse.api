<?php

/**
* Messenger class
*
* Class for messaging
* @package  sefi
*/
class Messenger extends Toolbox
{
	/**
	* Construct a dummy instanct of Messenger
	*
	* @return	object		new instance of Messenger
	*/
	public function __construct()
	{
		return $this;
	}

    /**
    * Log a message
    *
    * @param 	string	$properties 	message properties
    * @param	integer	$type			message type
    * @return 	mixed	logging result
	*/
	public static function log( $properties, $type )
	{
		$class_serializer = self::getSerializerClass();

		return $class_serializer::log($properties, $type);
	}

    /**
    * Check outbox
    *
    * @return 	nothing
	*/
	public static function checkOutbox()
	{
        global $build_jenkins;

		// check if the CLI mode is enabled
		if ( ! $build_jenkins &&  ! isset($_SERVER['SERVER_NAME'] ) )

			echo 'operation date: '.date('Y-m-d_H:i:s')."\n";

		$class_data_fetcher = self::getDataFetcherClass();
		$class_service_manager = self::getServiceManagerClass();
		$class_serializer = self::getSerializerClass();
		
		$config = array(
			'auth' => 'login',
			'username' => SMTP_USER_NAME,
			'password' => SMTP_PASSWORD,
			'port' => SMTP_PORT
		);
		
		// set the transport property
		$transport = new Zend_Mail_Transport_Smtp( SMTP_HOST, $config );
		
		$unsent_emails = $class_data_fetcher::fetchEmails( EMAIL_STATUS_UNSENT );
		
		while ( list( $id, $email ) = each( $unsent_emails ) )
		{
			try {
				// set the email sending flag
				$email_sent = $email->send( $transport );
			}
			catch ( Exception $exception )
			{
				$class_serializer::logErrorMessage(
					$id,
					ENTITY_EMAIL,
					$exception->getFile().'('.$exception->getLine().'): '.$exception->getMessage()
				);
			}

			if ( ! isset( $email_sent ) || ! $email_sent )
		
				$class_serializer::toggleStatus(
					$id,
					ENTITY_EMAIL,
					EMAIL_STATUS_BOUNCED
				);
			else

				$class_serializer::toggleStatus( $id, ENTITY_EMAIL );
		}	

		// check if the CLI mode is enabled
		if ( ! isset( $_SERVER['SERVER_NAME'] ) )
	
			echo "\n";
	}

    /**
    * Provide with some feedback
    *
    * @param 	mixed	$feedback 	feedback
    * @param	mixed	$identifier	identifier
    * @return 	nothing
	*/
	public static function provideWithFeedback( $feedback, $identifier )
	{
		global $class_application;

		$class_deployer = $class_application::getDeployerClass();

		$class_dumper = $class_application::getDumperClass();

		$class_member = $class_application::getMemberClass();

		$class_serializer = $class_application::getSerializerClass();

		$class_template_engine = $class_application::getTemplateEngineClass();

		while ( list( $type, $instance ) = each( $feedback ) )
		{
			switch ( $type )
			{	
				case AFFORDANCE_DISPLAY:

					$_SESSION[ENTITY_FEEDBACK] = array(
						$identifier =>
							array( $type => $instance )
					);

						break;

				case AFFORDANCE_SEND:
		 
					if (
						is_array( $instance ) &&
						count( $instance )
					)
					{
						// set the email sending indicator
						$email_sent = FALSE;
			
						$sender_email = DIALOG_SENDER_EMAIL;
			
						$sender_name = DIALOG_SENDER_NAME_CONFIRMATION;
			
						$subject = DIALOG_SUBJECT_CONFIRMATION_MESSAGE;
			
						// set a email
						$email = new Zend_Mail(ZEND_CHARSET_UTF8);
						
						if (
							isset( $instance ) &&
							isset( $instance['template'] ) &&
							isset( $instance['template']['contents'] ) && 
							isset( $instance['template']['name'] )
						)
						{
							$template_path =
								dirname(__FILE__).
									DIR_PARENT_DIRECTORY.
										DIR_PARENT_DIRECTORY.
											DIR_TEMPLATES.'/'.
												TO_MEMBER.
													$class_application::translate_entity(
														$instance['template']['name'],
														ENTITY_AFFORDANCE,
														ENTITY_MESSAGE
													).
														EXTENSION_TPL
							;
			
							if (
								! file_exists( $template_path ) ||
								strlen( $instance['template']['contents'] ) !=
									strlen( file_get_contents( $template_path ) )
							)
							{
								$handle = fopen(
									$template_path,
									FILE_ACCESS_MODE_OVERWRITE
								);

								fwrite( $handle, $instance['template']['contents'] );
								fclose( $handle );
							}
						}
			
						$template_engine = new $class_template_engine();

						$cache_id = md5(
							serialize(
								array(
									$instance,
									$class_member::getIdentifier( FALSE, FALSE ),
									$class_member::getIdentifier( TRUE, FALSE )
								)
							)
						);

						if (
							is_array( $instance['parameters'] ) && 
							! (
								$cached = $template_engine->is_cached(
									$template_path,
									$cache_id
								)
							)
						)

							while (
								list( $property, $value ) =
								   each($instance['parameters'] )
							)
			
								$template_engine->assign( $property, $value );
			
						$body_text = $template_engine->fetch( $template_path, $cache_id );
			
						// set the email properties	
						$email->setFrom( $sender_email, $sender_name );
						$email->addBcc( RECIPIENT_UNIT_TESTING_CONFIRMATION );
						$email->addTo(
							$instance['parameters']['to'],
							$instance['parameters']['name']
						);
						$email->setSubject( $subject );
						$email->setBodyText( $body_text, ZEND_CHARSET_UTF8 );
			
						// set the message
						$body_html = str_replace(
							array("\r\n", "\n", "\r"),
							'<br />',
							$body_text
						);
			
						$body_html = preg_replace(
							'#http://(\S*)#',
							'<a href=\'http://\1\'>http://\1</a>',
							$body_html
						);
			
						// set the HTML body
						$email->setBodyHtml( $body_html, ZEND_CHARSET_UTF8 );
			
						$context = array(
							'from' => array( $sender_name => $sender_email ),
							'body_html' => $body_html,
							'body_text' => $body_text,
							'subject' => $subject,
							'to' => array(
								$instance['parameters']['name'] =>
									$instance['parameters']['to']
							)
						);
			
						if ( isset( $instance['parameters']['password'] ) )
						
							$context['identifier'] = $instance['parameters']['to'];
						
						$class_serializer::save(
							$context,
							ENTITY_MESSAGE
						);
	
						if (
							! $class_deployer::preproductionEnvironment() ||
							$class_deployer::unitTestingMode()
						)

							// set the email sending flag
							$class_serializer::save( $email, ENTITY_EMAIL );
					}

						break;
			}
		}		
	}
}
