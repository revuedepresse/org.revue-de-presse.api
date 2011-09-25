<?php

/**
* Book class
*
* Class for handling book
* @package  sefi
*/
class Book extends Content
{
	/**
	* Update the books feeds
	* 
	* @param 	integer	$count	number of items to be updated
	* @return	nothing
	*/
	public static function fetchBookFeed( $count = 1, $verbose = FALSE )
	{
		global $class_application;
	
		$class_api = $class_application::getApiClass();
		
		$class_book = $class_application::getBookClass();
		
		$class_dumper = $class_application::getDumperClass();
		
		$collection_book = self::getBookByStatus( BOOK_STATUS_UNKNOWN, $count );

		if ( ! is_array( $collection_book ) )
		
			$collection_book = array( 0 => $collection_book );

		$count_books = count( $collection_book );
	
		$index_max = $count_books - 1;
		
		$status = BOOK_STATUS_FOUND;	
		
		for ( $index = $index_max; $index >= 0 ; $index-- )
		{
			$book =	$collection_book[$index];
		
			$book_id = $book->{PROPERTY_ID};
			
			$book = $class_book::getById( $book_id );
		
			// Get a book by providing its ISBN
			$response = $class_api::lookUpBookByISBN( $book->{PROPERTY_ISBN} . '' );
		
			$class_dumper::log(
				__METHOD__,
				array(
					'$book  = $class_book::getById( $book_id )', $book,
					'[response]', $response
				),
				$verbose
			);

			unset( $book->{PROPERTY_ENTITY_NAME} );
		
			$book->{PROPERTY_FEED} = serialize( $response );
			$book->{PROPERTY_STATUS} = $status;

			$class_dumper::log(
				__METHOD__,
				array(
					'$book->sync()', $book->sync(),
					'$book', $book
				),
				$verbose
			);
		}
	}

	/**
	* Get a Book by providing its id
	*
	* @param	integer	$id	identifier
	* @return	object	Store
	*/
	public static function getById($id)
	{
		if ( ! is_numeric( $id  ) )
			
			throw new Exception( EXCEPTION_INVALID_ARGUMENT );

		return self::getByProperty(
			$id,
			PROPERTY_ID,
			array(
				PROPERTY_ID,
				PROPERTY_ISBN,
				PROPERTY_FEED,
				PROPERTY_STATUS
			),
			CLASS_BOOK,
			TRUE
		);	
	}

	/**
	* Get a book by its status
	*
	* @param	string	$status status
	* @param	string	$count	number of items to be retrieved
	* @return	object	mixed
	*/
	public static function getBookByStatus( $status = NULL, $count = 1 )
	{
		$book_status = $status;
		
		if ( ! is_integer( $status ) )
	
			$book_status  = BOOK_STATUS_UNKNOWN;

		$properties = self::fetchProperties(
			array(
				SQL_LIMIT => array(
					PROPERTY_COUNT => $count
				),
				SQL_SELECT => array(
					PROPERTY_ID,
					PROPERTY_ISBN,
					PROPERTY_STATUS,
					PROPERTY_FEED
				),
				PROPERTY_STATUS => $book_status,
			),
			__CLASS__,
			FALSE,
			FALSE // memcache flag
		);

		return $properties;
	}

	/**
	* Get a signature
	*
	* @param	boolean	$namespace	namespace flag
	* @return	string	signature
	*/
	public static function getSignature( $namespace = TRUE )
	{
		$_class = __CLASS__;

		if ( ! $namespace )

			list( $_namespace, $_class ) = explode( '\\', __CLASS__ );

		return $_class;
	}

	/**
	* Make an instance of the Book class
	*
	* @return	object	Book instance
	*/
	public static function make()
	{
		global $class_application;
		
		$class_dumper = $class_application::getDumperClass();

		$arguments = func_get_args();

		if ( isset( $arguments[0] ) )

			$isbn = $arguments[0];
		else

			throw new Exception( EXCEPTION_INVALID_ARGUMENT );

		if ( isset( $arguments[1] ) )

			$status = $arguments[1];
		else

			throw new Exception( EXCEPTION_INVALID_ARGUMENT );

		$feed = NULL;

		if ( isset( $arguments[2] ) )

			$feed = $arguments[2];

		$properties = array(
			PROPERTY_ISBN => $isbn,
			PROPERTY_STATUS => $status
		);

		if ( isset( $feed ) )
		
			$properties = array_merge(
				$properties,
				array( PROPERTY_FEED => $feed )
			);

		return self::add( $properties );
	}
}

/**
*************
* Changes log
*
*************
* 2011 09 25
*************
*
* development :: api :: amazon
*
* Implement book maker, feed fetching from Amazon's Product Advertising API
*
* method affected ::
*
* BOOK :: fetchBookFeed
* BOOK :: getSignature
* BOOK :: make
*
* (branch 0.1 :: revision :: 658)
*
*/