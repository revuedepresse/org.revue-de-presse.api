<?php

namespace cid;

/**
* cid namespace created under introspection project 
*
* @package  cid
*/

/**
* Tokens_Stream class
*
* Class for managing tokens streams
* @package  cid
*/
class Tokens_Stream extends \Alpha
{
    public $context;
    static protected $persistent_context;
    static protected $position;
    static protected $river;

    /**
    * Construct an introspection class
    * Instantiate a TOKENS_STREAM
    * by initializing CONTEXT and CONDITIONS properties
    * @see      ALPHA::__construct
    *
    * @param    object  $quantities quantities
    * @param    object  $conditions conditions
    * @return   object  Introspection
    */
    public function __construct( $conditions = null, $quantities = null )
    {
        if ( ! isset( $this->{PROPERTY_CONTEXT} ) )
            $this->{PROPERTY_CONTEXT} = array();

        if ( ! is_null( $conditions ) )

            $tokens_stream = parent::__construct( $conditions, $quantities );
        else
        {
            $this->{PROPERTY_CONDITIONS} = new \stdClass();
            $tokens_stream = $this;
        }
    
        return $tokens_stream;
    }

    /**
    * Get a member variable
    *
    * @param    string  $name   variable name
    * @return   mixed   position
    */
    public function &__get( $name )
    {
        if ( $name === PROPERTY_CONTEXT )

            $member_variable = &$this->{PROPERTY_CONTEXT};
        else

            $member_variable = &parent::__get( $name );

        return $member_variable;
    }

    /**
    * Close stream
    *
    * @param    mixed   $protocol   protocol
    * @param    boolean $handle_only
    * @return   nothing
    */
    public function close( $protocol = null, $handle_only = false )
    {
        $context = &$this->getContext();
        if ( is_null( $protocol ) ) $protocol = self::getProtocol();
    
        if ( ! $handle_only ) self::closeStream( $context );
        
        $options = self::extractOptions( $context );

        if (
            isset( $options[$protocol] ) &&
            ( $protocol_options = $options[$protocol] ) &&
            isset( $protocol_options[PROPERTY_CONTAINER_REFERENCES] ) && (
            $references = $protocol_options[PROPERTY_CONTAINER_REFERENCES]
            ) && isset( $references[PROPERTY_HANDLE] ) &&
            ( $handle = $references[PROPERTY_HANDLE] )
        )
        {
            if ( is_resource( $handle ) ) fclose( $handle );
        }
    }

    /**
    * Get an entry point belonging to a stream,
    * provided an entry point is a reference to a value
    * made available by the access key of a container called "river"
    * declared as a static property of the TOKENS_STREAM class
    * Generated keys are saved as key property
    * (of the current TOKENS_STREAM object)
    * @see      TOKENS_STREAM->getRiver()
    *
    * @param    string  $key    access key 
    * @return   string  entry point
    */
    public function &getEntryPoint( $key = null)
    {
        $river = &$this->getRiver();

        if ( is_null( $key ) )
        {
            $key = count( $river );
            //if ( $key > 1 ) unset( $river[$key - 2] );
            $river[$key] = array();
            $this->{PROPERTY_KEY} = $key;
        }

        $entry_point = &$river[$key];

        return $entry_point;
    }

    /**
    * Get a context
    *
    * @return   &array  context
    */
    public function &getContext()
    {
        return $this->{PROPERTY_CONTEXT};
    }

    /**
    * Get a key
    * 
    * @return mixed key
    */
    public function getKey()
    {
        $key = null;
        
        if ( isset( $this->{PROPERTY_KEY} ) ) $key = $this->{PROPERTY_KEY};
            
        return $key;
    }

    /**
    * Get option by name
    *
    * @param    string  $name       name
    * @param    mixed   $protocol   protocol
    * @return   mixed   option value
    */
    public function getOption( $name, $protocol = null )
    {
        $option = 
        $protocol_options = null;
        $options = $this->getOptions();

        if ( is_null( $protocol ) )
            $protocol = self::getProtocol();

        if ( isset( $options[$protocol] ) )
            $protocol_options = $options[$protocol];

        if ( str_key_arr( $name, $protocol_options ) )
            $option = $protocol_options[$name];

        return $option;
    }

    /**
    * Get options
    *
    * @return   array   context
    */
    public function getOptions()
    {
        $context = $this->getContext();

        return self::extractOptions( $context );
    }

    /**
    * Close a stream
    *
    * @return   nothing
    */
    public function stream_close()
    {
        //$this->close( NULL, TRUE );

        if ( 
            ( $consistent = $this->getOption( PROPERTY_SERIALIZABLE ) ) &&
            ( $consistent === true )
        )
            $this->setPosition( 0 );
    }

    /**
    * Tests for end-of-file on a file pointer
    * Retrieve current stream size before checking
    * if current position incremented with the current number of bytes read
    * would not reach nor exceed the limit 
    *
    * @return   nothing
    */
    public function stream_eof()
    {
        $position = self::getPosition();
        $options = $this->getOptions();

        if ( is_null( $position ) ) $position = 0;

        $size = 0;
        $stream = self::getStream( $this->getKey() );

        if ( isset( $stream->{PROPERTY_SIZE} ) )
            $size = $stream->{PROPERTY_SIZE};

        return $position >= $size;
    }
    
    /**
    * Open a stream
    *
    * @param    string  $path           access path
    * @param    string  $mode           opening mode
    * @param    integer $options        optional flags
    * @param    string  &$opened_path   path to opened resource 
    * @return   stream resource
    */
    public function stream_open(
        $path,
        $mode,
        $options = STREAM_REPORT_ERRORS,
        &$opened_path
    )
    {                   
        $callback_parameters = false;

        if (
            ! in_array( $mode,
                array(
                    FILE_ACCESS_MODE_APPEND_ONLY,
                    FILE_ACCESS_MODE_APPEND,
                    FILE_ACCESS_MODE_READ_ONLY,
                    FILE_ACCESS_MODE_OVERWRITE,
                    FILE_ACCESS_MODE_WRITE_ONLY,
                    FILE_ACCESS_MODE_WRITE
                )
            )
        )
            throw new \Exception(
                sprintf(
                    EXCEPTION_INVALID_PROPERTY,
                    str_replace( '_', ' ', PROPERTY_MODE_ACCESS )
                )
            );

        if (
            get_class(
                $tokens_stream = self::initialize(
                    $path, false, $this->{PROPERTY_CONTEXT}, $this
                )
            ) === __CLASS__
        )
            $callback_parameters = true;

        return $callback_parameters;
    }

    /**
    * Read a stream
    *
    * @param    integer $count  tokens count to be read
    * @return   nothing
    */
    public function stream_read( $count )
    {
        if ( $count < 0 ) $count = 0;
        $hash_length = self::getHashLength();
        $key = $this->getKey();
        $max_chunk_size = self::getMaxChunkSize();
        $protocol = self::getProtocol();
        $stream = &self::getStream( $key );
        $subsequence = '';

        if ( ! is_null( $stream ) )
        {
            if ( is_string( $sequence = $stream->{PROPERTY_SEQUENCE} ) )
            {
                if ( is_int( $size = $stream->{PROPERTY_SIZE} ) )
                {
                    if ( str_key_arr(
                            $protocol,
                            $contextual_options = $this->getOptions()
                    ) )
                    {
                        $options = $contextual_options[$protocol];
                        $context = &$this->{PROPERTY_CONTEXT};

                        // Forward stream handle
                        $this->setOption(
                            PROPERTY_CONTAINER_REFERENCES,
                            $options[PROPERTY_CONTAINER_REFERENCES],
                            $context
                        );

                        $length = ! isset( $options[PROPERTY_LENGTH] ) ?
                            $size : $options[PROPERTY_LENGTH]
                        ;

                        $offset =
                            is_null( $position = self::getPosition() ) ||
                            ( $position <= 0 ) ?
                            0 : $position / $hash_length
                        ;

                        if ( str_mmb_obj(
                                PROPERTY_OFFSET, ( object ) $options, false
                        ) )
                            $offset = $options[PROPERTY_OFFSET];    

                        $length_bytes = $length * $hash_length;
                        $offset_bytes = $offset * $hash_length;
    
                        if ( $length_bytes > $max_chunk_size )
                            $length_bytes = $max_chunk_size;
                        
                        $new_position = $offset_bytes + $length_bytes;
    
                        if ( $new_position > $size - $hash_length )
                            $new_position = $size - $hash_length;
    
                        self::setPosition( $new_position );

                        $subsequence = substr(
                            $sequence, $offset_bytes, $length_bytes
                        );
                }
                else
                    throw new \Exception( sprintf(
                        EXCEPTION_INVALID_PROPERTY, ENTITY_CONTEXT
                    ) );
                }
                else
                    throw new \Exception( sprintf(
                        EXCEPTION_INVALID_PROPERTY, ENTITY_SIZE
                    ) );
            }       
            else
                throw new \Exception( sprintf(
                    EXCEPTION_INVALID_PROPERTY, ENTITY_SEQUENCE
                ) );
        }
        else
            throw new \Exception( sprintf(
                EXCEPTION_INVALID_ENTITY, ENTITY_STREAM
            ) );

        return $subsequence;
    }

    /**
    * Seek to specific location in an instance of Tokens_Stream
    *
    * @todo     implement manual position adjustment
    * @return   boolean position update indicator
    */
    public function stream_seek( $offset, $whence = SEEK_SET )
    {
    }       

    /**
    * Retrieve the current position of a stream
    *
    * @return   mixed   position
    */
    public function stream_tell()
    {
        $position = self::getPosition();
        self::log( $position, 'position', __FILE__, __LINE__, __METHOD__ );
        return $position;
    }

    /**
    * Write to a stream
    *
    * @return   mixed   position
    */
    public function stream_write( $data )
    {
        $key = $this->getKey();
        $access_mode = $this->getOption( PROPERTY_MODE_ACCESS );
        $bytes_written = strlen( $data );
        $current_position = 0;
        $hash_length = self::getHashLength();
        $protocol = self::getProtocol();
        $stream = &self::getStream( $key );
        $file_path = $stream->{PROPERTY_PATH_FILE};
        $signal = $stream->{PROPERTY_SIGNAL};
        $size = $stream->{PROPERTY_SIZE};
        $properties = self::checkProperties( array(
            PROPERTY_MODE_ACCESS => FILE_ACCESS_MODE_READ_ONLY,
            PROPERTY_PATH => $file_path
        ) );
        $read_tokens_count = self::slen( $properties[PROPERTY_URI_REQUEST] );

        if ( isset( $stream->{PROPERTY_SUBSTREAM} ) )
        
            $bytes = self::renderSignal(
                self::buildSignal( $stream->{PROPERTY_SUBSTREAM} ),
                RENDER_TYPE_SIGNAL,
                true
            );
        else

            $bytes = $signal;

        switch ( $access_mode )
        {
            case FILE_ACCESS_MODE_APPEND_ONLY:
            case FILE_ACCESS_MODE_APPEND:

                $current_position = $read_tokens_count * $hash_length;

            case FILE_ACCESS_MODE_OVERWRITE:
            case FILE_ACCESS_MODE_WRITE_ONLY:
            case FILE_ACCESS_MODE_WRITE:

            $handle = fopen( $file_path, $access_mode );
            $result = fwrite( $handle, $bytes );
            fclose( $handle );

            if ( $result )
            {
                $new_position = $current_position + $bytes_written;
                self::setPosition( $new_position );
            }

                break;

            default:

                throw new \Exception( sprintf( EXCEPTION_INVALID_ENTITY,
                    str_replace( '_', ' ', ENTITY_MODE_ACCESS )
                ) );
        }

        $output = array();
        $result = exec( 'php -l "' . $file_path . '"', $output );

        try {
            if ( 0 !== strpos( $result, 'No syntax errors detected' ) )

                throw new \Exception(
                    EXCEPTION_SOURCE_BUILDER_SYNTAX_ERROR 
                );
        }
        catch ( \Exception $exception )
        {
            if ( INTROSPECTION_VERBOSE )
            {
                echo
                    '<pre>',
                        highlight_string( file_get_contents( $file_path ) ),
                        '<br />', print_r( $output[1], true ),
                    '</pre>'
                ;
                global $class_application, $verbose_mode;
                $class_dumper = $class_application::getDumperClass();
                $class_dumper::log( __METHOD__, array(
                    $exception
                ), true, true );
            }
            else self::log(
                $exception->getMessage(), 'exception',
                __FILE__, __LINE__, __METHOD__
            );
        }

        $success = $result ? strlen( $bytes_written ) : false;

        return $success;
    }

    /**
    * Extract tokens from a signal
    *
    * @return nothing
    */
    public function tokenize()
    {
        $tokens = array();
        
        $check_single_token = function( $token )
        {
            list( , $properties ) = each( $token );
            return is_array( $properties ) &&
            isset( $properties[0] ) &&
            ( $properties[0] === T_INLINE_HTML );
        };

        if ( isset( $this->{PROPERTY_SIGNAL} ) )
        {
            $this->{PROPERTY_SIGNAL} = self::applyTransformations( array(
                PROPERTY_CONTAINER => $this,
                PROPERTY_SUBJECT => $this->{PROPERTY_SIGNAL}
            ) );        
            $tokens = token_get_all( $this->{PROPERTY_SIGNAL} );
        }
    
        if ( count( $tokens ) === 1 )
        {
            if ( $check_single_token( $tokens ) )
            {
                $_signal = '<?php '.$this->{PROPERTY_SIGNAL};
                $_tokens = token_get_all( $_signal );

                if (
                    ( count( $_tokens ) === 1 ) &&
                    $check_single_token( $_tokens )
                )
                    throw new \Exception(
                        sprintf( EXCEPTION_INVALID_ENTITY, ENTITY_SOURCE )
                    );
                else
                {
                    array_shift( $_tokens );                        
                    $tokens = $_tokens;
                }
            }
        }

        return $tokens;
    }

    /**
    * Add substitutions as a new transformation
    *
    * @param    object      $container
    * @param    array       $substitutions
    * @param    resource    $context
    * @return   $container
    */
    public static function addSubstitutions(
        &$container, $substitutions = null, &$context = null
    )
    {
        if ( ! is_null( $context ) && is_resource( $context ) )

            $_substitutions = self::extractOption( array(
                PROPERTY_CONTEXT => $context,
                PROPERTY_NAME => PROPERTY_SUBSTITUTIONS
            ) );

        else if ( is_array( $substitutions ) )
        
            $_substitutions = $substitutions;

        else throw new \Exception( EXCEPTION_INVALID_ARGUMENT );

        if ( ! is_null( $_substitutions ) )
        {
            self::addTransformation( array(
                PROPERTY_CONTAINER => &$container,
                PROPERTY_TRANSFORMATION => array(
                    PROPERTY_METHOD => array(
                        __CLASS__, 'applySubstitutions'
                    ),
                    PROPERTY_ARGUMENTS => array( array(
                        PROPERTY_SUBSTITUTIONS => $_substitutions
                    ) )
                ),
                PROPERTY_REPEATABLE => false
            ) );
        }

        return $container;
    }

    /**
    * Add a transformation 
    *
    * @param    array   $properties
    * @return   container
    */
    public static function addTransformation( $properties )
    {
        $repeatable = true;

        /**
        * Extract properties
        *
        * @tparam   mixed   $container
        * @tparam   boolean $repeatable
        * @tparam   array   $transformation
        */
        extract( $properties, EXTR_REFS );

        if ( is_object( $container ) )
        {
            if ( ! isset( $container->{PROPERTY_TRANSFORMATIONS} ) )
                $container->{PROPERTY_TRANSFORMATIONS} = array();

            $transformations = &$container->{PROPERTY_TRANSFORMATIONS};
        }
        else if ( is_array( $container ) )
        {
            if ( ! isset( $container[PROPERTY_TRANSFORMATIONS] ) )
                $container[PROPERTY_TRANSFORMATIONS] = array();

            $transformations = &$container[PROPERTY_TRANSFORMATIONS];
        }

        if ( ! isset( $transformations[PROPERTY_HASHES] ) )

            $transformations[PROPERTY_HASHES] = array();

        $hash = md5( serialize( $transformation ) );

        if (
            $repeatable ||
            ! isset( $transformations[PROPERTY_HASHES][$hash] )
        )
            $transformations[PROPERTY_STACK][] = array(
                PROPERTY_HASH => $hash,
                PROPERTY_TIMESTAMP => time(),
                PROPERTY_TRANSFORMATION => $transformation
            );

        $transformations[PROPERTY_HASHES][$hash] = $transformation;

        return $container;
    }

    /**
    * Add a transformation 
    *
    * @param    string  $name
    * @param    mixed   $value
    * @param    mixed   $container
    * @return   container
    */
    public static function appendToHistory( $name, $value, &$container )
    {
        if ( is_object( $container ) )
        {
            if ( ! isset( $container->{PROPERTY_HISTORY} ) )
                $container->{PROPERTY_HISTORY} = array();
            $history = &$container->{PROPERTY_HISTORY};
        }
        else if ( is_array( $container ) )
        {
            if ( ! isset( $container[PROPERTY_HISTORY] ) )
                $container[PROPERTY_HISTORY] = array();
            $history = &$container[PROPERTY_HISTORY];
        }

        $hash = md5( serialize( $value ) );
        $_value = array(
            PROPERTY_TIMESTAMP => time(),
            PROPERTY_VALUE => $value,
        );
        $history[$name][$hash] = $_value;

        return $container;
    }

    /**
    * Append tokens to a stream
    *
    * @param    array   $store  properties  store
    * @return   integer written tokens count
    */
    public static function appendToStream( $store )
    {
        $store[PROPERTY_MODE_ACCESS] = FILE_ACCESS_MODE_APPEND_ONLY; 
        return self::writeInStream( $store );
    }

    /**
    * Apply substitutions to a subject
    *
    * @param    array   $properties
    * @return   tokens
    */
    public static function applySubstitutions( $properties )
    {
        $collection_replace =
        $collection_search = array();
        $last_revision = self::getLastRevision();

        /**
        * Extract properties
        *
        * @tparam   $subject
        * @tparam   $substitutions
        */
        extract( $properties );

        foreach( $substitutions as $search => $replace )
        {
            $collection_search[] = $search;
            $collection_replace[] = $replace;
        }

        $collection_search[] = 'day__';
        $collection_replace[] = date( 'd' );

        $collection_search[] = 'month__';
        $collection_replace[] = date( 'm' );

        $collection_search[] = 'revision__';
        $collection_replace[] = $last_revision;

        $_subject = $subject;

        if ( is_array( $subject ) )
        {
            foreach ( $subject as $index => $item )

                if ( is_array( $item ) && isset( $item[1] ) )

                    $_subject[$index][1] = str_replace(
                        $collection_search, $collection_replace,
                        $item[1]
                    );
        }
        else if ( is_string( $subject ) )

            $_subject = str_replace(
                $collection_search, $collection_replace,
                $subject
            );                  


        return $_subject;
    }

    /**
    * Replace placeholders encaspsulated within tokens
    *
    * @param    array   $properties properties
    * @return   tokens
    */
    public static function applyTransformations( $properties )
    {
        /**
        * @tparam   $container
        * @tparam   $subject
        */
        extract( $properties );

        $result = $subject;

        if ( isset( $container->{PROPERTY_TRANSFORMATIONS} ) )
        {
            $transformations = $container->{PROPERTY_TRANSFORMATIONS};
        
            if (
                isset( $transformations[PROPERTY_STACK] ) &&
                is_array( $transformations[PROPERTY_STACK] )
            )
            {
                $stack = $transformations[PROPERTY_STACK];

                foreach ( $stack as $index => $properties )
                {
                    if ( isset( $properties[PROPERTY_TRANSFORMATION] ) )
                    
                        $transformation = $properties[PROPERTY_TRANSFORMATION];

                    if ( $method = self::validMethod( $transformation ) )
                    {
                        $arguments = array();

                        if ( isset( $transformation[PROPERTY_ARGUMENTS] ) )

                            $arguments = $transformation[PROPERTY_ARGUMENTS];

                        if ( $arguments[0] && is_array( $arguments[0] ) )
                        
                            $arguments[0][PROPERTY_SUBJECT] = $subject;

                        $result = call_user_func_array(
                            $method, $arguments 
                        );
                    }
                    else
                        throw new \Exception( sprintf( 
                            EXCEPTION_INVALID_ENTITY, ENTITY_METHOD
                        ) );

                }
            }
        }

        return $result; 
    }

    /**
    * Build a signal from a stream
    *
    * @param    array   $stream     stream
    * @return   signal
    */
    public static function buildSignal( $stream )
    {
        $signal = array();

        if ( is_array( $stream ) )
    
            while ( list( $token_index, $token ) = each( $stream ) )
            {
                if (
                    is_string( $token ) ||
                    ( is_array( $token ) && isset( $token[1] ) )
                )
                    $signal[$token_index] = self::getTokenValue( $token );
                else
                {
                    $tokens = $token;
                    $signal[$token_index] = '';

                    while (
                        list( $_token_index, $_token ) = each( $tokens )
                    )
                        $signal[$token_index] .=
                            self::getTokenValue( $_token )
                        ;
                } // taking care of aggregation results
            }

        return $signal;
    }

    /**
    * Check a context made of various properties
    * Initialize references container when needed
    * Merge existing length and offset and provided matching properties
    * Open a stream before returning a handle and the number of bytes read
    * @see      TOKENS_STREAM :: getHashLength
    * @see      TOKENS_STREAM :: getProtocol
    * @see      TOKENS_STREAM :: openStream
    *
    * @param    array   $properties properties
    * @return   mixed   callback parameters
    */
    public static function checkContext( &$properties )
    {
        $full_read = false;
        $hash_length = self::getHashLength();
        $protocol = self::getProtocol();

        /**
        * Extract properties
        *
        * @tparam   $access_mode    access mode
        * @tparam   $context        contextual options
        * @tparam   $full_read      full read flag
        * @tparam   $hash_length    hash length (optional)
        * @tparam   $length         stream length to be read (optional)
        * @tparam   $max_length     max stream length
        * @tparam   $offset         offset to skip before read
        * @tparam   $path           path leading to stream
        * @tparam   $signal         (optional)
        */
        extract( $properties ); 

        if ( is_null( $context ) || is_resource( $context ) )
        {
            if ( ! isset( $length ) ) $length = $max_length;

            $options = array(
                $protocol => array(
                    PROPERTY_LENGTH => $length,
                    PROPERTY_OFFSET => $offset
                )
            );

            if ( isset( $signal ) )

                $options[$protocol][PROPERTY_SIGNAL] = $signal;

            if ( is_resource( $context) )
            {
                $_options = stream_context_get_options( $context );

                if ( $full_read )
                {
                    $options[$protocol] = array_merge(
                        $_options[$protocol], $options[$protocol]
                    );
                    
                }
                else if (
                    isset( $_options[$protocol][PROPERTY_SIGNAL] ) &&
                    ! isset( $options[$protocol][PROPERTY_SIGNAL] )
                )
                    $options[$protocol][PROPERTY_SIGNAL] =
                        $_options[$protocol][PROPERTY_SIGNAL]
                    ;               
            }

            if ( ! isset( $options[PROPERTY_CONTAINER_REFERENCES] ) )
            {
                $references_container = array();
                $options[PROPERTY_CONTAINER_REFERENCES] = &$references_container;
            }

            $_context = stream_context_create( $options );
            $context = &$_context;
            $properties[PROPERTY_CONTEXT] = &$context;
        }
        else
            throw new \Exception( sprintf(
                EXCEPTION_INVALID_ENTITY, ENTITY_CONTEXT
            ) );

        $callback_parameters = array( 
            PROPERTY_COUNT_BYTES => $length * $hash_length,
            PROPERTY_HANDLE => self::openStream( $path, $access_mode, $context )
        );

        return $callback_parameters;
    }

    /**
    * Make contextual options always available as a resource
    *
    * @param    array   &$properties    properties
    * @param    string  $access_mode    access mode
    * @param    mixed   $protocol       protocol
    * @return   nothing
    */
    public static function checkContextAsReference(
        &$properties, $access_mode = null, $protocol = null
    )
    {
        if ( is_null( $protocol ) )
            $protocol = self::getProtocol();

        if ( is_null( $access_mode ) )
            $access_mode = FILE_ACCESS_MODE_READ_ONLY;

        $options = array(
            $protocol => array( PROPERTY_MODE_ACCESS => $access_mode )
        );

        $context = stream_context_create( $options );

        if ( ! isset( $properties[PROPERTY_CONTEXT] ) )

            $properties[PROPERTY_CONTEXT] = &$context;
    }

    /**
    * Prevent use of host different from "sandbox" (set as default one)
    * and invalid request uri from being queried
    *
    * @param    array   $properties endpoint properties
    * @return   string  endpoint
    */
    public static function checkEndpoint( $properties )
    {
        $root_directory = '';

        /**
        * Extract properties
        *
        * @tparam   $host           host
        * @tparam   $request_uri    request uri
        */
        extract( $properties ); 

        if (
            isset( $host ) &&
            ! is_null( $host ) &&
            ( $host !== APPLICATION_SANDBOX ) &&
            ( strlen( $host ) > 0 )
        )
            throw new \Exception( sprintf(
                EXCEPTION_INVALID_ENTITY, ENTITY_HOST . ' (' . $host .')'
            ) );
        else
            $root_directory = self::getRootDirectory();

        if ( ! isset( $request_uri ) )

            throw new \Exception(
                sprintf(
                    EXCEPTION_INVALID_PROPERTY,
                    str_replace( '_', ' ', PROPERTY_URI_REQUEST )
                )
            );

        return $root_directory . $request_uri;
    }

    /**
    * Check stream length to be read
    *
    * @param    integer $length stream length
    * @return   integer checked length
    */
    public static function checkLength( $length = null )
    {
        if ( is_null( $length ) ) $length = 1;
        else if ( ( $length < 0 ) && ( $length !== -1 ) )

            throw new \Exception(
                sprintf( EXCEPTION_INVALID_PROPERTY, PROPERTY_LENGTH )
            );

        return $length;
    }

    /**
    * Count options to detect possible need for signal retrieval 
    * A special conditional flag "large definition" is made active
    * when a contextual option is available 
    *   either as a "signal" and contains a non empty value
    *   or as a "file path" and matches with an actual script
    *
    * Whenever the "large definition" flag is active,
    * the following contextual options are made available:
    *  context (parent directory location)
    *  warehouse (dummy name hardcoded to "build")
    *  resource (file name)
    *
    * @param    array   $options
    * @return   array   checked options
    * @todo gem type (file extension -> hardcoded to PHP for now)
    */
    public static function checkOptions( $options )
    {
        $conditions = array();
        $large_definition = false;
        $metadata = false;
        $file_path =
        $options_count =
        $signal = null;
        $protocol = self::getProtocol();

        if (
            ! is_null( $options ) &&
            is_array( $options ) &&
            isset( $options[$protocol] ) &&
            is_array( $options[$protocol] ) &&
            ( $protocol_options = $options[$protocol] )
        )
        {
            $large_definition =
                (
                    ( $options_count = count( $protocol_options ) ) &&
                    isset( $protocol_options[PROPERTY_SIGNAL] ) &&
                    ( $signal = $protocol_options[PROPERTY_SIGNAL] ) &&
                    ( strlen( $signal ) > 0 )
                )
            ;

            $large_definition = 
                (
                    isset( $protocol_options[PROPERTY_PATH_FILE] ) &&
                    ( $file_path = trim(
                        $protocol_options[PROPERTY_PATH_FILE]
                    ) ) &&
                    ( strlen( $file_path ) > 0 ) &&
                    file_exists( $file_path )
                ) || $large_definition
            ;               

            if ( isset( $protocol_options[PROPERTY_METADATA] ) )
                $metadata = $protocol_options[PROPERTY_METADATA];
        }

        if ( $large_definition )
        {
            if ( isset( $signal ) )

                self::appendToHistory(
                    PROPERTY_SIGNAL, $signal, $conditions
                );

            if ( is_null( $options_count ) || is_null( $signal ) )

                $signal = file_get_contents( $file_path );

            if ( isset( $protocol_options[PROPERTY_SUBSTITUTIONS] ) )
            {
                $substitutions = $protocol_options[PROPERTY_SUBSTITUTIONS];
                self::addSubstitutions( $conditions, $substitutions );
                self::appendToHistory(
                    PROPERTY_SIGNAL, $signal, $conditions
                );
            }

            $path_sections = explode( CHARACTER_SLASH, $file_path );
            $directory_parent = implode( CHARACTER_SLASH, $path_sections );
            $conditions[PROPERTY_RESOURCE] = array_pop( $path_sections );
            $conditions[CONDITION_CONTEXT] = $directory_parent;
            $conditions[CONDITION_CONTEXT_GEM_TYPE] = EXTENSION_PHP;
            $conditions[CONDITION_WAREHOUSE] = DIR_BUILD;
            $conditions[PROPERTY_PATH_FILE] = $file_path;
            $conditions[PROPERTY_SIGNAL] = $signal;
        }
        else if ( ! is_null( $metadata ) )

            throw new \Exception(
                sprintf( EXCEPTION_INVALID_ENTITY, ENTITY_DEFINITION )
            );

        return $conditions;
    }

    /**
    * Initialize properties when needed and check their consistency
    *   access mode
    *   context
    *   file path
    *   format
    *   length
    *   offset
    *   path
    *   placeholders
    *   signal
    *   size
    *   URI request
    *
    * @see      TOKENS_STREAM :: checkContextAsReference
    * @see      TOKENS_STREAM :: extractOptions
    * @see      TOKENS_STREAM :: getProtocol
    * @see      TOKENS_STREAM :: getRootDirectory
    * @see      TOKENS_STREAM :: initialize
    * @see      TOKENS_STREAM :: slen
    * @param    array   $properties properties
    * @return   array   properties
    */
    public static function checkProperties( $properties )
    {
        global $class_application;
        $class_entity = $class_application::getEntityClass();

        $directory_root = self::getRootDirectory();

        $invalid_path = true;
        $invalid_request_uri = true;

        $path =
        $request_uri = null;

        $protocol = self::getProtocol();

        if (
            ! isset( $properties[PROPERTY_MODE_ACCESS] ) ||
            ! ( $access_mode = $properties[PROPERTY_MODE_ACCESS] )
        )
            throw new \Exception(
                sprintf(
                    EXCEPTION_INVALID_PROPERTY,
                    str_replace( '_', ' ', PROPERTY_MODE_ACCESS )
                )
            );

        if (
            isset( $properties[PROPERTY_PATH] ) &&
            ( $path = trim( $properties[PROPERTY_PATH] ) ) &&
            ( strlen( $path ) > 0 )
        )
            $invalid_path = false;

        if (
            isset( $properties[PROPERTY_URI_REQUEST] ) &&
            ( $request_uri = trim( $properties[PROPERTY_URI_REQUEST] ) ) &&
            ( strlen( $request_uri ) > 0 )
        )
            $invalid_request_uri = false;

        if ( $invalid_path && $invalid_request_uri )
        
            throw new \Exception(
                sprintf( EXCEPTION_INVALID_PROPERTY, PROPERTY_ENDPOINT )
            );

        else if (
            $invalid_path || ! isset( $properties[PROPERTY_URI_REQUEST] )
        )
        {
            $base_url = self::getProtocol() . '://' . self::getHost();

            if ( isset( $properties[PROPERTY_URI_REQUEST] ) )
            {
                $path =
                    $base_url .
                    $properties[PROPERTY_URI_REQUEST]
                ;
                $properties[PROPERTY_PATH] = $path;
            }
            else if (
                ! isset( $properties[PROPERTY_URI_REQUEST] ) &&
                ! $invalid_path &&
                ( false === strpos( $path, $directory_root ) )
            )
                $properties[PROPERTY_URI_REQUEST] = substr(
                    $path, strlen( $base_url )
                ) ;

            else if (
                ! isset( $properties[PROPERTY_URI_REQUEST] ) && 
                ( 0 === strpos( $path, $directory_root ) )
            )
            {
                $properties[PROPERTY_URI_REQUEST] =
                    substr( $path, strlen( $directory_root ) )
                ;
                $properties[PROPERTY_PATH] =
                    $base_url . $properties[PROPERTY_URI_REQUEST]
                ;
                $properties[PROPERTY_PATH_FILE] = $path;
                $path = $properties[PROPERTY_URI_REQUEST];
            }

            if ( ! isset( $properties[PROPERTY_PATH_FILE] ) )

                $properties[PROPERTY_PATH_FILE] =
                    $directory_root .
                    $properties[PROPERTY_URI_REQUEST]
                ;
        }

        if (
            in_array( $access_mode, array(
                FILE_ACCESS_MODE_APPEND,
                FILE_ACCESS_MODE_APPEND_ONLY,
                FILE_ACCESS_MODE_OVERWRITE,
                FILE_ACCESS_MODE_WRITE_ONLY
            ) )
        ) // these accessing modes induce the creation of non existing files 
        {
            if ( isset( $properties[PROPERTY_SIGNAL] ) )
            
                $signal = $properties[PROPERTY_SIGNAL];
            else

                throw new \Exception( sprintf(
                    EXCEPTION_INVALID_ENTITY, ENTITY_SIGNAL
                ) );

            $options = array(
                $protocol => array(
                    PROPERTY_MODE_ACCESS => $access_mode,
                    PROPERTY_SIGNAL => $signal
                )
            );

            if (
                isset( $properties[PROPERTY_SUBSTITUTIONS] ) &&
                ! isset( $options[$protocol][PROPERTY_SUBSTITUTIONS] )
            )
                $options[$protocol][PROPERTY_SUBSTITUTIONS] =
                    $properties[PROPERTY_SUBSTITUTIONS]
                ;

            if ( isset( $properties[PROPERTY_CONTEXT] ) )
            {
                $_options = self::extractOptions(
                    $properties[PROPERTY_CONTEXT]
                );

                $options[$protocol] = array_merge(
                    $_options[$protocol], $options[$protocol]
                );
            }

            $context = stream_context_create( $options );

            $tokens_stream = self::initialize(
                $path, null, $context
            );

            $file_path = $tokens_stream->{PROPERTY_PATH_FILE};

            $properties[PROPERTY_CONTEXT] = &$context;
            $properties[PROPERTY_PATH_FILE] = $file_path;

            if ( $invalid_request_uri )

                $properties[PROPERTY_URI_REQUEST] =
                    substr(
                        $properties[PROPERTY_PATH_FILE],
                       strlen( self::getRootDirectory() )
                    )
                ;

            $handle = fopen( $file_path, $access_mode );
            fclose( $handle ); 
        }
        else if (
            in_array( $access_mode, array( FILE_ACCESS_MODE_READ_ONLY ) )
        )
        {   
            if (
                ! isset( $properties[PROPERTY_SIGNAL] ) &&
                isset( $properties[PROPERTY_PATH_FILE] ) &&
                str_valid( $file_path = $properties[PROPERTY_PATH_FILE] )
            )
            {
                if ( file_exists( $file_path ) )

                    $properties[PROPERTY_SIGNAL] = file_get_contents(
                        $file_path
                    );
                else
                    throw new \Exception(
                        EXCEPTION_MISSING_RESOURCE . ' (' . $file_path . ')'
                    );
            }

            self::checkContextAsReference( $properties );
        }

        $properties[PROPERTY_SIZE] = self::slen( $path );

        switch ( $access_mode )
        {
            case FILE_ACCESS_MODE_APPEND:
            case FILE_ACCESS_MODE_READ_ONLY:
            case FILE_ACCESS_MODE_WRITE:
    
                if ( ! isset( $properties[PROPERTY_FORMAT] ) )
    
                    $properties[PROPERTY_FORMAT] =
                        $class_entity::getDefaultType( null, ENTITY_FORMAT )
                            ->{PROPERTY_VALUE}
                    ; // xhtml is the default format type

                // Default length and offset properties definition
                // aim to handle the whole signal
                if ( ! isset( $properties[PROPERTY_LENGTH] ) )

                    $properties[PROPERTY_LENGTH] = -1;

                if ( ! isset( $properties[PROPERTY_OFFSET] ) )

                    $properties[PROPERTY_OFFSET] = 0;

                    break;
        }
        
        switch ( $access_mode )
        {
            case FILE_ACCESS_MODE_APPEND_ONLY:
            case FILE_ACCESS_MODE_APPEND:
            case FILE_ACCESS_MODE_OVERWRITE:
            case FILE_ACCESS_MODE_READ_ONLY:
            case FILE_ACCESS_MODE_WRITE_ONLY:
            case FILE_ACCESS_MODE_WRITE:

                if (
                    ! in_array(
                        $access_mode,
                        // following modes could be used for reading only
                        // no error should be raised when using them
                        // when no contents is provided
                        array( FILE_ACCESS_MODE_APPEND, FILE_ACCESS_MODE_WRITE )
                    ) && ! isset( $properties[PROPERTY_SIGNAL] )
                )
                    throw new \Exception( sprintf(
                        EXCEPTION_INVALID_PROPERTY, PROPERTY_SIGNAL
                    ) );

                else if ( ! isset( $properties[PROPERTY_SIGNAL] ) )

                    $properties[PROPERTY_SIGNAL] = '';

                    break;
        } // checking signal consistency

        return $properties;
    }

    /**
    * Close a stream by toggling the serializable contextual property
    *
    * @param    mixed   &$context
    * @return   nothing
    */
    public static function closeStream( &$context = null )
    {
        self::setOption( PROPERTY_SERIALIZABLE, true, $context );
    }

    /**
    * Declare persistency coordinates
    *
    * @param    array   $coordinates
    * @param    string  $entity
    * @return   mixed   option
    */
    public static function declarePersistencyCoordinates(
        $coordinates, $entity
    )
    {
        global $class_application, $verbose_mode;
        $class_dumper = $class_application::getDumperClass();

        $table_prefix = substr( PREFIX_TABLE, 0, -1 );
        $table_prefix_prefix = PREFIX_PREFIX . substr( PREFIX_TABLE, 0, -1 );
        $column_prefix =
            PREFIX_PREFIX . PREFIX_TABLE . substr( PREFIX_COLUMN, 0, -1 )
        ;

        $coordinates_mapping = array(
            $column_prefix => FILE_NAME_GLOBALS_SEFI,
            $table_prefix => FILE_NAME_GLOBALS_SEFI,
            $table_prefix_prefix => FILE_NAME_GLOBALS_SEFI
        );

        foreach ( $coordinates as $coordinate_name => $coordinate_value )
        {
            if ( ! isset( $coordinates_mapping[$coordinate_name] ) )
            
                throw new Exception( EXCEPTION_INVALID_ENTITY, ENTITY_MAPPING );
            else
            {
                $resource_properties = self::importPersistencyDeclarations( array(
                    PROPERTY_DESTINATION => self::getRootDirectory(),
                    PROPERTY_FILES_NAMES => $coordinates_mapping[$coordinate_name]
                ) );

                if ( count( $resource_properties ) && is_array( $resource_properties ) )
            
                    foreach ( $resource_properties as $resource_name => $resource_value )
                    {
                        $root_directory = self::getRootDirectory();
                        $properties = array( PROPERTY_PATH =>
                            PROTOCOL_TOKEN . '://' . self::getHost() . substr(
                                $resource_value[PROPERTY_PATH],
                                strlen( $root_directory )
                            )
                        );
                        $properties[PROPERTY_LENGTH] = -1;
                        $properties[PROPERTY_OFFSET] = 0;
                        $properties[PROPERTY_SIGNAL] = $resource_value[PROPERTY_CONTENT];
                        $substream = self::getSubstream(
                            $properties[PROPERTY_PATH], FILE_ACCESS_MODE_READ_ONLY,
                            $properties[PROPERTY_LENGTH], $properties[PROPERTY_OFFSET]
                        );

                        $find_in_stream = function ( $properties )
                        {
                            $results = array(
                                PROPERTY_OCCURRENCES => array(),
                                PROPERTY_OCCURRENCE_LAST => null
                            );
    
                            /**
                            * Extract properties
                            *
                            * @tparam   mixed   $subject
                            * @tparam   string  $type
                            * @tparam   mixed   $pattern
                            */
                            extract( $properties );
    
                            if ( is_array( $subject ) && count( $subject ) )
    
                                foreach ( $subject as $index => $item )
                                {
                                    $_subject = self::getTokenValue( $item );
                                    $valid_string = ( strlen( trim( $_subject ) ) > 0 )
                                        && ( false !== strpos( $_subject, $pattern . '_' ) )
                                    ;
                                        
                                    if ( $valid_string )
                                    {
                                        $results[PROPERTY_OCCURRENCES][] = $index;
                                        $results[PROPERTY_OCCURRENCE_LAST] = $index;
                                    }
                                }
                        };
    
                        $find_in_stream( array(
                            PROPERTY_SUBJECT => $substream,
                            PROPERTY_TYPE => SEARCH_TYPE_FULL_TEXT,
                            PROPERTY_PATTERN => strtoupper( $coordinate_name )
                        ) );
    
                        global $class_application, $verbose_mode;
                        $class_dumper = $class_application::getDumperClass();
                        $class_dumper::log( __METHOD__, array(
                            $substream
                        ), true, true );
    
                        //$root_directory = self::getRootDirectory();
                        //$properties = array( PROPERTY_PATH =>
                        //  PROTOCOL_TOKEN . '://' . self::getHost() . substr(
                        //      $resource_property[PROPERTY_PATH],
                        //      strlen( $root_directory )
                        //  )
                        //);
                        //$properties[PROPERTY_LENGTH] = 8;
                        //$properties[PROPERTY_OFFSET] = 26;
                        //$properties[PROPERTY_SIGNAL] = $resource_property[PROPERTY_CONTENT];
                        //
                        //# reading first token hash
                        //
                        //$tokens_count = $class_tokens_stream::appendToStream( $properties );
                        //global $class_application, $verbose_mode;
                        //$class_dumper = $class_application::getDumperClass();
                        //$class_dumper::log( __METHOD__, array(
                        //  $coordinates->$prefix_table_column
                        //), TRUE, TRUE );
                    }

            }
        }
    }

    /**
    * Extract an option from a request
    *
    * @param    array   $request
    * @return   mixed   option
    */
    public static function extractOption( $request )
    {
        $option = null;
        $options = new \stdClass();
        $protocol = self::getProtocol();
    
        if ( str_key_arr( PROPERTY_CONTEXT, $request ) )
            $options = self::extractOptions( $request[PROPERTY_CONTEXT] );

        if (
            str_key_arr( PROPERTY_NAME, $request ) &&
            str_key_arr( $protocol, $options ) &&
            str_key_arr( $request[PROPERTY_NAME], $options[$protocol] )
        )
            $option = $options[$protocol][$request[PROPERTY_NAME]];
        
        return $option;
    }

    /**
    * Translate PHP array into a resource containing contextual options
    * and returns it
    * If a classic file handle is discovered as persisting in memory
    * (as a static property of the TOKENS_STREAM class),
    * the file handle is copied to the resource
    * If an array is provided as store parameter,
    * its metadata and "endpoint" properties are checked
    * in order to update the returned result
    * the store parameter may contain a previously built resource
    * which is to be preserved by calling native PHP function array_merge
    * @see      TOKENS_STREAM :: checkEndpoint
    * @see      TOKENS_STREAM :: checkOptions
    *
    * @param    mixed   $store  store
    * @return   mixed   options checked after extraction, merged if needed
    * 
    * @todo check alternate merge options
    */
    public static function extractOptions( $store = null )
    {
        $options = array();
        $protocol = self::getProtocol();

        if ( is_resource( $store ) )
        {
            $options = stream_context_get_options( $store );
            
            if (
                isset( self::$persistent_context ) &&
                ! is_null( self::$persistent_context ) &&
                is_resource( self::$persistent_context )
            )
            {
                $_options = stream_context_get_options(
                    self::$persistent_context
                );

                if (
                    isset( $_options[$protocol] ) &&
                    ( $protocol_options = $_options[$protocol] ) &&
                    isset( $protocol_options[PROPERTY_CONTAINER_REFERENCES] )
                )
                    $options[$protocol][PROPERTY_CONTAINER_REFERENCES] = 
                        $protocol_options[PROPERTY_CONTAINER_REFERENCES]
                    ;
            }
        }

        if (
            ! is_null( $store ) && is_array( $store ) &&
            ( count( $store ) > 0 )
        )
        {
            $metadata = true;

            $file_path = self::checkEndpoint( $store );

            if ( isset( $store[PROPERTY_METADATA] ) )
                $metadata = $store[PROPERTY_METADATA];

            if ( count( $options ) )

                list( $protocol ) = each( $options );

            $options[$protocol][PROPERTY_PATH_FILE] = $file_path;
            $options[$protocol][PROPERTY_METADATA] = $metadata;

            if ( isset( $store[PROPERTY_SIGNAL] ) )
            {
                $signal = $store[PROPERTY_SIGNAL];
                $options[$protocol][PROPERTY_SIGNAL] = $signal;
            }

            // forward pre-existing context
            if ( ! isset( $store[PROPERTY_CONTEXT] ) )

                $store[PROPERTY_CONTEXT] = null;
            
            else if ( is_resource( $store[PROPERTY_CONTEXT] ) )
            {
                $_options = stream_context_get_options(
                    $store[PROPERTY_CONTEXT]
                );
            
                if ( str_key_arr( $protocol, $_options ) )
                    $options[$protocol] = array_merge(
                        $_options[$protocol], $options[$protocol]
                    );
            }

            $options = self::checkOptions( $options );
        }

        return $options;
    }

    /**
    * Extract a substream
    *
    * @param    array   $definition definition
    * @return   array   excerpt
    */
    public static function extractSubstream( $definition )
    {
        /**
        * Extract variables
        *
        * @tparam   $length         bytes count to read
        * @tparam   $offset         skip bytes before read 
        * @tparam   $old_position   before handling to come
        * @tparam   $sequence       sequence of stream sections
        */
        extract( $definition );

        $hash_length = self::getHashLength();
        $shift = 0;
        $stream = self::getStream();
        $stream->{PROPERTY_SUBSTREAM} = array();

        if ( is_null( $offset ) )
        {
            $shift = $old_position / $hash_length;
            $offset = 0;
        }

        $count_max = $stream->{PROPERTY_SIZE} / $hash_length;
        $hash_map = $stream->{PROPERTY_HASH_MAP};

        if ( ( $length = self::checkLength( $length ) ) === -1 )
            $length = $count_max;

        $first_index = $offset;
        $last_index = min( $first_index + $length - 1, $count_max - 1 );

        $hash_table = str_split( $sequence, $hash_length );
        $length_max = self::getMaxChunkSize() / $hash_length;

        for ( $index = $last_index ; $index >= $first_index ; $index-- )
        {
            $hash_index = $last_index - $index;
            $index_excerpt = $hash_index + $first_index + $shift;

            if ( isset( $hash_table[$hash_index] ) )

                $stream->{PROPERTY_SUBSTREAM}[$index_excerpt] =
                    $hash_map[$hash_table[$hash_index]][PROPERTY_TOKEN]
                ;
            else
                throw new \Exception(
                    sprintf( EXCEPTION_INVALID_ENTITY, ENTITY_HASH )
                );
        }

        return $stream->{PROPERTY_SUBSTREAM};
    }

    /**
    * Extract tokens
    *
    * @param    mixed   $substream  substream
    * @return   array   tokens
    */      
    public static function extractTokens( $substream = null )
    {
        $tokens = array();

        if ( ! is_array( $substream ) )
            throw new \Exception(
                EXCEPTION_INVALID_ARGUMENT . ' (' . PROPERTY_SUBSTREAM . ')'
            );

        while ( list( $token_index, $token ) = each( $substream ) )

            $tokens[$token_index] = self::getTokenValue( $token, 0 );

        return $tokens;                 
    }

    /**
    * Feed a river with a stream (TOKENS_STREAM instantiation)
    * River which is represented by an entry point inheriting 
    * the sequence property from the stream passed as a parameter
    * A default position property and the stream itself are attached
    * to the entry point
    * @see      TOKENS_STREAM->getEntryPoint
    *
    * @param    object  $stream         stream
    * @param    mixed   $entry_point    entry point
    * @return   nothing
    */
    public static function feedRiver( $stream = null, $entry_point = null )
    {
        if ( ! is_null( $stream ) )
        {
            if ( is_null( $entry_point ) )
                $entry_point = &$stream->getEntryPoint();

            if  ( ! isset( $entry_point[PROPERTY_STREAM] ) )
            {
                if ( isset( $stream->{PROPERTY_SEQUENCE} ) )
                    $entry_point[PROPERTY_SEQUENCE] =
                        $stream->{PROPERTY_SEQUENCE}
                    ;
                else
                    throw new \Exception( sprintf(
                        EXCEPTION_INVALID_ENTITY, ' ' . ENTITY_CONTENT
                    ) );

                $entry_point[PROPERTY_POSITION] = null;
                $entry_point[PROPERTY_STREAM] = $stream;
            }
            else
                throw new \Exception(
                    sprintf( EXCEPTION_EXISTING_ENTITY, ' ' . ENTITY_STREAM )
                ) ;
        }
    }

    /**
    * Get coordinates
    * 
    * @param    string  $path   path
    * @return   array   containing current host and request URI
    */
    public static function getCoordinates( $path )
    {
        if ( ! str_valid( $path ) )

            throw new \Exception( sprintf(
                EXCEPTION_INVALID_PROPERTY, PROPERTY_PATH
            ) );

        $url = parse_url( $path );
        
        if ( isset( $url[PROPERTY_PATH] ) )

            $request_uri = $url[PROPERTY_PATH];
        else

            throw new \Exception(
                sprintf( EXCEPTION_INVALID_ENTITY, ENTITY_PATH )
            );

        $host = isset( $url[PROPERTY_HOST] )
            ? $url[PROPERTY_HOST] : APPLICATION_SANDBOX
        ;

        return array( $request_uri, $host );
    }

    /**
    * Get a handle
    *
    * @param    mixed   $properties
    * @return   resource handle
    */
    public static function getHandle( $properties )
    {
        $handle = self::openStream(
            $properties[PROPERTY_PATH], $properties[PROPERTY_MODE_ACCESS],
            $properties[PROPERTY_CONTEXT]
        );
        return $handle;
    }

    /**
     * Gets the hash length
     *
     * @return integer   hash length
     * @throws \Exception
     */
    public static function getHashLength()
    {
        if ( defined( 'HASH_LENGTH_MD5' ) )

            $hash_length = HASH_LENGTH_MD5;
        else
            throw new \Exception(
                sprintf( EXCEPTION_INVALID_PROPERTY, PROPERTY_LENGTH_HASH )
            );
            
        return $hash_length;
    }

    /**
    * Get application host (sandbox by default)
    *
    * return    string  host name   representing a repository 
    */
    public static function getHost()
    {
        return APPLICATION_SANDBOX;
    }

    /**
    * Get last revision committed to version control system
    *
    * return    integer revision
    */
    public static function getLastRevision()
    {
        global $class_application;
        $class_source = $class_application::getSourceClass();
        return call_user_func( array( $class_source, __FUNCTION__ ) );
    }

    /**
    * Get the maximum chunk size
    * 
    * @return integer   max chunk size
    */
    public static function getMaxChunkSize()
    {
        if ( defined( 'SIZE_MAX_CHUNK_BUFFER' ) )

            $max_chunk_size = SIZE_MAX_CHUNK_BUFFER;
        else
            throw new \Exception(
                sprintf( EXCEPTION_INVALID_PROPERTY, PROPERTY_MAX_CHUNK_SIZE )
            );

        return $max_chunk_size;
    }

    /**
    * Get a persistent handle
    *
    * @param    resource    $store
    * @return   resource    handle
    */
    public static function getPersistentHandle( $store )
    {
        $handle = null;
        $persistent_options = self::extractOptions( $store );
        $protocol = self::getProtocol();

        if (
            isset( $persistent_options[$protocol] ) 
            && isset( $persistent_options[$protocol][PROPERTY_CONTAINER_REFERENCES] )
            && ( $references =
                &$persistent_options[$protocol][PROPERTY_CONTAINER_REFERENCES]
            ) && isset( $references[PROPERTY_HANDLE] )
        )
            $handle = $references[PROPERTY_HANDLE];
        
        return $handle;
    }

    /**
    * Get the current position of a stream
    *
    * @return   nothing
    */
    public static function getPosition()
    {
        return self::$position;
    }

    /**
     * Gets the default protocol (represented by a 3-characters long string)
     *
     * @return   string  protocol used to handle a stream
     * @throws \Exception
     */
    public static function getProtocol()
    {
        $protocol_symbol = 'PROTOCOL_TOKEN';
        
        if ( defined( $protocol_symbol ) )

            $protocol = constant( $protocol_symbol );
        else
            throw new \Exception(
                sprintf( EXCEPTION_INVALID_ENTITY, ENTITY_PROTOCOL )
            );

        return $protocol;
    }
    /**
    * Initialize an array as a static property of the TOKENS_STREAM class
    * to become a container called "river"
    * 
    *
    * @return   array   river
    */
    public static function &getRiver()
    {
        if ( ! isset( self::$river ) ) self::$river = array();

        return self::$river;
    }

    /**
    * Get the root directory, the physical location of a repository
    * to which scripts to be analysed can be added to (sandbox by default),
    * Read and write operations will take place from this repository
    * 
    * @return   string
    */
    public static function getRootDirectory()
    {
        $directory_symbol = 'DIR_SANDBOX';

        if ( ! defined( $directory_symbol ) )

            throw new \Exception(
                EXCEPTION_INCOMPLETE_SERVICE_CONFIGURATION
            );

        $root_directory =
            realpath( __DIR__ . '/../../' . DIR_API . '/' . DIR_WTW .
                constant( $directory_symbol ) ) 
        ;

        if ( ! file_exists( $root_directory ) )
        {
            error_log(sprintf('[meta-programming] attempt to create sandbox (%s)', $root_directory ) );
            mkdir( $root_directory, 0770, true );
        }
        else if (
            file_exists( $root_directory ) &&
            ! is_dir( $root_directory )
        )
            throw new \Exception( sprintf(
                EXCEPTION_INVALID_ENTITY,
                str_replace( '_', ' ', ENTITY_DIRECTORY_ROOT )
            ) );

        return $root_directory;
    }

    /**
    * Get a signal
    *
    * @param    string      $path       path
    * @param    string      $mode       accessing mode
    * @param    integer     $count      number of items to be retrieved
    * @param    integer     $start      offset
    * @param    resource    $context    stream contextual options
    * @return   array       signal
    */
    public static function getSignal(
        $path,
        $mode,
        $count = null,
        $start = null,
        $context = null
    )
    {
        return self::buildSignal(
            self::getSubstream( $path, $mode, $count, $start, $context )
        );
    }

    /**
     * Gets a stream
     *
     * @param null $key access key providing entry point
     * @return mixed stream
     * @throws \Exception
     */
    public static function &getStream( $key = null )
    {
        $river = &self::getRiver();
        $stream = null;

        if ( ! is_null( $key ) )
        {
            if (
                isset( $river[$key] ) &&
                isset( $river[$key][PROPERTY_STREAM] )
            )
                $stream = &$river[$key][PROPERTY_STREAM];
            else
                throw new \Exception(
                    sprintf( EXCEPTION_INVALID_ENTITY, ENTITY_STREAM )
                );
        }
        else
        {
            if ( count( $river ) > 0 )

                $properties = &$river[count( $river ) - 1];
            else
                throw new \Exception(
                    sprintf( EXCEPTION_INVALID_ENTITY, ENTITY_RIVER )
                );

                if (
                    is_array( $properties ) &&
                    isset( $properties[PROPERTY_STREAM] )
                )
                    $stream = &$properties[PROPERTY_STREAM];
            }

        return $stream;
    }

//  public static function &getStream( $key = NULL )
//  {
//      $river = &self::getRiver();
//      $stream = NULL;
//
//      if ( ! is_null( $key ) )
//      {
//          if (
//              isset( $river[$key] ) &&
//              isset( $river[$key][PROPERTY_STREAM] )
//          )
//              $stream = &$river[$key][PROPERTY_STREAM];
//          else
//              throw new \Exception(
//                  sprintf( EXCEPTION_INVALID_ENTITY, ENTITY_STREAM )
//              );
//      }
//      else
//      {
//          if ( count( $river ) > 0 )
//          {
//              $properties = &$river[count( $river ) - 1];
//
//              if (
//                  is_array( $properties ) &&
//                  isset( $properties[PROPERTY_STREAM] )
//              )
//                  $stream = &$properties[PROPERTY_STREAM];
//          }
//      }
//
//      return $stream;
//  }

    /**
     * Gets a subsequence
     *
     * @param      $streamProperties
     * @param null $context
     *
     * @return string
     */
    public static function getSubsequence(array $streamProperties, &$context = null)
    {
        $path = null;
        $count = null;
        $start = null;
        $mode = null;

        if (isset($streamProperties[PROPERTY_LENGTH])) {
            $count = $streamProperties[PROPERTY_LENGTH];
        }

        if (isset($streamProperties[PROPERTY_MODE_ACCESS])) {
            $mode = $streamProperties[PROPERTY_MODE_ACCESS];
        }

        if (isset($streamProperties[PROPERTY_PATH])) {
            $path = $streamProperties[PROPERTY_PATH];
        }

        if (isset($streamProperties[PROPERTY_OFFSET])) {
            $start = $streamProperties[PROPERTY_OFFSET];
        }

        // enable full read
        // when reading length has been set to "magic" value -1
        if ( ( $length = self::checkLength( $count ) ) === -1 ) {
            $full_read = true;
        } else {
            $full_read = false;
        }

        $max_length = self::getMaxChunkSize() / self::getHashLength();
        $options = self::extractOptions( $context );
        $protocol = self::getProtocol();
        $stream_length = self::slen( $path, $context );

        if ( isset( $options[$protocol][PROPERTY_SIGNAL] ) )
        {
            $stream = self::getStream();
            $stream->setStreamSubsequence($count, $start);
            $subsequence = $stream->{PROPERTY_SUBSEQUENCE};
        }
        else
        {
            self::registerStreamWrapper();

            unset($streamProperties[PROPERTY_LENGTH]);
            $properties = array_merge(
                array(PROPERTY_CONTEXT => $context),
                $streamProperties
            );

            self::checkContextAsReference( $properties, $mode );

            $properties[PROPERTY_LENGTH_MAX] = $max_length;

            if ( $full_read )
            {
                $start = 0;
                $length = $stream_length;
            }
            else if ( $start > $stream_length )
            {       
                $start = $stream_length;
                $length = 0;
            }

            if ($length < $max_length) // max = 8192 / hash length
            {
                $exceeded_max_length = false;
                $limit = $start;
                $properties[PROPERTY_LENGTH] = $length;

                if ( $length + $start > $stream_length ) {
                    $properties[PROPERTY_LENGTH] = $stream_length - $start;
                }
            }
            else 
            {
                $exceeded_max_length = true;
                $full_loops = ( int ) round( $length / $max_length );
                $last_length = ( $length % $max_length );
                $limit = $start + $length;

                if ( $start + $length > $stream_length ) {
                    $limit = $stream_length;
                }
            }

            $loop_index = 0;
            $subsequence = '';

            while ( $start <= $limit )
            {
                $properties[PROPERTY_OFFSET] = $start;
                
                if ($exceeded_max_length && ( $loop_index === $full_loops ))
                    $properties[PROPERTY_LENGTH] = $last_length;

                $subsequence .= self::getStreamSection( $properties );

                $start += $max_length;
                $loop_index++;
            }
        }

        return $subsequence;
    }

    /**
    * Get a substream
    *
    * @param    string      $path       path
    * @param    string      $mode       accessing mode
    * @param    integer     $count      number of items to be retrieved
    * @param    integer     $start      offset
    * @param    resource    &$context   stream contextual options
    * @return   array       substream
    */
    public static function getSubstream(
        $path = null,
        $mode = null,
        $count = null,
        $start = null,
        &$context = null
    )
    {
        $old_position = self::getPosition();

        // enable full read
        // when reading length has been set to "magic" value -1
        if ( ( $length = self::checkLength( $count ) ) === -1 ) {
            $full_read = true;
        } else {
            $full_read = false;
        }

        $streamProperties = array(
            PROPERTY_LENGTH => $count,
            PROPERTY_MODE_ACCESS => $mode,
            PROPERTY_OFFSET => $start,
            PROPERTY_PATH => $path,
            PROPERTY_READ_FULL => $full_read
        );
        $subsequence = self::getSubsequence($streamProperties, $context);

        $definition = array(
            PROPERTY_LENGTH => $count,
            PROPERTY_OFFSET => $start,
            PROPERTY_POSITION_OLD => $old_position,
            PROPERTY_SEQUENCE => $subsequence
        );
        $substream = self::extractSubstream( $definition );
        return $substream;
    }

    /**
    * Get a stream section
    *
    * @param    array   $properties properties
    * @return   string  section
    */
    public static function getStreamSection( $properties )
    {
        $section = '';
        
        /**
        * Extract properties
        *
        * @tparam   $bytes_count    bytes count
        * @tparam   $handle         handle
        */
        extract( self::checkContext( $properties ) );
        
        if ( $bytes_count > 0 )

            $section .= fread( $handle, $bytes_count );

        return $section;
    }

    /**
    * Get tokens
    *
    * @param    string      $path       path
    * @param    string      $mode       accessing mode
    * @param    integer     $count      number of items to be retrieved
    * @param    integer     $start      offset
    * @param    resource    &$context   stream contextual options
    * @return   string      $content    content
    */
    public static function getToken(
        $path = null,
        $mode = null,
        $count = null,
        $start = null,
        &$context = null
    )
    {
        $substream = self::getSubstream(
            $path, $mode, $count, $start, $context
        );

        return self::extractTokens( $substream );
    }

    /**
    * Get the value of a token
    *
    * @param    mixed   $token
    * @param    integer $property_index
    * @result   string  value
    */
    public static function getTokenValue( $token, $property_index = 1 )
    {
        if ( is_array( $token ) )
        {
            $token_value = $token[$property_index];

            if ( is_long( $token_value ) )

                $result = token_name( $token_value );
            else 
                $result = $token_value;
        }
        else if ( is_string( $token ) )

            $result = $token;               

        return $result;
    }

    /**
    * Import persistency declarations
    *
    * @see      FILE_MANAGER :: importPersistencyDeclarations
    */
    public static function importPersistencyDeclarations()
    {
        global $class_application;
        $arguments = func_get_args();
        $class_file_manager = $class_application::getFileManagerClass();
        return call_user_func_array(
            array( $class_file_manager, __FUNCTION__ ), $arguments
        );
    }

    /**
    * Retrieve "coordinates" (host and request URI)
    * @see      TOKENS_STREAM :: getCoordinates
    * 
    * Extract contextual options (as an array typecasted as an array)
    * from a resource if any is available
    * @see      TOKENS_STREAM :: extractOptions
    *
    * Spawn an instance of TOKENS_STREAM for non-null metadata
    * @see      TOKENS_STREAM :: spawn
    *
    * Populate the file path property of the TOKENS_STREAM to be returned
    *
    * Apply an optional key binding eventually
    * @see      TOKENS_STREAM->getEntryPoint
    * @see      TOKENS_STREAM->getKey
    * 
    * @param    string      $path       path leading to resource
    * @param    boolean     $metadata   TRUE if only metadata (like length)
    *                                   should be retrieved
    *                                   FALSE otherwise
    * @param    resource    &$context   stream contextual options
    * @param    mixed       $binding    binding (optional)
    * @return   nothing
    */
    public static function initialize(
        $path,
        $metadata = false,
        &$context = null,
        $binding = null
    )
    {
        $tokens_stream = new \stdClass();

        list( $request_uri, $host ) = self::getCoordinates( $path );

        $store = array( 
            PROPERTY_CONTEXT => $context,
            PROPERTY_HOST => $host,
            PROPERTY_METADATA => $metadata,
            PROPERTY_URI_REQUEST => $request_uri
        );

        $conditions = ( object ) self::extractOptions( $store );

        if ( ! is_null( $metadata ) )

            $tokens_stream = self::spawn(
                $conditions, $metadata, $context
            );
        else
        {
            // a target file exists and might need to be truncated
            if ( str_mmb_obj( PROPERTY_PATH_FILE, $conditions ) )

                $path = $conditions->{PROPERTY_PATH_FILE};

            self::addSubstitutions( $tokens_stream, null, $context );

            $tokens_stream->{PROPERTY_PATH_FILE} = $path;
        }

        if (
            is_object( $tokens_stream ) &&
            isset( $tokens_stream->{PROPERTY_KEY} ) &&
            ! is_null( $binding ) && is_object( $binding )
        ) // The access key of a TOKENS_STREAM object
        // (used to access its entry point in the "river" container)
        // is copied to the binding object
            $binding->{PROPERTY_KEY} = $tokens_stream->{PROPERTY_KEY};

        return $tokens_stream;
    }

    /**
    * Inject tokens into a stream
    *
    * @param    array   $store  properties  store
    * @return   integer written tokens count
    */
    public static function injectIntoStream( $store )
    {
        if (
            ! isset( $store[PROPERTY_MODE_ACCESS] ) &&
            ! isset( $store[PROPERTY_OFFSET] )
        )
            $result_injection = self::appendToStream( $store );

        else if ( isset( $store[PROPERTY_OFFSET] ) )
        {
            self::registerStreamWrapper();

            $offset = $store[PROPERTY_OFFSET];
            $signal = $store[PROPERTY_SIGNAL];
            unset( $store[PROPERTY_OFFSET] );
            unset( $store[PROPERTY_SIGNAL] );

            $store[PROPERTY_OFFSET] = 0;
            if ( ! isset( $store[PROPERTY_LENGTH] ) )
                $store[PROPERTY_LENGTH] = -1;

            $store[PROPERTY_MODE_ACCESS] = FILE_ACCESS_MODE_READ_ONLY;
            $properties = self::checkProperties( $store );
            $handle = self::getHandle( $properties );
            $stream = self::getStream();
            $existing_signal = $stream->{PROPERTY_SIGNAL};
            $store[PROPERTY_SIGNAL] = $existing_signal;
            $length = $stream->{PROPERTY_SIZE} / self::getHashLength();

            if ( $offset > $length )
            {
                unset( $store[PROPERTY_OFFSET] );
                $result = self::injectToStream( $store );
            }
            else
            {
                $result_opening = self::writeInSubstream( array(
                    PROPERTY_LENGTH => $offset,
                    PROPERTY_MODE_ACCESS => FILE_ACCESS_MODE_WRITE_ONLY,
                    PROPERTY_OFFSET => 0,
                    PROPERTY_PATH => $store[PROPERTY_PATH],
                    PROPERTY_PROPERTIES => $properties
                ) );

                $result_injection = self::writeInSubstream( array(
                    PROPERTY_LENGTH => -1,
                    PROPERTY_MODE_ACCESS => FILE_ACCESS_MODE_APPEND_ONLY,
                    PROPERTY_OFFSET => 0,
                    PROPERTY_PATH => $store[PROPERTY_PATH],
                    PROPERTY_PROPERTIES => $properties,
                    PROPERTY_SIGNAL => $signal
                ) );

                $result_closing = self::writeInSubstream( array(
                    PROPERTY_LENGTH => $length - $offset + 1,
                    PROPERTY_MODE_ACCESS => FILE_ACCESS_MODE_APPEND_ONLY,
                    PROPERTY_OFFSET => $offset,
                    PROPERTY_PATH => $store[PROPERTY_PATH],
                    PROPERTY_PROPERTIES => $properties,
                    PROPERTY_SIGNAL => $existing_signal
                ) );
            }
        }

        return $result_injection;
    }

    /**
    * Log information
    *
    * @param    mixed   $information
    * @param    mixed   $label
    * @param    mixed   $script
    * @param    mixed   $line
    * @param    mixed   $method
    * @return   nothing
    */
    public static function log(
        $information, $label = null,
        $script = null, $line = null, $method = null
    )
    {
        global $class_application, $verbose_mode;
        $class_dumper = $class_application::getDumperClass();

        $log_entry =
            '["' . $label . '" logged at '. date( 'Y-m-d Hi').' ] '
        ;

        $class_dumper::error_log( $log_entry );

        if ( ! is_null( $script ) )

            $class_dumper::error_log( '[script "' . $script . '"]' );

        if ( ! is_null( $line ) )
            
            $class_dumper::error_log( '[line # ' . $line . ']' );

        if ( ! is_null( $method ) )

            $class_dumper::error_log( '[when calling "' . $method . '"]' );


        $class_dumper::error_log( $information );
    }

    /**
    * Open a stream
    *
    * @param    string  $path           path
    * @param    string  $access_mode    access mode
    * @param    mixed   $context        context
    * @return   resource    handle
    */
    public static function openStream(
        $path, $access_mode, &$context = null
    )
    {
        self::setOption( PROPERTY_SERIALIZABLE, false, $context );
        $handle = fopen( $path, $access_mode, false, $context );
        self::setOption(
            PROPERTY_CONTAINER_REFERENCES,
            array( PROPERTY_HANDLE => &$handle ),
            $context
        );
        self::$persistent_context = $context;
        return $handle;
    }

    /**
    * Register safely a stream wrapper
    *
    * @return   nothing
    */
    public static function registerStreamWrapper()
    {
        $protocol = self::getProtocol();

        if ( 
            ( $wrappers = stream_get_wrappers() ) &&
            is_array( $wrappers ) && count( $wrappers ) &&
            ! in_array( $protocol, $wrappers )
        )
            stream_wrapper_register( $protocol, __CLASS__ );
    }

    /**
    * Render a context
    *
    * @param    mixed   $properties properties
    * @return   mixed   render
    */
    public static function render( $properties )
    {
        global $class_application;
        $class_entity = $class_application::getEntityClass();

        $excerpt =
        $render = '';

        $format_default =
            $class_entity::getDefaultType( null, ENTITY_FORMAT )
                ->{PROPERTY_VALUE}
        ;

        $render_type_default =
            $class_entity::getDefaultType( null, ENTITY_RENDER )
                ->{PROPERTY_VALUE}
        ;

        $end_of_line = '<br />';

        $properties = self::checkProperties( $properties );
        $context = &$properties[PROPERTY_CONTEXT];

        $length_stream = $properties[PROPERTY_SIZE];

        if ( ! isset( $properties[PROPERTY_RENDER] ) )
            $render_type = $render_type_default;
        else
            $render_type = $properties[PROPERTY_RENDER];

        if (ucfirst( $render_type ) !== 'Subsequence') {
            $tokens = call_user_func(
                array( __CLASS__, ACTION_GET . ucfirst( $render_type ) ),
                $properties[PROPERTY_PATH],
                $properties[PROPERTY_MODE_ACCESS],
                $properties[PROPERTY_LENGTH],
                $properties[PROPERTY_OFFSET],
                $context
            );
        } else {
            $tokens = call_user_func(
                array( __CLASS__, ACTION_GET . ucfirst( $render_type ) ),
                array(
                    PROPERTY_LENGTH => $properties[PROPERTY_LENGTH],
                    PROPERTY_MODE_ACCESS => $properties[PROPERTY_MODE_ACCESS],
                    PROPERTY_OFFSET => $properties[PROPERTY_OFFSET],
                    PROPERTY_PATH => $properties[PROPERTY_PATH],
                ), $context
            );
        }

        switch( $properties[PROPERTY_FORMAT] )
        {
            case $format_default: // XHTML

                $render .=
                    '<h1>' . 'Source introspection' . '</h1>' .
                    '<h2>' .
                        'stream length: '. $length_stream .
                    '</h2>'. 
                    '<p>'.
                    'resource: ' . $properties[PROPERTY_PATH] .
                    '</p>' .
                    'render type: ' . $render_type .
                    '</p>' .
                    '<p>' .
                    'excerpt: '.
                    '</p>'
                ;

                $aggregator = function( $value, $key ) use
                ( $end_of_line, &$excerpt, $render_type ) 
                {
                    $excerpt .=
                        print_r( $value, true ) .
                        (
                            $render_type === RENDER_TYPE_TOKEN
                            ? $end_of_line
                            : ''
                        )
                    ;
                };

                $render .='<pre>';
                
                if ( $render_type !== $render_type_default )
                {
                    array_walk( $tokens, $aggregator );

                    if ( $render_type === RENDER_TYPE_SIGNAL )
                        $excerpt = highlight_string( $excerpt, true );
                }
                else

                    $excerpt .= $tokens;

                $render .= $excerpt . '</pre>';

                    break;

            default:

                $render .= 'stream length: '. $length_stream . $end_of_line;
                $render .= '<pre>' . print_r( $tokens, true ) . '</pre>';

                    break;
        }

        return $render;
    }

    /**
    * Render a signal
    *
    * @param    mixed   $signal         signal
    * @param    mixed   $render_type    render type
    * @param    boolean $return         return flag
    * @return   mixed   render
    */
    public static function renderSignal(
        $signal, $render_type = null, $return = false
    )
    {
        global $class_application, $verbose_mode;

        $class_entity = $class_application::getEntityClass();

        $excerpt = '';

        $render_type_default =
            $class_entity::getDefaultType( null, ENTITY_RENDER )
                ->{PROPERTY_VALUE}
        ;

        if ( is_null( $render_type ) ) $render_type = $render_type_default;

        switch ( $render_type )
        {
            case RENDER_TYPE_BLOCK:

                $aggregator = function( $value, $key ) use ( &$excerpt )
                {
                    switch( ord( $value ) )
                    {
                        case ord( "\t" ): $clean_value = '\t'; break;
                        case ord( "\n"):

                            $clean_value = '';
                            $white_spaces = str_split( $value, 1 );
                            
                            foreach( $white_spaces as $whitespace )
                            
                                switch ( ord( $whitespace ) )
                                {
                                    case ord( "\t" ): $clean_value .= '\t';

                                        break;

                                    case ord( "\n" ): $clean_value .= '\n';

                                        break;
                                }

                        break;
                        default:

                            if (
                                ( false === strpos( $value, '/**' ) ) &&
                                ( false === strpos( $value, '//' ) )
                            )

                                $clean_value = htmlentities( str_replace(
                                    array( "\t", "\n" ),
                                    array(
                                        PLACEHOLDER_TABULATION,
                                        PLACEHOLDER_END_OF_LINE
                                    ),
                                    $value
                                ) );
                            else
                                $clean_value = '<pre>' . $value . '</pre>';                                 
                    }

                    $excerpt .=
                        '<div class="token" id="' . $key . '">' .
                            $clean_value .
                        '</div>'
                    ;
                };

                    break;

            case RENDER_TYPE_SIGNAL:

                $aggregator = function( $value, $key ) use ( &$excerpt )
                {
                    $excerpt .= $value;
                };

                    break;                  
        }

        array_walk( $signal, $aggregator );
    
        if ( $return === false )

            echo htmlentities( $excerpt );

        return $excerpt;
    }

    /**
    * Set a contextual option
    *
    * @param    string      $name       name
    * @param    string      $value      value
    * @param    resource    $context    context
    * @return   resource    context
    */
    public static function setOption(
        $name, $value = null, &$context = null
    )
    {
        $protocol = self::getProtocol();
        if (!is_null($context)) $options = self::extractOptions($context);
        if (!isset($options[$protocol])) $options[$protocol] = array();
        $options[$protocol][$name] = $value;
        $_context = stream_context_create( $options );
        $context = $_context;
    }

    /**
    * Set the current position of a stream
    *
    * @param    integer $position   position
    * @return   integer previous position of a stream
    */
    public static function setPosition( $position = null )
    {
        $previous_position = null;
        if ( isset( self::$position ) ) $previous_position = self::$position;
        self::$position = $position;
        return $previous_position;
    }

    /**
     * @param $count
     * @param $start
     *
     * @return string
     */
    public function setStreamSubsequence($count, $start)
    {
        $sequences                      = str_split($this->{PROPERTY_SEQUENCE}, self::getHashLength());
        $count                          = ($count === -1 ? count($sequences) : $count);
        $start                          = empty($start) ? 0 : $start;
        $subsequences                   = array_splice($sequences, $start, $count);
        $subsequence                    = implode($subsequences);
        $this->{PROPERTY_SUBSEQUENCE}   = $subsequence;

        return $this;
    }

    /**
    * Set a subsequence in a stream
    *
    * @param    array   $properties stream properties
    * @return   integer number of bytes written
    */
    public static function setSubsequence( $properties )
    {
        self::registerStreamWrapper();
        $properties[PROPERTY_OFFSET] = 0;
        $properties[PROPERTY_LENGTH] = -1;
        $properties = self::checkProperties( $properties );
        self::openStream(
            $properties[PROPERTY_PATH], $properties[PROPERTY_MODE_ACCESS],
            $properties[PROPERTY_CONTEXT]
        );
        $tokens_stream = self::getToken(
            $properties[PROPERTY_PATH],
            FILE_ACCESS_MODE_READ_ONLY,
            -1,
            null,
            $properties[PROPERTY_CONTEXT]
        );

        return $tokens_stream;
    }

    /**
    * Shape a stream from a descriptive store
    *
    * @param    array   $store  properties  store
    * @return   object  Tokens_Stream
    */          
    public static function shape( $store )
    {
        $store = self::checkProperties( $store );

        $conditions = ( object ) self::extractOptions( $store );
        $conditions->{PROPERTY_SIGNAL} = $store[PROPERTY_SIGNAL];

        return self::spawn( $conditions );
    }

    /**
    * Count and return the number of (hashes or tokens) detected
    * while translating a given resource into a stream
    *
    * @param    string  $path       path to resource
    * @param    mixed   $context    stream contextual options
    * @return   integer length
    */
    public static function slen( $path, &$context = null )
    {
        $length = 0;
        $tokens_stream = self::initialize( $path, true, $context );

        if (
            isset( $tokens_stream->{PROPERTY_TOKEN} ) &&
            is_array( $tokens_stream->{PROPERTY_TOKEN} )
        )
            $length = count( $tokens_stream->{PROPERTY_TOKEN} );

        return $length;
    }

    /**
    * Instantiate a TOKENS_STREAM from conditions
    * (contextual options typecasted as a an object)
    * @see      TOKENS_STREAM :: __construct
    * @see      ALPHA::__construct
    *
    * Extract tokens from a signal
    * Populate both Token and Size properties
    * (by multiplying the tokens count by the length of a hash,
    * its number of characters)
    * @see      TOKENS_STREAM->tokenize
    *
    * @param    object  $conditions conditions
    * @param    boolean $metadata   TRUE if only metadata (like length)
    *                               should be retrieved
    *                               FALSE otherwise
    *                                   depending on its value
    *                                   other function calls are made
    *
    * @see      TOKENS_STREAM :: streamLineContent
    * @see      TOKENS_STREAM :: feedRiver
    *
    * @return   object  Token Stream
    */
    public static function spawn( $conditions, $metadata = false )
    {
        $hash_length = self::getHashLength();

        if ( ! is_object( $conditions ) )
            throw new \Exception( EXCEPTION_INVALID_ARGUMENT );

            $tokens_stream = new self( $conditions );
            $tokens = $tokens_stream->tokenize();
            $count_tokens = count( $tokens );

            $tokens_stream->{PROPERTY_TOKEN} = $tokens;
            $tokens_stream->{PROPERTY_SIZE} = $count_tokens * $hash_length;

        if ( ! $metadata )
        {
            self::streamlineContent( $tokens, $tokens_stream );
            self::feedRiver( $tokens_stream );
        }

        return $tokens_stream;
    }

//  public static function spawn( $conditions, $metadata = FALSE )
//  {
//      $hash_length = self::getHashLength();
//      $stream = self::getStream();
//
//      if ( ! is_object( $conditions ) )
//
//          throw new \Exception( EXCEPTION_INVALID_ARGUMENT );
//
//      if ( is_null( $stream ) )
//      {
//          $tokens_stream = new self( $conditions );
//          $tokens = $tokens_stream->tokenize();
//          $count_tokens = count( $tokens );
//          $tokens_stream->{PROPERTY_TOKEN} = $tokens;
//          $tokens_stream->{PROPERTY_SIZE} = $count_tokens * $hash_length;
//      }
//      else
//      {
//          $tokens_stream = $stream;
//          $tokens = $tokens_stream->{PROPERTY_TOKEN};
//      }
//
//      if ( ! $metadata )
//      {
//          self::streamlineContent( $tokens, $tokens_stream );
//          self::feedRiver( $tokens_stream );
//      }
//
//      return $tokens_stream;
//  }

    /**
    * Streamline a content from tokens by building the following properties
    *   hashmap (tokens hashtable)
    *   sequence (concatenation of hashes)
    *   full stream (tokens indexed on position)
    *   
    *
    * @param    array   $tokens tokens
    * @param    object  &$container container
    * @return   object  container carrying streamlined content
    */
    public static function streamlineContent( $tokens, &$container = null )
    {
        $count_tokens = count( $tokens );
        $full_stream = 
        $hash_map = array();
        $sequence = '';

            for (
            $token_index = $count_tokens - 1 ;
            $token_index >= 0 ;
            $token_index--
            )
            {
                $item = array(
                    PROPERTY_HASH => md5( serialize( $tokens[$token_index] ) ),
                    PROPERTY_INDEX => $token_index,
                    PROPERTY_TOKEN => $tokens[$token_index]
                );
    
                $hash_map[$item[PROPERTY_HASH]] = $item;
            array_unshift( $full_stream, $item );
            $sequence = $item[PROPERTY_HASH] . $sequence;
        }

        if ( is_null( $container ) ) $container = new \stdClass();

        $container->{PROPERTY_HASH_MAP} = $hash_map;
        $container->{PROPERTY_SEQUENCE} = $sequence;
        $container->{PROPERTY_STREAM_FULL} = $full_stream;

        return $container;
    }

    /**
    * Streamline a content from tokens by building the following properties
    *   hashmap (tokens hashtable)
    *   sequence (concatenation of hashes)
    *   full stream (tokens indexed on position)
    *
    *
    * @param    array   $tokens tokens
    * @param    object  &$container container
    * @return   object  container carrying streamlined content
    */
//    public static function streamlineContent( $tokens, &$container = NULL )
//    {
//        global $class_application;
//        $class_memento = $class_application::getMementoClass();
//        $count_tokens = count( $tokens );
//        $_container = new \stdClass();
//        $full_stream =
//        $hash_map = array();
//        $sequence = '';
//
//        $memento_key = md5( serialize( array(
//            __METHOD__, $container->{PROPERTY_PATH_FILE}
//        ) ) );
//        $callback_parameters_cached = $class_memento::retrieveData(
//            $memento_key
//        );
//
//        if ( FALSE === $callback_parameters_cached )
//        {
//            for (
//                $token_index = 0;
//                $token_index < $count_tokens;
//                $token_index++
//            )
//            {
//                $item = array(
//                    PROPERTY_HASH => md5( serialize( $tokens[$token_index] ) ),
//                    PROPERTY_INDEX => $token_index,
//                    PROPERTY_TOKEN => $tokens[$token_index]
//                );
//
//                $hash_map[$item[PROPERTY_HASH]] = $item;
//                $full_stream[] = $item;
//                $sequence .= $item[PROPERTY_HASH];
//            }
//
//            $_container->{PROPERTY_HASH_MAP} = $hash_map;
//            $_container->{PROPERTY_SEQUENCE} = $sequence;
//            $_container->{PROPERTY_STREAM_FULL} = $full_stream;
//
//            $class_memento::storeData( $_container, $memento_key );
//        }
//        else
//
//            $_container = $callback_parameters_cached;
//
//        foreach ( $_container as $name => $value )
//
//            $container->$name = $value;
//
//        return $container;
//    }

    /**
    * Check if a method is valid
    *
    * @param    $method 
    * @return   result
    */
    public static function validMethod( $method )
    {
        $result = false;
    
        if ( 
            isset( $method ) &&
            isset( $method[PROPERTY_METHOD] ) &&
            ( is_array( $method[PROPERTY_METHOD] ) ) &&
            isset( $method[PROPERTY_METHOD][0] ) &&
            ( $class = trim( $method[PROPERTY_METHOD][0] ) ) &&
            class_exists( $class ) &&
            ( $methods = get_class_methods( $class ) ) &&
            isset( $method[PROPERTY_METHOD][1] ) &&
            ( $function = trim( $method[PROPERTY_METHOD][1] ) ) &&
            in_array( $function, $methods )
        )
            $result = array( $class, $function );
    
        return $result;
    }

    /**
    * Write tokens on a stream
    *
    * @param    array   $store  properties  store
    * @return   integer written tokens count
    */
    public static function writeInStream( $store )
    {
        $hash_length = self::getHashLength();

        if ( ! isset( $store[PROPERTY_DATA] ) )
        {
            self::registerStreamWrapper();
            $properties = self::checkProperties( $store );
            $handle = self::getHandle( $properties );
            $stream = self::getStream();

            if ( ! isset( $store[PROPERTY_OFFSET] ) )
            {
                $data = $stream->{PROPERTY_SEQUENCE};
                $tokens_count = $stream->{PROPERTY_SIZE} / $hash_length;
            }
            else
            {
                if ( ! isset( $store[PROPERTY_LENGTH] ) )
                    $store[PROPERTY_LENGTH] = -1;

                self::getSubstream(
                    $store[PROPERTY_PATH], FILE_ACCESS_MODE_READ_ONLY,
                    $store[PROPERTY_LENGTH], $store[PROPERTY_OFFSET],
                    $properties[PROPERTY_CONTEXT]
                );
                global $class_application, $verbose_mode;
                $class_dumper = $class_application::getDumperClass();
                $class_dumper::log( __METHOD__, array(
                    $stream
                ), false );
                $data = $stream->{PROPERTY_SUBSEQUENCE};
                $tokens_count = strlen( $data ) / $hash_length;
            }
        }
        else
        {
            /**
            * Extract contextual parameters
            *
            * @tparam   $handle
            * @tparam   $data
            */
            extract( $store );
            $tokens_count = strlen( $data ) / $hash_length;
        }

        // third parameter has been set to length of 1 byte
        // to prevent atomic writing with append mode
        $result = fwrite( $handle, $data, 1 );
        $bytes = $result ? $tokens_count : false;

        return $bytes;
    }

    /**
    * Write in a substream
    *
    * @param    mixed   $store
    * @return   mixed   result
    */
    public static function writeInSubstream( $store )
    {
        /**
        * Extract properties
        *
        * @tparam   string      $access_mode
        * @tparam   resource    $context        (optional)
        * @tparam   integer     $length
        * @tparam   integer     $offset
        * @tparam   string      $path
        * @tparam   mixed       $properties
        * @tparam   string      $signal         (optional)
        */
        extract( $store );

        if ( isset( $context ) ) $properties[PROPERTY_CONTEXT] = $context;
        if ( isset( $signal ) )
        {
            $properties[PROPERTY_SIGNAL] = $signal;
            self::checkContext( $properties );
        }

        $properties[PROPERTY_MODE_ACCESS] = $access_mode;
        $properties = self::checkProperties( $properties );
        $handle = self::getHandle( $properties );
        $substream = self::getSubstream(
            $path, FILE_ACCESS_MODE_READ_ONLY,
            $length, $offset,
            $properties[PROPERTY_CONTEXT]
        );  
        $stream = self::getStream();
        global $class_application, $verbose_mode;
        $class_dumper = $class_application::getDumperClass();
        $class_dumper::log( __METHOD__, array(
            $stream
        ), false );
        $_store = array(
            PROPERTY_DATA => $stream->{PROPERTY_SUBSEQUENCE},
            PROPERTY_HANDLE => $handle
        );
        return self::writeInStream( $_store );
    }
}
