<?php

require_once('Insight/Plugin/Console.php');

class Insight_Plugin_Group extends Insight_Plugin_Console {
    
    public function open() {
        $this->message->meta($this->_addFileLineMeta(array(
            'group.start' => true
        )))->send(true);
        return $this->message;
    }

    public function close() {
        $this->message->meta($this->_addFileLineMeta(array(
            'group.end' => true
        )))->send(true);
        return $this->message;
    }
}
