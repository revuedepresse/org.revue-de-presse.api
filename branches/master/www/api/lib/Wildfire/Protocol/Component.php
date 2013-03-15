<?php

require_once 'Wildfire/Protocol.php';


class Wildfire_Protocol_Component extends Wildfire_Protocol
{    
    const CHUMK_DELIM = '__<|CHUNK|>__';

    public function parse(&$buffers, &$receivers, &$senders, &$messages, $key, $value) {
    }
    
    public function encodeMessage($options, $message)
    {
        if($this->uri=="http://registry.pinf.org/cadorn.org/wildfire/@meta/protocol/component/0.1.0" ||
           $this->uri=="__TEST__") {

            $protocol_id = $message->getProtocol();
            if(!$protocol_id) {
                throw new Exception("Protocol not set for message");
            }
            $receiver_id = $message->getReceiver();
            if(!$receiver_id) {
                throw new Exception("Receiver not set for message");
            }
            $sender_id = $message->getSender();
            if(!$sender_id) {
                throw new Exception("Sender not set for message");
            }
            
            $headers = array();
            
            $meta = $message->getMeta();
            if(!$meta) {
                $meta = '';
            }
    
            $data = str_replace('|', '&#124;', $meta) . '|' . str_replace('|', '&#124;', $message->getData());

            $parts = explode(self::CHUMK_DELIM, chunk_split($data, $options['messagePartMaxLength'], self::CHUMK_DELIM));
    
            for ($i=0 ; $i<count($parts) ; $i++) {
    
                $part = $parts[$i];
                if ($part) {
    
                    $msg = '';
    
                    if (count($parts)>2) {
                        $msg = (($i==0)?strlen($data):'')
                               . '|' . $part . '|'
                               . (($i<count($parts)-2)?'\\':'');
                    } else {
                        $msg = strlen($part) . '|' . $part . '|';
                    }

                    $headers[] = array(
                        $protocol_id,
                        $receiver_id,
                        $sender_id,
                        $msg);
                }
            }
    
            return $headers;
        }
    }
    
    public function encodeKey($util, $receiverId, $senderId)
    {
        if($this->uri=="http://registry.pinf.org/cadorn.org/wildfire/@meta/protocol/component/0.1.0" ||
           $this->uri=="__TEST__") {
        
            if(!isset($util["protocols"])) $util["protocols"] = array();
            if(!isset($util["messageIndexes"])) $util["messageIndexes"] = array();
            if(!isset($util["receivers"])) $util["receivers"] = array();
            if(!isset($util["senders"])) $util["senders"] = array();
    
            $protocol = self::_getProtocolIndex($util, $this->uri);
            $messageIndex = self::_getMessageIndex($util, $protocol);
            $receiver = self::_getReceiverIndex($util, $protocol, $receiverId);
            $sender = self::_getSenderIndex($util, $protocol, $receiver, $senderId);
            
            return $util["HEADER_PREFIX"] . $protocol . "-" . $receiver . "-" . $sender . "-" . $messageIndex;
        }
    }

    
    private static function _getProtocolIndex($util, $protocolId)
    {
        if(isset($util["protocols"][$protocolId])) return $util["protocols"][$protocolId];
        for( $i=1 ; ; $i++ ) {
            $value = $util["applicator"]->getMessagePart($util['HEADER_PREFIX'] . "protocol-" . $i);
            if(!$value) {
                $util["protocols"][$protocolId] = $i;
                $util["applicator"]->setMessagePart($util['HEADER_PREFIX'] . "protocol-" . $i, $protocolId);
                return $i;
            } else
            if($value==$protocolId) {
                $util["protocols"][$protocolId] = $i;
                return $i;
            }
        }
    }
    
    private static function _getMessageIndex($util, $protocolIndex)
    {
        if(isset($util["messageIndexes"][$protocolIndex])) {
            $value = $util["messageIndexes"][$protocolIndex];
        } else {
            $value = $util["applicator"]->getMessagePart($util['HEADER_PREFIX'] . $protocolIndex . "-index");
        }
        if(!$value) {
            $value = 0;
        }
        $value++;
        $util["messageIndexes"][$protocolIndex] = $value;
        $util["applicator"]->setMessagePart($util['HEADER_PREFIX'] . $protocolIndex . "-index", $value);
        return $value;
    }
    
    private static function _getReceiverIndex($util, $protocolIndex, $receiverId)
    {
        if(isset($util["receivers"][$protocolIndex . ":" . $receiverId])) return $util["receivers"][$protocolIndex . ":" . $receiverId];
        for( $i=1 ; ; $i++ ) {
            $value = $util["applicator"]->getMessagePart($util['HEADER_PREFIX'] . $protocolIndex . "-" . $i . "-receiver");
            if(!$value) {
                $util["receivers"][$protocolIndex . ":" . $receiverId] = $i;
                $util["applicator"]->setMessagePart($util['HEADER_PREFIX'] . $protocolIndex . "-" . $i . "-receiver", $receiverId);
                return $i;
            } else
            if($value==$receiverId) {
                $util["receivers"][$protocolIndex . ":" . $receiverId] = $i;
                return $i;
            }
        }
    }
    
    private static function _getSenderIndex($util, $protocolIndex, $receiverIndex, $senderId)
    {
        if(isset($util["senders"][$protocolIndex . ":" . $receiverIndex . ":" . $senderId])) return $util["senders"][$protocolIndex . ":" . $receiverIndex . ":" . $senderId];
        for( $i=1 ; ; $i++ ) {
            $value = $util["applicator"]->getMessagePart($util['HEADER_PREFIX'] . $protocolIndex . "-" . $receiverIndex . "-" . $i . "-sender");
            if(!$value) {
                $util["senders"][$protocolIndex . ":" . $receiverIndex . ":" . $senderId] = $i;
                $util["applicator"]->setMessagePart($util['HEADER_PREFIX'] . $protocolIndex . "-" . $receiverIndex . "-" . $i . "-sender", $senderId);
                return $i;
            } else
            if($value==$senderId) {
                $util["senders"][$protocolIndex . ":" . $receiverIndex . ":" . $senderId] = $i;
                return $i;
            }
        }
    }

}
