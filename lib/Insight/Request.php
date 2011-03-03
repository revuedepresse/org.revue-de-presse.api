<?php

class Insight_Request
{
    protected $config = null;
    protected $clientKey = false;
    protected $url = false;
    protected $action = false;
    protected $arguments = array();
    
    
    public function setConfig($config)
    {
        $this->config = $config;
    }

    public function setClientKey($key) {
        $this->clientKey = $key;
    }

    public function getClientKey() {
        if(!$this->clientKey) {
            throw new Exception('Client key not set');
        }
        return $this->clientKey;
    }

    public function initServerRequest($payload)
    {
        $this->url = (isset($payload['url']))?$payload['url']:false;
        if($this->url) {
            // strip protocol (we assume the same code is run for same URL no matter what the protocol is)
            $urlInfo = parse_url($this->url);
            $this->url = substr($this->url, strlen($urlInfo['scheme']) + 3);
        }
        $this->action = $payload['action'];
        $this->arguments = (isset($payload['args']))?$payload['args']:array();
    }

    public function initAppRequest($server)
    {
        // strip protocol (we assume the same code is run for same URL no matter what the protocol is)
        $this->url = $server['HTTP_HOST'] . $server['REQUEST_URI'];
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function getAction()
    {
        return $this->action;
    }

    public function getArguments()
    {
        return $this->arguments;
    }

    public function getArgument($name)
    {
        if(!isset($this->arguments[$name])) {
            throw new Exception('Argument not set: ' . $name);
        }
        return $this->arguments[$name];
    }
    
    public function getFromCache($name)
    {
        $file = $this->_cachePathForName($name);
        if(!file_exists($file)) {
            return false;
        }
        return json_decode(file_get_contents($file), true);
    }

    public function storeInCache($name, $object)
    {
        file_put_contents($this->_cachePathForName($name), json_encode($object));
    }

    protected function _cachePathForName($name)
    {
        $url = $this->getUrl();
        if(!$url) {
            throw new Exception('URL must be set for request in order to use cache!');
        }

        $file = $this->config->getCachePath() . DIRECTORY_SEPARATOR .
                '_request' . DIRECTORY_SEPARATOR .
                md5('lkA022HSye2' . $this->getClientKey()) . '-' . md5($url);
        if(!file_exists($file)) {
            if(!mkdir($file, 0775, true)) {
                throw new Exception('Error creating cache path at: ' . $file);
            }
        }
        return $file . DIRECTORY_SEPARATOR . $name . '.json';        
    }
    
}
