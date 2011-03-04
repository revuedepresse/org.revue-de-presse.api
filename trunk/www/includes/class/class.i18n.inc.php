<?php

/**
* I18n class
*
* Class for handling internationalization
* @package  sefi
*/
class I18n extends File_Manager
{
	protected $language_code;

	/**
    * load a store
    *
    * @param	string	$store_name		containing a store name
    * @param	string 	$language		containing a language code
    * @param	integer	$storage_model	representing a store model
    * @param	string	$encoding 		containing an encoding
	* @param	array	$cache			cache flag 
    * @return 	nothing
	*/
	public static function load_store(
		$store_name = NULL,
		$language = LANGUAGE_CODE_ENGLISH,
		$storage_model = STORE_DATABASE,
		$encoding = I18N_ENCODING_UTF8,
		$cache = CACHE_STATUS_FORCE_REFRESH
	)
	{
		switch ($storage_model)
		{
			case STORE_DATABASE:

				$class_data_fetcher = CLASS_DATA_FETCHER;

				if (
					$cache ||
					!isset($_SESSION[STORE_I18N]) ||
					!isset($_SESSION[STORE_I18N][$language]) ||
					!isset($_SESSION[STORE_I18N][$language][$store_name]) ||
					!count($_SESSION[STORE_I18N][$language][$store_name])
				)
				{
					if (!isset($_SESSION[STORE_I18N]))
		
						$_SESSION[STORE_I18N] = array();
				
					if (!isset($_SESSION[STORE_I18N][$language]))
		
						$_SESSION[STORE_I18N][$language] = array();
		
					if (!isset($_SESSION[STORE_I18N][$language][$store_name]))
		
						$_SESSION[STORE_I18N][$language][$store_name] = array();

					$class_data_fetcher::fetchI18nItems(
						array(
							PROPERTY_NAMESPACE => $store_name,
							PROPERTY_LANGUAGE => $language
						),
						$_SESSION[STORE_I18N][$language][$store_name]
					);
				}

				self::loadItems(
					$_SESSION[STORE_I18N][$language][$store_name],
					$store_name,
					$language
				);

					break;

			case STORE_XML:

				// get the current language
				$original_language = lang::get();

				// check the encoding argument
				if (
					!defined(I18N_ENCODING_UTF8) &&
					$encoding == I18N_ENCODING_UTF8
				)
					self::utf8();

				if (!empty($storage_model))
				{
					$file_path =
						dirname(__FILE__).'/../'.
						CHARACTER_SLASH.DIR_I18N.CHARACTER_SLASH.
						$store_name.'-'.parent::get().EXTENSION_XML
					;
		
					// check the file path
					if (file_exists($file_path) && !is_dir($file_path))
		
						parent::load($store_name);
				}
		
				// restore the original language
				self::setCurrentLanguage($original_language);

					break;
		}
	}

	/**
    * Load items
    *
    * @param	array	$items		items
    * @param	string	$namespace	namespace
    * @param	string	$language	language
    * @return 	nothing
	*/
	public static function loadItems(
		array $items,
		$namespace = NULL,
		$language = LANGUAGE_CODE_ENGLISH
	)
	{
		if (is_null($namespace))

			$namespace = I18N_STORE_FORM;

		else if (empty($namespace))

			throw new Exception(EXCEPTION_INVALID_ARGUMENT);

		while (list($item_id, $item) = each($items))

			if (
				isset($item->{PROPERTY_NAME}) &&
				isset($item->{PROPERTY_VALUE}) &&
				!defined(strtoupper($namespace.'_'.$item->{PROPERTY_NAME}))
			)
				
				define(strtoupper($namespace.'_'.$item->{PROPERTY_NAME}), $item->{PROPERTY_VALUE});
	}

	/**
    * Alias to the load_store method 
    *
    * @param	string	$store_name		containing a store name
    * @param	string 	$language		containing a language code
    * @param	integer	$storage_model	representing a store model
    * @param	string	$encoding 		containing an encoding
    * @return 	nothing
	*/
	public static function loadStore(
		$store_name = NULL,
		$language = LANGUAGE_CODE_ENGLISH,
		$storage_model = STORE_DATABASE,
		$encoding = I18N_ENCODING_UTF8
	)
	{
		return self::load_store(
			$store_name,
			$language,
			$storage_model,
			$encoding
		);
	}

	/*
	* Get the static charsert
	*
	* @return string
	*/
	public static function &getCharset()
	{
		$charset = &self::$utf8;

		return $charset;
	}

	/*
	* Set the UTF-8 charset 
	*
	* @return nothing
	*/
	public static function utf8()
	{
		$charset = &self::getCharset();
		$charset = TRUE;
		define('UTF8', TRUE);
	}

	/*
	* Import data into the database
	*
	* @param 	string	$store_name			store name
	* @param	string	$file_extension		file extension
	* @param	string	$language			language
	* @param	string	$source_directory	source directory
	* @return 	nothing
	*/
	public static function importData(
		$store_name,
		$file_extension = EXTENSION_XML,
		$language = I18N_DEFAULT_LANGUAGE,
		$source_directory = FALSE
	)
	{
		global $class_application, $verbose_mode;

		$class_event = $class_application::getEventClass();

		$class_serializer = $class_application::getSerializerClass();

		$class_dom_document = $class_application::getDomDocumentClass();
	
		$dom_document = new $class_dom_document();

		if (!$source_directory)

			$source_directory = dirname(__FILE__).'/../'.DIR_I18N;

		$file_path = $source_directory.'/'.$store_name.'-'.$language.$file_extension;

		if (file_exists($file_path))

			$document = $dom_document->load($file_path);

		$node_list = $dom_document->getElementsByTagName('s');

		$node_count = $node_list->length;

		for ( $node_index = 0 ; $node_index < $node_count ; $node_index++ )
		{
			$node = $node_list->item($node_index);

			if (
				trim($node->getAttribute('id')) != '' &&
				isset($node->firstChild) &&
				isset($node->firstChild->textContent)
			)

				$class_serializer::importLanguageItem(
					array(
						PROPERTY_LANGUAGE => $language,
						PROPERTY_NAME => $node->getAttribute('id'),
						PROPERTY_NAMESPACE => $store_name,
						PROPERTY_VALUE => $node->firstChild->textContent
					)
				);

			else if (trim($node->getAttribute('id') != ''))

				$class_event::logEvent(
					array(
						PROPERTY_DESCRIPTION => sprintf(EVENT_DESCRIPTION_EMPTY_NODE, $node->getAttribute('id')),
						PROPERTY_TYPE => EVENT_TYPE_LANGUAGE_ITEM_IMPORT
					)
				);
			else

				$class_event::logEvent(
					array(
						PROPERTY_DESCRIPTION => sprintf(EVENT_DESCRIPTION_WRONG_NODE_DEFINITION, $node_index),
						PROPERTY_TYPE => EVENT_TYPE_LANGUAGE_ITEM_IMPORT
					)
				);
		}
	}

	/*
	* Import i18n data
	*
	* @return 	nothing
	*/
	public static function importI18nData()
	{
		if ( isset( $_GET[GET_FILE] ) && ! empty( $_GET[GET_FILE] ) )
		
			$store_name = $_GET[GET_FILE];

		if ( ! empty( $store_name ) )
		
			self::importData( $store_name );
		else
		
			throw new Exception( EXCEPTION_INVALID_ARGUMENT );
	}

	/*
	* Set the current language
	*
	* @param 	string	$language_code	language code
	* @return 	nothing
	*/
	public static function setCurrentLanguage($language_code)
	{
		self::$language_code = $language_code;
	}
}
?>