<?php

class Wildfire_Receiver
{
    
    private $channel = null;
    private $sender = null;
    private $receiver = null;
    private $protocol = null;
    
    
    public function setChannel($channel)
    {
        $this->channel = $channel;
        $this->channel->addReceiver($this);
    }

    public function getChannel()
    {
        return $this->channel;
    }

    public function setProtocol($protocol)
    {
        $this->protocol = $protocol;
    }
    
    public function getProtocol()
    {
        return $this->protocol;
    }
    
    public function setSender($sender)
    {
        $this->sender = $sender;
    }
    
    public function getSender()
    {
        return $this->sender;
    }
    
    public function setReceiver($receiver)
    {
        $this->receiver = $receiver;
    }
    
    public function getReceiver()
    {
        return $this->receiver;
    }


    public function onMessageGroupStart() {
        // TO BE SUBCLASSED
    }
    
    public function onMessageGroupEnd() {
        // TO BE SUBCLASSED
    }
    
    public function onMessageReceived(Wildfire_Message $message)
    {
        // TO BE SUBCLASSED
    }
}
