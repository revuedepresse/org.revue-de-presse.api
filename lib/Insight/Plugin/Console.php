<?php

require_once('Insight/Util.php');
require_once('Insight/Plugin/API.php');

class Insight_Plugin_Console extends Insight_Plugin_API {
    
    protected static $groupIndex = 0;


    public function label($label) {
        return $this->message->meta(array(
            'label' => $label
        ));
    }

    public function renderer($renderer) {
        return $this->message->meta(array(
            'renderer' => $renderer
        ));
    }

    public function filter($filter) {
        return $this->message->meta(array(
            'encoder.filter' => $filter
        ));
    }
    
    public function log($data) {
        $this->message->meta($this->_addFileLineMeta(array(
            'priority' => 'log'
        )))->send($data);
    }

    public function info($data) {
        $this->message->meta($this->_addFileLineMeta(array(
            'priority' => 'info'
        )))->send($data);
    }
    
    public function warn($data) {
        $this->message->meta($this->_addFileLineMeta(array(
            'priority' => 'warn'
        )))->send($data);
    }

    public function error($data) {
        $this->message->meta($this->_addFileLineMeta(array(
            'priority' => 'error'
        ), $data))->send($data);
    }

    public function group($name=null) {
        if(!$name) {
            $name = md5(uniqid() . microtime(true) . (self::$groupIndex++));
        }
        return $this->message->api('Insight_Plugin_Group')->meta(array(
            'group' => $name
        ));
    }

    public function trace($title) {

        $trace = debug_backtrace();
        if(!$trace) return false;
        array_splice($trace, 0, $this->traceOffset-1);

        $this->message->meta($this->_addFileLineMeta(array(
            'renderer' => 'insight:structures/trace',
            'encoder.depthExtend' => 5
        )))->send(array(
            'title' => $title,
            'trace' => $trace
        ));
    }

    public function table($title, $data, $header=false) {
        
        if(is_object($data)) {
            // convert object to name/value table
            $originalData = $data;
            $data = array();
            foreach( (array)$originalData as $name => $value ) {
                $data[] = array($name, $value);
            }
        } else
        if(is_array($data)) {
            if(Insight_Util::is_list($data)) {
                // we have a simple list
                // this means we have a set of rows with cells or just a list where each element should be it's own row
                $originalData = $data;
                $isSimple = false;
                foreach( $originalData as $value ) {
                    // if value is not an array we assume we have a list where the value should be it's own row
                    // i.e. we will wrap each value in an array to simulate a row with one cell
                    if(!is_array($value) || !Insight_Util::is_list($value)) {
                        $isSimple = true;
                        break;
                    }
                }
                if($isSimple) {
                    // wrap each element in an array to simulate a row with one cell
                    $data = array();
                    foreach( $originalData as $name => $value ) {
                        $data[] = array($value);
                    }
                }
            } else {
                // convert associative array to name/value table
                $originalData = $data;
                $data = array();
                foreach( $originalData as $name => $value ) {
                    $data[] = array($name, $value);
                }
            }
        }
        
        $this->message->meta($this->_addFileLineMeta(array(
            'renderer' => 'insight:structures/table',
            'encoder.depthExtend' => 2
        )))->send(array(
            'title' => $title,
            'data' => $data,
            'header' => $header
        ));
    }

    public function on($path) {
        return $this->message->api('Insight_Plugin_Selective', true)->on($path);
    }

    public function nolimit($nolimit=true) {
        return $this->message->meta(array(
            'encoder.depthNoLimit' => ($nolimit===true)
        ));
    }
}
