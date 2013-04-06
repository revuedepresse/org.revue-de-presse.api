<?php

/**
* Insight class
*
* Class to handle an insight
* @package  sefi
*/
class Insight extends Toolbox
{
	protected $properties;

	/**
	* Construct an insight
	*
	* @param	mixed	$properties	properties of an insight
	* @return	object	representing an insight
	*/
	public function __construct( $properties )
	{
		$this->properties = new stdClass();

		$this->setProperties( $properties );
	}

	/**
	* Get a property magically
	*
	* @param	string	$name	name
	* @return	mixed	property value
	*/
	public function &__get($name)
	{
		$property = &$this->getProperty($name);
		
		return $property;
	}

	/**
	* Check if a property is set
	*
	* @param	string	$name	name
	* @return	nothing
	*/
	public function __isset($name)
	{
		$isset = FALSE;

		if (isset($this->$name) || $this->__get($name) !== NULL)
		
			$isset = TRUE;

		return $isset;
	}

	/**
	* Set a property magically
	*
	* @param	string	$name	name
	* @param	string	$value	value
	* @return	nothing
	*/
	public function __set($name, $value)
	{
		return $this->setProperty($name, $value);
	}

	/**
	* Get the properties
	*
	* @return	mixed	properties
	*/
	public function &getProperties()
	{
		return $this->properties;
	}

	/**
	* Get a property value
	*
	* @param	string	$name	name
	* @return	mixed	property value
	*/
	public function &getProperty($name)
	{
		if (!isset($this->properties->$name))
		
			$this->properties->$name = null;
			
		return $this->properties->$name;
	}

	/**
	* Set the value of a property
	*
	* @param	string	$name	name
	* @param	mixed	$value	value
	* @return	nothing
	*/
	public function setProperty($name, $value)
	{
		$_value = &$this->getProperty($name);
		
		$_value = $value;
	}

	/**
	* Set the values of properties
	*
	* @param	mixed	$properties	properties values
	* @return	nothing
	*/	
	public function setProperties($properties)
	{
		if (
			(is_array($properties) &&  count($properties) != 0) ||
			(is_object($properties) && count(get_object_vars($properties) != 0))
		)

			foreach ($properties as $name => $value)

				$this->setProperty($name, $value);
	}

	/**
	* Add an insight
	*
	* @param	mixed	$properties	properties values
	* @return	nothing
	*/	
	public static function add($properties)
	{
		if (
			(is_array($properties) &&  count($properties) != 0) ||
			(is_object($properties) && count(get_object_vars($properties) != 0))
		)
		{
			$insight = new self($properties);

			return $insight->serialize();
		}
	}

	/**
	* Display an insight form
	*
	* @param	integer	$target_type
	* @param	integer	$target
	* @param	integer $parent
	* @return	string	insight HTML form 
	*/
	public static function getForm(
		$target_type,
		$target,
		$parent
	)
	{
		global $class_application, $verbose_mode;

		$search = array(
			'{$affordance}',
			'{$target}',
			'{$target_type}',
			'{$parent}'
		);

		$replace = array(
			ACTION_SHARE_INSIGHT,
			$target,
			$target_type,
			$parent
		);

		$form = $class_application::spawnFormView(
			ACTION_POST.'.'.
			$target,
			$search,
			$replace
		);
		
		return $form;
	}

	/**
	* Serialize an insight
	*
	* @return	nothing
	*/
	public function serialize()
	{
		$class_serializer = CLASS_SERIALIZER;
		
		return $class_serializer::save($this, get_class($this));
	}

	/**
	* Display an insight form
	*
	* @param	integer	$target_type
	* @param	integer	$target
	* @param	integer $parent
	* @return	string	insight HTML form 
	*/
	public static function displayForm($target_type, $target, $parent)
	{
		echo call_user_func_array(
			array(CLASS_INSIGHT, 'getForm'),
			func_get_args()
		);
	}

	/**
	* Render a thread
	*
	* @param	integer $target
	* @param	integer $target_type
	* @return	nothing
	*/
	public static function displayThread($target, $target_type = NULL)
	{
		$class_entity = CLASS_ENTITY;

		if (is_null($target_type))

			$target_type = $class_entity::getByName(CLASS_PHOTOGRAPH)->{PROPERTY_ID};

		echo self::getThread($target, $target_type);
	}

	/**
	* Fetch an insight
	*
	* @param	object	$node	insight node
	* @return	mixed
	*/
	public static function fetchInsight($node)
	{
		if (is_object($node) && isset($node->{PROPERTY_THREAD}))
		{		
			$insights = self::fetchInsights(array(PROPERTY_ID => $node->{PROPERTY_THREAD}));
	
			list(, $insight) = each($insights);
	
			return $insight;
		}
		else

			return NULL;
	}

	/**
	* Fetch insights
	*
	* @param	object	$conditions	conditions
	* @return	mixed
	*/
	public static function fetchInsights($conditions)
	{
		$class_data_fetcher = CLASS_DATA_FETCHER;
		
		if (is_array($conditions))

			return  $class_data_fetcher::fetchInsights($conditions);
		else

			return NULL;
	}

	/**
	* Fetch an insight node
	*
	* @param	integer 	$id		insight node identifier
	* @return	mixed
	*/
	public static function fetchInsightNode($id)
	{
		$class_insight_node = CLASS_INSIGHT_NODE;
		
		return $class_insight_node::fetchInsightNode($id);
	}

	/**
	* Fetch the children of an insight node
	*
	* @param	integer 	$id		insight node identifier
	* @return	mixed
	*/
	public static function fetchInsightNodeChildren($id)
	{
		$class_insight_nodes = CLASS_INSIGHT_NODE;

		return $class_insight_nodes::fetchInsightNodeChildren($id);	
	}

	/**
	* Fetch the siblings of an insight node
	*
	* @param	object	$node	insight node 
	* @return	mixed
	*/
	public static function fetchInsightNodeSiblings($node)
	{
		$class_insight_nodes = CLASS_INSIGHT_NODE;

		return $class_insight_nodes::fetchInsightNodeSiblings($node);		
	}

	/**
	* Fetch insight nodes
	*
	* @param	array	$where_clause	fetching conditions
	* @return	mixed
	*/
	public static function fetchInsightNodes($where_clause)
	{
		$class_insight_nodes = CLASS_INSIGHT_NODE;

		return $class_insight_nodes::fetchInsightNodes($where_clause);
	}

	/**
	* Get a configuration
	*
	* @param	string	$configuration_type		type of configuration
	* @return	mixed	configuration
	*/
	public static function getConfiguration($configuration_type = CONFIGURATION_SERIALIZATION)
	{
		$class_entity = CLASS_ENTITY;

		return $class_entity::getConfiguration($configuration_type, __CLASS__);
	}

	/**
	* Render a thread
	*
	* @param	integer $target
	* @param	integer $target_type
	* @return	string	insight HTML thread
	*/
	public static function getThread($target, $target_type = NULL)
	{
		$class_entity = CLASS_ENTITY;

		$class_view_builder = CLASS_VIEW_BUILDER;

		if (is_null($target_type))

			$target_type = $class_entity::getByName(CLASS_PHOTOGRAPH)->{PROPERTY_ID};

		return $class_view_builder::renderThread(self::loadThread($target, $target_type));
	}

	/**
	* Get a thread
	*
	* @param	integer $target
	* @param	integer $target_type
	* @return	mixed	thread
	*/
	public static function loadThread($target, $target_type = NULL)
	{
		global $class_application;

		$class_data_fetcher = $class_application::getDataFetcherClass();

		$class_entity = $class_application::getEntityClass();

		if (is_null($target_type))

			$target_type = $class_entity::getByName(CLASS_PHOTOGRAPH)->{PROPERTY_ID};

		$_thread = self::reuniteRelatives($target, $target_type);

		return $_thread;
	}

	/**
	* Get the children of a thread
	*
	* @param	integer $target
	* @param	integer $target_type
	* @return	mixed	children
	*/
	public static function loadThreadChildren($target, $target_type = NULL)
	{
		$class_data_fetcher = CLASS_DATA_FETCHER;

		$class_entity = CLASS_ENTITY;

		if (is_null($target_type))

			$target_type = $class_entity::getByName(CLASS_PHOTOGRAPH)->{PROPERTY_ID};

		return $class_data_fetcher::fetchThread($target, $target_type, SCHEMA_TYPE_CHILDREN);
	}

	/**
	* Get the parents of a thread
	*
	* @param	integer $target
	* @param	integer $target_type
	* @return	mixed	parents
	*/
	public static function loadThreadParents($target, $target_type = NULL)
	{
		$class_data_fetcher = CLASS_DATA_FETCHER;

		$class_entity = CLASS_ENTITY;

		if (is_null($target_type))

			$target_type = $class_entity::getByName(CLASS_PHOTOGRAPH)->{PROPERTY_ID};

		return $class_data_fetcher::fetchThread($target, $target_type, SCHEMA_TYPE_PARENTS);
	}
	
	/**
	* Process a request
	*
	* @param	integer $request request
	* @return	mixed
	*/
	public static function processRequest($request)
	{
		$return = FALSE;

		if (is_object($request) && isset($request->{PROPERTY_AFFORDANCE}))
		{
			switch ($request->{PROPERTY_ID})
			{
				case ROUTE_ACTION_REMOVE_INSIGHT_NODE:

					$return = self::removeById($request->{PROPERTY_IDENTIFIER});
		
					break;
			}
		}
		
		return $return;
	}

	/**
	* Remove by id
	*
	* @param	integer $id	id
	* @return	mixed
	*/
	public static function removeById($id)
	{
		$class_snapshot = CLASS_SNAPSHOT;

		$node = self::fetchInsightNodes(array(PROPERTY_ID => $id));

		if (isset($node[$id]->{PROPERTY_PARENT}))

			$ascendant_node = self::fetchInsightNodes(array(PROPERTY_ID => $node[$id]->{PROPERTY_PARENT}));

		$children_nodes = self::fetchInsightNodes(array(PROPERTY_PARENT => $id));

		$parent_node = isset($ascendant_node) ? $ascendant_node : $node;

		list($node_id) = each($parent_node);

		if (isset($ascendant_node))
	
			$parent_node[$node_id]->{PROPERTY_CHILDREN} =  $node;

		else if (count($children_nodes) != 0)

			$parent_node[$node_id]->{PROPERTY_CHILDREN} =  $children_nodes;

		if (isset($ascendant_node) && count($children_nodes) != 0)

			$parent_node[$node_id]->{PROPERTY_CHILDREN}[$id]->{PROPERTY_CHILDREN} = $children_nodes;
	
		$insight_snapshot = new self($parent_node);

		// make a snapshot of a sub thread
		$class_snapshot::add(array(PROPERTY_STATE => $insight_snapshot));
	
		$insight_node = new self(
			array(
				PROPERTY_ID => $id,
				PROPERTY_STATUS => INSIGHT_NODE_STATUS_INACTIVE
			)
		);

		$return_url = $insight_node->serialize();

		// if the removed insight had children, they are hooked to their closest parent
		if (count($children_nodes) > 0)

			while (list($child_id, $child) = each($children_nodes))
			{
				$child_node = new self(
					array(
						PROPERTY_ID => $child_id,
						PROPERTY_PARENT => $node[$id]->{PROPERTY_PARENT}
					)
				);

				$child_node->serialize();
			}

		return $return_url;
	}

	/**
	* Reunite relatives
	* 
	* @param	integer $target
	* @param	integer $target_type
	* @return	mixed	reunited relatives
	*/
	public static function reuniteRelatives($target, $target_type)
	{
		global $class_application;

		$class_data_fetcher = $class_application::getDataFetcherClass();

		$tree = $class_data_fetcher::fetchThread($target, $target_type, SCHEMA_TYPE_TREE);

		$parents = $class_data_fetcher::fetchThread($target, $target_type, SCHEMA_TYPE_PARENTS);

		$childen = $class_data_fetcher::fetchThread($target, $target_type, SCHEMA_TYPE_CHILDREN);

		$remove_children = function (&$value, $index)
		{
			$_value = array();

			while (list($node_id, $node) = each($value))
			
				$_value[$node_id] = $node_id;

			$value = $_value;
		};

		$parent_nodes =
		$parents = array_reverse($parents, TRUE);

		array_walk($parent_nodes, $remove_children);

		while (list($parent_id, $children) = each($parents))
		{
			while (list($child_id, $child) = each($children))
			{
				if (isset($parent_nodes[$child_id]))
				{
					while (list($node_id, $node) = each($parents[$child_id]))
					{
						if (!isset($parents[$parent_id][$child_id]->{PROPERTY_CHILDREN}))

							$parents[$parent_id][$child_id]->{PROPERTY_CHILDREN} = array();

						if ($parents[$child_id][$node_id]->{PROPERTY_PARENT} == $child_id)
						{
							$parents[$parent_id][$child_id]->{PROPERTY_CHILDREN}[$node_id] = &$parents[$child_id][$node_id];
						}
					}

					reset($parents[$child_id]);
				}
			}
			
			reset($children);
		}

		$clean_relatives = function (&$family)
		{
			$minima = array();

			$level = &$family;

			$ground_floor = &$family;

			$k = 0;

			while (is_array($level) && count($level) > 0 && NULL !== ($minima[$k] = min(array_keys($level))))
			{
				if (is_object($level[$minima[$k]]) && isset($level[$minima[$k]]->{PROPERTY_CHILDREN}))

					$level = &$level[$minima[$k]]->{PROPERTY_CHILDREN};
				else
				
					$level = &$level[$minima[$k]];				

				$k++;
			}

			$k = 0;

			$family = &$ground_floor;

			end($family);
			list($last_node) = each($family);
			reset($family);

			while (is_array($ground_floor) && (list($index, $node) = each($ground_floor)))
			{
				if (
					$index != $minima[$k] &&
					isset($ground_floor[$index])
				)
				{
					// to be resumed
					if (
						$ground_floor[$index] &&
						!is_object($ground_floor[$index]) && !is_object($ground_floor[$minima[$k]])
						||
						is_object($ground_floor[$index]) && is_object($ground_floor[$minima[$k]]) &&
						$ground_floor[$index]->{PROPERTY_PARENT} != $ground_floor[$minima[$k]]->{PROPERTY_PARENT}
					)

						unset($ground_floor[$index]);
				}

				if ($index == $last_node)
				{
					if (is_object($ground_floor[$minima[$k]]) && isset($ground_floor[$minima[$k]]->{PROPERTY_CHILDREN}))
					{
						list($node_id, $node) = each($ground_floor[$minima[$k]]->{PROPERTY_CHILDREN});
					
						$ground_floor = &$ground_floor[$minima[$k]]->{PROPERTY_CHILDREN};
					}
					else 

						$ground_floor = &$ground_floor[$minima[$k]];

					end($ground_floor);
					list($last_node) = each($ground_floor);
					reset($ground_floor);

					$k++;
				}
			}

			reset($ground_floor);
			
			$family = &$ground_floor;
		};

		reset($parents);

		$clean_relatives($parents);

		return $parents;
	}
}
?>