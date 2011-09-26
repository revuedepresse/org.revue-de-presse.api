<?php

/**
* Insight Node class
*
* Class to handler an insight node
* @package  sefi
*/
class Insight_Node extends Edge
{
	/**
	* Check credentials
	*
    * @param	array	$proofs				credentials
    * @param	mixed	$credential_type	type of credentials
	* @return	mixed	authorization scope
	*/	
	public static function checkCredentials(
		$proofs = NULL,
		$credential_type = NULL
	)
	{
		global $class_application;

		$class_data_fetcher = $class_application::getDataFetcherClass();

		$class_dumper = $class_application::getDumperClass();

		$authorization_scope = FALSE;

		if ( isset( $proofs->{PROPERTY_IDENTIFIER} ) )
		{
			$insight_node = self::fetchInsightNode(
				$proofs->{PROPERTY_IDENTIFIER}
			);

			if ( isset( $insight_node->{PROPERTY_OWNER} ) )

				$authorization_scope = (int) $insight_node->{PROPERTY_OWNER};
		}
		
		if (
			isset($proofs->{PROPERTY_AFFORDANCE}) &&
			FALSE !== strpos(
				$proofs->{PROPERTY_AFFORDANCE},
				$class_application::translate_entity(
					ACTION_REPLY_TO,
					ENTITY_URI
				)			
			)
		)
	
			$authorization_scope = !!!$class_data_fetcher::getEntityTypeValue(
				array(
					PROPERTY_ENTITY => ENTITY_AUTHORIZATION_SCOPE,
					PROPERTY_NAME => PROPERTY_MEMBERSHIP
				)
			);

		return $authorization_scope;
	}

	/**
	* Display an edition form
	*
	* @param	$context	context
	* @return	nothing
	*/	
	public static function displayEditionForm( $context )
	{
		global $class_application;

		$class_view_builder = $class_application::getViewBuilderClass();

		return $class_view_builder::displayForm(
			$context,
			__CLASS__,
			TRUE,
			FALSE
		);
	}

	/**
	* Display a preview
	*
	* @param	$context	context
	* @return	nothing
	*/	
	public static function displayPreview($context)
	{
		return self::getPreview( $context );
	}

	/**
	* Get a preview
	*
	* @param	$context	context
	* @return	nothing
	*/	
	public static function getPreview( $context )
	{
		global $class_application;

		$class_view_builder = $class_application::getViewBuilderClass();

		return $class_view_builder::getPreview( $context, __CLASS__ );
	}
	
	/**
	* Edit an object of the Insight Node class
	*
	* @param	$context	context
	* @return	nothing
	*/	
	public static function edit( $context )
	{
		self::extendContext( $context );

		return self::displayEditionForm($context);
	}

	/**
	* Fetch a context
	*
	* @param	object	$context	context
	* @return	mixed	context
	*/
	public static function extendContext( &$context )
	{
		global $class_application;

		$agent_entity = $class_application::getEntityAgent();

		$class_dumper = $class_application::getDumperClass();
	
		if (
			is_object($context) &&
			get_class($context) == CLASS_STANDARD_CLASS &&
			isset($context->{PROPERTY_IDENTIFIER})
		)
		{
			$insight_node = self::fetchInsightNode(
				$context->{PROPERTY_IDENTIFIER}
			);

			if (isset($insight_node->{PROPERTY_THREAD}))
			{
				$insight = $agent_entity::getById(
					$insight_node->{PROPERTY_THREAD},
					array(
						PROPERTY_TARGET_TYPE => array(
							PROPERTY_FOREIGN_KEY =>
								PREFIX_TABLE_COLUMN_ENTITY.PROPERTY_ID
						),
						PROPERTY_TARGET,
						PROPERTY_ID
					),
					CLASS_INSIGHT
				);

				while ( list( $name, $value ) = each( $insight ) )

					if ( $name != PROPERTY_ENTITY_NAME )
					
						$insight_node->$name = $value;
			}

			while ( list( $name, $value ) = each( $insight_node ) )

				$context->$name = $value;
		}
	}

	/**
	* Fetch an insight node
	*
	* @param	integer 	$id		insight node identifier
	* @return	mixed
	*/
	public static function fetchInsightNode($id)
	{
		if (is_numeric($id))
		{		
			$nodes = self::fetchInsightNodes(array(PROPERTY_ID => $id));
	
			list(, $node) = each($nodes);
	
			return $node;
		}
		else

			return NULL;
	}

	/**
	* Fetch the children of an insight node
	*
	* @param	integer 	$id		insight node identifier
	* @return	mixed
	*/
	public static function fetchInsightNodeChildren($id)
	{
		if (is_numeric($id))

			return self::fetchInsightNodes(
				array(
					PROPERTY_PARENT => $id,
					PROPERTY_STATUS => INSIGHT_NODE_STATUS_ACTIVE
				)
			);
		else
		
			return NULL;
	}

	/**
	* Fetch the siblings of an insight node
	*
	* @param	object	$node	insight node 
	* @return	mixed
	*/
	public static function fetchInsightNodeSiblings($node)
	{
		if (is_object($node) && isset($node->{PROPERTY_THREAD}))
		{
			$node_siblings = self::fetchInsightNodes(
				array(
					PROPERTY_PARENT => INSIGHT_TYPE_PARENT_ROOT,
					PROPERTY_STATUS => INSIGHT_NODE_STATUS_ACTIVE,
					PREFIX_TABLE_COLUMN_INSIGHT.PROPERTY_ID => $node->{PROPERTY_THREAD}
				)
			);

			unset($node_siblings[$node->{PROPERTY_ID}]);

			return $node_siblings;
		}
		else
		
			return NULL;
	}

	/**
	* Fetch insight nodes
	*
	* @param	array	$where_clause	fetching conditions
	* @return	mixed
	*/
	public static function fetchInsightNodes($where_clause)
	{
		return parent::fetchInsightNodes($where_clause);
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

		if ( ! $namespace )

			list( $_namespace, $_class ) = explode('\\', __CLASS__);

		return $_class;
	}

	/**
	* Reply to an insight node
	*
	* @return	nothing
	*/	
	public static function replyTo($context)
	{
		global $class_application;

		$class_dumper = $class_application::getDumperClass();

		$class_entity = $class_application::getEntityClass();

		$class_insight = $class_application::getInsightClass();

		$class_standard_class = $class_application::getStandardClass();

		$class_view_builder = $class_application::getViewBuilderClass();

		if (
			isset( $context->{PROPERTY_IDENTIFIER} ) &&
			$identifier = $context->{PROPERTY_IDENTIFIER}
		)
		{
			self::extendContext( $context );

			$callback_preview = self::getPreview($context);

			list( , $preview ) = each( $callback_preview );

			$callback_insight = $class_insight::getForm(
				$class_entity::getByName(CLASS_PHOTOGRAPH)->{PROPERTY_ID},
				$context->{PROPERTY_TARGET},
				$identifier
			);

			$context = new $class_standard_class();

			$context->{PROPERTY_CONTAINER} = array(
				HTML_ELEMENT_DIV => array(
					HTML_ATTRIBUTE_CLASS =>
						STYLE_CLASS_REPLY
				)
			);

			$context->{PROPERTY_BODY} =
				$preview.
				$callback_insight
			;

			$context->{PROPERTY_CACHE_ID} = md5( $context->{PROPERTY_BODY} );

			$class_view_builder::display( $context, VIEW_TYPE_INJECTION );
		}
		else
		{
			throw new Exception( EXCEPTION_INVALID_IDENTIFIER );

			$class_application::jumpTo( PREFIX_ROOT );
		}
	}
}
?>