<?php

require_once('Insight/Plugin/API.php');

class Insight_Plugin_Package extends Insight_Plugin_API {

    public function setInfo($info) {
        return $this->message->meta(array(
            "encoder" => "JSON",
            "target" => "info"
        ))->send($info);
    }

}
