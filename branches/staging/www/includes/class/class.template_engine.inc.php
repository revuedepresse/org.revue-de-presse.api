<?php

/**
* Template engine class
*
* Class to construct Template Engine
* @package  sefi
*/
class Template_Engine extends Smarty_SEFI
{
    /**
    * Construct a Smarty template
    *
    * @return  object   representing a Smarty template instance
    */	    
    public function __construct()
    {
        parent::__construct();
    }

    /**
    * Clear all cache
    *
    * @param    boolean $force      forcing flag
    * @param    mixed   $checksum   checksum
    * @return   nothing
    */	    
    public function clear( $force = FALSE, $checksum = NULL )
    {
        if ( ! DEPLOYMENT_CACHING || $force )

            $this->clear_all_cache();
    }

    /**
    * Executes & returns or displays the template results
    *
    * @param    string      $resource_name  template name
    * @param    string      $cache_id       cache id 
    * @param    string      $compile_id     compile id
    * @param    string      $parent         parent template
    * @param    boolean     $display        display flag
    * @return   mixed
    */
    public function fetch(
        $resource_name,
        $cache_id = NULL,
        $compile_id = NULL,
        $parent = NULL,
        $display = FALSE
    )
    {
        return parent::fetch(
            $resource_name,
            $cache_id,
            $compile_id,
            $parent,
            $display
        );
    }

    /**
    * Clear all cache
    *
    * @param    integer $exp_time   expiration time
    * @param    string  $type       resource type
    * @return   integer     number of cache files deleted
    */
    public function clear_all_cache( $exp_time = NULL, $type = NULL )
    {
        return $this->clearAllCache( $exp_time, $type );
    }

    /**
    * Executes & returns or displays the template results
    *
    * @param    string  $template 
    * @param    string  $cache_id
    * @param    string  $compile_id
    * @param    mixed   $parent
    * @return   nothing
    */
    public function is_cached(
        $template,
        $cache_id = NULL,
        $compile_id = NULL,
        $parent = NULL
    )
    {
        if ( defined( 'DEPLOYMENT_CACHING' ) && DEPLOYMENT_CACHING )
        
            $is_cached = $this->isCached(
                $template,
                $cache_id = NULL,
                $compile_id = NULL,
                $parent = NULL
            );    
        else
            $is_cached = FALSE;

        return $is_cached;
    }
}

/**
*************
* Changes log
*
*************
* 2011 09 27
*************
*
* project :: wtw ::
*
* deployment :: template engine :
*
* Enable caching in dependence with deployment settings
*
* method affected ::
*
* TEMPLATE_ENGINE->is_cached
* 
* (revision 330)
*
*/