<?php

/**
	PHP Fat-Free Framework - Less Hype, More Meat.

	Fat-Free is a modular and lightweight PHP 5.3+ Web development framework
	designed to help build dynamic Web sites. This Database Pack works
	seamlessly with the Fat-Free Core and includes the Axon auto-mapping ORM
	and an easy-to-use SQL handler for interfacing with any database engine.

	The contents of this file are subject to the terms of the GNU General
	Public License Version 3.0. You may not use this file except in
	compliance with the license. Any of the license terms and conditions
	can be waived if you get permission from the copyright holder.

	Copyright (c) 2009-2010 Fat-Free Factory
	Bong Cosca <bong.cosca@yahoo.com>

		@package Database
		@version 1.2.8
**/

//! Database Pack
class F3db {

	//@{
	//! Locale-specific error/exception messages
	const TEXT_DBConnect='Database connection failed';
	const TEXT_PDOExt='PDO extension {@CONTEXT} is not enabled';
	const TEXT_SQL='SQL error - {@CONTEXT}';
	const TEXT_AxonEngine='Database engine is not supported';
	const TEXT_AxonTable='Unable to map table {@CONTEXT} to Axon';
	const TEXT_AxonEmpty='Axon is empty';
	const TEXT_AxonNotMapped='The field {@CONTEXT} does not exist';
	const TEXT_AxonCantUndef='Cannot undefine an Axon-mapped field';
	const TEXT_AxonCantUnset='Cannot unset an Axon-mapped field';
	const TEXT_AxonConflict='Name conflict with Axon-mapped field';
	const TEXT_AxonNotVirtual='{@CONTEXT} is not a virtual field';
	const TEXT_AxonInvalid='Invalid virtual field expression';
	const TEXT_AxonReadOnly='Virtual fields are read-only';
	//@}

	//! Default cache timeout for Axon sync method
	const SYNC_Default=60;

	/**
		Convert characters that need to be quoted in database queries to
		XML entities; quote string and add commas between each argument
			@return string
			@public
			@static
	**/
	public static function qq() {
		$_args=func_get_args();
		$_argn=func_num_args();
		$_text='';
		foreach ($_args as $_arg)
			$_text.=($_text?',':'').
				is_string($_arg)?('"'.F3::convQuotes($_arg).'"'):$_arg;
		return $_text;
	}

	/**
		Retrieve from cache; or save SQL query results to cache if not
		previously executed
			@param $_cmd string
			@param $_id string
			@param $_ttl integer
			@private
			@static
	**/
	private static function sqlCache($_cmd,$_id='DB',$_ttl) {
		// Get hash code for SQL command
		if (!file_exists(F3::$global['CACHE']))
			// Create the framework's cache folder
			mkdir(F3::$global['CACHE']);
		$_file=F3::$global['CACHE'].'sql-'.F3::hashCode($_cmd);
		// Reset PHP's stat cache
		clearstatcache();
		$_db=&F3::$global[$_id];
		if (file_exists($_file) && (
			F3::$global['TIME']-filemtime($_file))<$_ttl) {
			// Gather cached SQL queries for profiler
			F3::$profile[$_id]['cache'][$_cmd]++;
			// PHP doesn't allow serialization of resources; save PDO
			$_pdo=$_db['pdo'];
			// Retrieve file from cache, convert JSON string,
			// and restore DB variable
			$_db=json_decode(
				gzinflate(file_get_contents($_file)),TRUE
			);
			// Restore PDO
			$_db['pdo']=$_pdo;
		}
		else {
			self::sqlExec($_cmd,$_id);
			if (!F3::$global['ERROR'])
				// Convert DB variable to JSON string, compress and cache
				file_put_contents($_file,gzdeflate(json_encode($_db)));
		}
	}

	/**
		Execute SQL statements
			@param $_cmd string
			@private
			@static
	**/
	private static function sqlExec($_cmd,$_id='DB') {
		// Gather real SQL queries for profiler
		F3::$profile[$_id]['queries'][$_cmd]++;
		// Execute SQL statement
		$_db=&F3::$global[$_id];
		$_db['query']=$_db['pdo']->query($_cmd,PDO::FETCH_LAZY);
		// Check SQLSTATE
		if ($_db['pdo']->errorCode()!='00000') {
			// Gather info about error
			$_text=$_db['pdo']->errorInfo();
			F3::$global['CONTEXT']=
				$_text[0].' ('.$_text[1].') '.$_text[2];
			trigger_error(F3::resolve(F3db::TEXT_SQL));
			return;
		}
		// Save result
		$_db['result']=$_db['query']->fetchAll(PDO::FETCH_ASSOC);
	}

	/**
		Process SQL statement(s)
			@param $_cmds mixed
			@param $_id string
			@param $_ttl integer
			@public
			@static
	**/
	public static function sql($_cmds,$_id='DB',$_ttl=0) {
		$_db=&F3::$global[$_id];
		// Connect to database once
		if (!$_db || !$_db['dsn']) {
			// Unable to connect
			trigger_error(F3db::TEXT_DBConnect);
			return;
		}
		if (!$_db['pdo']) {
			$_ext='pdo_'.stristr($_db['dsn'],':',TRUE);
			if (!extension_loaded($_ext)) {
				// PDO extension not activated
				F3::$global['CONTEXT']=$_ext;
				trigger_error(F3::resolve(F3db::TEXT_PDOExt));
				return;
			}
			$_db['pdo']=@new PDO(
				$_db['dsn'],$_db['user'],$_db['password'],$_db['options']
			);
			if (!$_db['pdo']) {
				// Unable to connect
				trigger_error(F3db::TEXT_DBConnect);
				return;
			}
			// Retain data types
			$_db['pdo']->setAttribute(PDO::ATTR_EMULATE_PREPARES,FALSE);
			$_db['pdo']->setAttribute(PDO::ATTR_PERSISTENT,TRUE);
			// Define connection attributes
			$_attrs=explode('|',
				'AUTOCOMMIT|ERRMODE|CASE|CLIENT_VERSION|CONNECTION_STATUS|'.
				'PERSISTENT|PREFETCH|SERVER_INFO|SERVER_VERSION|TIMEOUT'
			);
			// Save attributes in DB global variable
			foreach ($_attrs as $_attr)
				// Suppress warning if SQL driver doesn't support attribute
				if ($_val=@$_db['pdo']->
					getAttribute(constant('PDO::ATTR_'.$_attr)))
						$_db['attributes'][$_attr]=$_val;
		}
		if (!is_array($_cmds))
			// Convert to array to prevent code duplication
			$_cmds=array($_cmds);
		// Remove empty elements
		$_cmds=array_diff($_cmds,array(NULL));
		$_db['result']=NULL;
		if (count($_cmds)>1)
			// More than one SQL statement specified
			$_db['pdo']->beginTransaction();
		foreach ($_cmds as $_cmd) {
			if (F3::$global['ERROR'])
				break;
			$_cmd=F3::resolve($_cmd);
			if ($_ttl)
				// Cache results
				self::sqlCache($_cmd,$_id,$_ttl);
			else
				// Execute SQL statement(s)
				self::sqlExec($_cmd,$_id);
		}
		if (count($_cmds)>1) {
			if (F3::$global['ERROR'])
				// Roll back transaction
				$_db['pdo']->rollBack();
			else
				// Commit transaction
				$_db['pdo']->commit();
		}
	}

	/**
		Database Pack bootstrap code
			@public
			@static
	**/
	public static function onLoad() {
		F3::$global['SYNC']=self::SYNC_Default;
	}

	/**
		Intercept calls to undefined static methods
			@return mixed
			@param $_func string
			@param $_args array
			@public
			@static
	**/
	public static function __callStatic($_func,array $_args) {
		F3::$global['CONTEXT']=$_func;
		trigger_error(F3::resolve(F3::TEXT_Method));
	}

	/**
		Class constructor
			@public
	**/
	public function __construct() {
		// Prohibit use of Database Pack as an object
		trigger_error(F3::TEXT_Object);
	}

}

//! Axon ORM
class Axon {

	//{@
	//! Axon properties
	private $DB=NULL;
	private $TABLE=NULL;
	private $KEYS=array();
	private $CRITERIA=NULL;
	private $OFFSET=NULL;
	private $FIELDS=array();
	private $VIRTUAL=array();
	private $EMPTY=TRUE;
	//@}

	/**
		Similar to Axon->find method but provides more fine-grained control
		over specific fields and grouping of results
			@param $_fields string
			@param $_criteria string
			@param $_grouping string
			@param $_order string
			@param $_limit string
			@param $_ttl integer
			@public
	**/
	public function lookup(
		$_fields,
		$_criteria=NULL,
		$_grouping=NULL,
		$_order=NULL,
		$_limit=NULL,
		$_ttl=0) {
			F3::sql(
				'SELECT '.$_fields.' FROM '.$this->TABLE.
					(is_null($_criteria)?'':(' WHERE '.$_criteria)).
					(is_null($_grouping)?'':(' GROUP BY '.$_grouping)).
					(is_null($_order)?'':(' ORDER BY '.$_order)).
					(is_null($_limit)?'':(' LIMIT '.$_limit)).';',
				$this->DB,
				$_ttl
			);
			return F3::$global[$this->DB]['result'];
	}

	/**
		Alias of the lookup method
			@public
	**/
	public function select() {
		// PHP doesn't allow direct use as function argument
		$_args=func_get_args();
		return call_user_func_array('self::lookup',$_args);
	}

	/**
		Return an array of DB records matching criteria
			@return array
			@param $_criteria string
			@param $_order string
			@param $_limit string
			@param $_ttl integer
			@public
	**/
	public function find(
		$_criteria=NULL,
		$_order=NULL,
		$_limit=NULL,
		$_ttl=0) {
			return $this->lookup('*',$_criteria,NULL,$_order,$_limit,$_ttl);
	}

	/**
		Return number of DB records that match criteria
			@return integer
			@param $_criteria string
			@param $_ttl integer
			@public
	**/
	public function found($_criteria=NULL,$_ttl=0) {
		$_result=$this->
			lookup('COUNT(*) AS found',$_criteria,NULL,NULL,NULL,$_ttl);
		return $_result[0]['found'];
	}

	/**
		Hydrate Axon with elements from framework array variable, keys of
		which must be identical to field names in DB record
			@param $_name string
			@public
	**/
	public function copyFrom($_name) {
		reset($this->FIELDS);
		while (list($_field,)=each($this->FIELDS))
			if (array_key_exists($_field,F3::get($_name)))
				$this->FIELDS[$_field]=F3::get($_name.'.'.$_field);
		$this->EMPTY=FALSE;
	}

	/**
		Populate framework array variable with Axon properties, keys of
		which will have names identical to fields in DB record
			@param $_name string
			@public
	**/
	public function copyTo($_name) {
		reset($this->FIELDS);
		while (list($_field,)=each($this->FIELDS))
			F3::set($_name.'.'.$_field,$this->FIELDS[$_field]);
	}

	/**
		Dehydrate Axon
			@private
	**/
	private function reset() {
		// Null out fields
		reset($this->FIELDS);
		while (list($_field,)=each($this->FIELDS))
			if (!preg_match('/TABLE|KEYS|FIELDS|EMPTY/',$_field))
				$this->FIELDS[$_field]=NULL;
		if ($this->KEYS) {
			// Null out primary keys
			reset($this->KEYS);
			while (list($_field,)=each($this->KEYS))
				$this->KEYS[$_field]=NULL;
		}
		// Dehydrate Axon
		$this->EMPTY=TRUE;
		$this->OFFSET=NULL;
	}

	/**
		Retrieve first DB record that satisfies criteria
			@param $_criteria string
			@param $_order string
			@param $_offset integer
			@public
	**/
	public function load($_criteria=NULL,$_order=NULL,$_offset=0) {
		if (method_exists($this,'beforeLoad'))
			// Execute beforeLoad event
			$this->beforeLoad();
		if (!is_null($_offset) && $_offset>-1) {
			$_virtual='';
			foreach ($this->VIRTUAL as $_field=>$_value)
				$_virtual.=($_virtual?',':'').
					'('.$_value['expr'].') AS '.$_field;
			// Retrieve record
			$_result=$this->lookup(
				'*'.($_virtual?(','.$_virtual):''),
				$_criteria,NULL,$_order,'1 OFFSET '.$_offset
			);
			$this->OFFSET=NULL;
			if ($_result) {
				// Hydrate Axon
				foreach ($_result[0] as $_field=>$_value) {
					if (array_key_exists($_field,$this->FIELDS)) {
						$this->FIELDS[$_field]=$_value;
						if (array_key_exists($_field,$this->KEYS))
							$this->KEYS[$_field]=$_value;
					}
					else
						$this->VIRTUAL[$_field]['value']=$_value;
				}
				$this->EMPTY=FALSE;
				$this->CRITERIA=$_criteria;
				$this->OFFSET=$_offset;
			}
			else
				$this->reset();
		}
		else
			$this->reset();
		if (method_exists($this,'afterLoad'))
			// Execute afterLoad event
			$this->afterLoad();
	}

	/**
		Retrieve N-th record relative to current using the same criteria
		that hydrated the Axon
			@param $_count integer
			@public
	**/
	public function skip($_count=1) {
		if ($this->dry()) {
			trigger_error(F3db::TEXT_AxonEmpty);
			return;
		}
		self::load($this->CRITERIA,$this->OFFSET+$_count);
	}

	/**
		Insert/update DB record
			@public
	**/
	public function save() {
		if ($this->EMPTY) {
			// Axon is empty
			trigger_error(F3db::TEXT_AxonEmpty);
			return;
		}
		if (method_exists($this,'beforeSave'))
			// Execute beforeSave event
			$this->beforeSave();
		$_new=TRUE;
		if ($this->KEYS)
			// If ALL primary keys are NULL, this is a new record
			foreach ($this->KEYS as $_value)
				if (!is_null($_value)) {
					$_new=FALSE;
					break;
				}
		if ($_new) {
			// Insert new record
			$_fields='';
			$_values='';
			foreach ($this->FIELDS as $_field=>$_value)
				if (!preg_match('/TABLE|KEYS|FIELDS|EMPTY/',$_field)) {
					$_fields.=($_fields?',':'').$_field;
					$_values.=($_values?',':'').
						(is_null($_value)?'NULL':F3::qq($_value));
				}
			F3::sql(
				'INSERT INTO '.$this->TABLE.' ('.$_fields.') '.
					'VALUES ('.$_values.');',
				$this->DB
			);
		}
		else {
			// Update record
			$_set='';
			foreach ($this->FIELDS as $_field=>$_value)
				if (!preg_match('/TABLE|KEYS|FIELDS|EMPTY/',$_field))
					$_set.=($_set?',':'').($_field.'='.
						(is_null($_value)?'NULL':F3::qq($_value)));
			// Use prior primary key values (if changed) to find record
			$_cond='';
			foreach ($this->KEYS as $_key=>$_value)
				$_cond.=($_cond?' AND ':'').($_key.
					(is_null($_value)?' IS NULL':('='.F3::qq($_value))));
			F3::sql(
				'UPDATE '.$this->TABLE.' SET '.$_set.
					(is_null($_cond)?'':(' WHERE '.$_cond)).';',
				$this->DB
			);
		}
		if ($this->KEYS) {
			// Update primary keys with new values
			reset($this->KEYS);
			while (list($_field,)=each($this->KEYS))
				$this->KEYS[$_field]=$this->FIELDS[$_field];
		}
		if (method_exists($this,'afterSave'))
			// Execute afterSave event
			$this->afterSave();
	}

	/**
		Delete DB record and reset Axon
			@public
	**/
	public function erase() {
		if ($this->EMPTY) {
			trigger_error(F3db::TEXT_AxonEmpty);
			return;
		}
		if (method_exists($this,'beforeErase'))
			// Execute beforeErase event
			$this->beforeErase();
		if ($this->KEYS) {
			// Use prior primary key values (if changed) to find record
			$_cond='';
			foreach ($this->KEYS as $_key=>$_value)
				$_cond.=($_cond?' AND ':'').($_key.
					(is_null($_value)?' IS NULL':('='.F3::qq($_value))));
			F3::sql(
				'DELETE FROM '.$this->TABLE.
					(is_null($_cond)?'':(' WHERE '.$_cond)).';',
				$this->DB
			);
		}
		$this->reset();
		if (method_exists($this,'afterErase'))
			// Execute afterErase event
			$this->afterErase();
	}

	/**
		Return TRUE if Axon is devoid of values in its properties
			@return boolean
			@public
	**/
	public function dry() {
		return $this->EMPTY;
	}

	/**
		Synchronize Axon and table structure
			@param $_table string
			@param $_id string
			@public
	**/
	public function sync($_table,$_id='DB') {
		$_db=&F3::$global[$_id];
		// Can't proceed until DSN is set
		if (!$_db || !$_db['dsn']) {
			trigger_error(F3db::TEXT_DBConnect);
			return;
		}
		if (method_exists($this,'beforeSync'))
			// Execute beforeSync event
			$this->beforeSync();
		// MySQL schema
		if (preg_match('/^mysql\:/',$_db['dsn'])) {
			$_cmd='SHOW columns FROM '.$_table.';';
			$_fields=array('Field','Key','PRI');
		}
		// SQLite schema
		elseif (preg_match('/^sqlite[2]*\:/',$_db['dsn'])) {
			$_cmd='PRAGMA table_info('.$_table.');';
			$_fields=array('name','pk',1);
		}
		// SQL Server/Sybase/DBLib/ProgreSQL schema
		elseif (preg_match('/^(mssql|sybase|dblib|pgsql)\:/',$_db['dsn'])) {
			$_cmd='SELECT C.column_name AS field,T.constraint_type AS key '.
				'FROM information_schema.columns C '.
				'LEFT OUTER JOIN information_schema.key_column_usage K '.
					'ON C.table_name=K.table_name AND '.
						'C.column_name=K.column_name '.
				'LEFT OUTER JOIN information_schema.table_constraints T '.
					'ON K.table_name=T.table_name AND '.
						'K.constraint_name=T.constraint_name '.
				'WHERE C.table_name="'.$_table.'";';
			$_fields=array('field','key','PRIMARY KEY');
		}
		// Unsupported DB engine
		else {
			trigger_error(F3db::TEXT_AxonEngine);
			return;
		}
		F3::sql($_cmd,$_id,F3::$global['SYNC']);
		$_result=$_db['result'];
		if (!$_result) {
			F3::$global['CONTEXT']=$_table;
			trigger_error(F3::resolve(F3db::TEXT_AxonTable));
			return;
		}
		// Initialize Axon
		$this->DB=$_id;
		$this->TABLE=$_table;
		foreach ($_result as $_col) {
			// Populate properties
			$this->FIELDS[$_col[$_fields[0]]]=NULL;
			if ($_col[$_fields[1]]==$_fields[2])
				// Save primary key
				$this->KEYS[$_col[$_fields[0]]]=NULL;
		}
		$this->EMPTY=TRUE;
		if (method_exists($this,'afterSync'))
			// Execute afterSync event
			$this->afterSync();
	}

	/**
		Create a virtual field
			@param $_name string
			@param $_expr string
			@public
	**/
	public function def($_name,$_expr) {
		if (array_key_exists($_name,$this->FIELDS)) {
			trigger_error(F3db::TEXT_AxonConflict);
			return;
		}
		if (!is_string($_expr) || !strlen($_expr)) {
			trigger_error(F3db::TEXT_AxonInvalid);
			return;
		}
		$this->VIRTUAL[$_name]['expr']=F3::resolve($_expr);
	}

	/**
		Destroy a virtual field
			@param $_name string
			@public
	**/
	public function undef($_name) {
		if (array_key_exists($_name,$this->FIELDS)) {
			trigger_error(F3db::TEXT_AxonCantUndef);
			return;
		}
		if (!array_key_exists($_name,$this->VIRTUAL)) {
			F3::$global['CONTEXT']=$_name;
			trigger_error(F3::resolve(F3db::TEXT_AxonNotMapped));
			return;
		}
		unset($this->VIRTUAL[$_name]);
	}

	/**
		Return TRUE if virtual field exists
			@param $_name
			@public
	**/
	public function isdef($_name) {
		return array_key_exists($_name,$this->VIRTUAL);
	}

	/**
		Return value of Axon-mapped/virtual field
			@return boolean
			@param $_name string
			@public
	**/
	public function __get($_name) {
		if (array_key_exists($_name,$this->FIELDS))
			return $this->FIELDS[$_name];
		if (array_key_exists($_name,$this->VIRTUAL))
			return $this->VIRTUAL[$_name]['value'];
		F3::$global['CONTEXT']=$_name;
		trigger_error(F3::resolve(F3db::TEXT_AxonNotMapped));
	}

	/**
		Assign value to Axon-mapped field
			@return boolean
			@param $_name string
			@param $_value mixed
			@public
	**/
	public function __set($_name,$_value) {
		if (array_key_exists($_name,$this->FIELDS)) {
			$this->FIELDS[$_name]=F3::resolve($_value);
			if (!is_null($_value))
				// Axon is now hydrated
				$this->EMPTY=FALSE;
			return;
		}
		if (array_key_exists($_name,$this->VIRTUAL)) {
			trigger_error(F3db::TEXT_AxonReadOnly);
			return;
		}
		F3::$global['CONTEXT']=$_name;
		trigger_error(F3::resolve(F3db::TEXT_AxonNotMapped));
	}

	/**
		Clear value of Axon-mapped field
			@return boolean
			@param $_name string
			@public
	**/
	public function __unset($_name) {
		if (array_key_exists($_name,$this->FIELDS)) {
			trigger_error(F3db::TEXT_AxonCantUnset);
			return;
		}
		F3::$global['CONTEXT']=$_name;
		trigger_error(F3::resolve(F3db::TEXT_AxonNotMapped));
	}
	/**
		Return TRUE if Axon-mapped/virtual field exists
			@return boolean
			@param $_name string
			@public
	**/
	public function __isset($_name) {
		return array_key_exists($_name,$this->FIELDS);
	}

	/**
		Magic method for PHP's var_export function
			@return object
			@param $_args array
			@public
	**/
	public function __set_state(array $_args) {
		return new Axon($_args['TABLE']);
	}

	/**
		Intercept calls to undefined object methods
			@param $_func string
			@param $_args array
			@public
	**/
	public function __call($_func,array $_args) {
		F3::$global['CONTEXT']=$_func;
		trigger_error(F3::resolve(F3::TEXT_Method));
	}

	/**
		Just display 'Object' if conversion to string is attempted
			@public
	**/
	public function __toString() {
		return 'Object';
	}

	/**
		Axon constructor
			@param $_table string
			@param $_id string
			@public
	**/
	public function __construct($_table,$_id='DB') {
		$this->sync($_table,$_id);
	}

}

?>
