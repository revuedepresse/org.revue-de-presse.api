<?php

require_once('Insight/Plugin/Console.php');

class Insight_Plugin_Page extends Insight_Plugin_Console {

    public function console() {
        return $this->message->api('Insight_Plugin_Console')->meta(array(
            'target' => 'console'
        ));
    }

}
