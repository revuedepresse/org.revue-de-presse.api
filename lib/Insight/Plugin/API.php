<?php

class Insight_Plugin_API {

    protected $temporaryTraceOffset = null;
    protected $traceOffset = 4;
    protected $message = null;
    protected $request = null;


    public function setRequest($request) {
        $this->request = $request;
    }

    public function setMessage($message) {
        $oldmsg = $this->message;
        $this->message = $message;
        return $oldmsg;
    }
    
    public function setTemporaryTraceOffset($offset) {
        $this->temporaryTraceOffset = $offset;
    }

    protected function _addFileLineMeta($meta=false, $data=false) {
        if(!$meta) {
            $meta = array();
        }
        if($data!==false && $data instanceof Exception && $this->temporaryTraceOffset==-1) {
            $meta['file'] = $data->getFile();
            $meta['line'] = $data->getLine();
        } else {
            $backtrace = debug_backtrace();
            $offset = $this->traceOffset;
            if($this->temporaryTraceOffset!==null) {
                $offset = $this->temporaryTraceOffset;
                $this->temporaryTraceOffset = null;
            }
            if($offset>=0) {
                if(isset($backtrace[$offset]['file'])) {
                    $meta['file'] = $backtrace[$offset]['file'];
                }
                if(isset($backtrace[$offset]['line'])) {
                    $meta['line'] = $backtrace[$offset]['line'];
                }
            }
        }
        return $meta;
    }
}
