<?php

// check the query flag
$query = true;

$offset = 0;

if (!$query)

    exit();

$resource = curl_init();

$header = array(
    "Host: www.netvibes.com",
    "User-Agent: Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10.6; fr; rv:1.9.2) Gecko/20100115 Firefox/3.6 FirePHP/0.4",
    "Accept: text/javascript, text/html, application/xml, text/xml, */*",
    "Accept-Language: en-us,en;q=0.8,fr;q=0.5,fr-fr;q=0.3",
    "Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7",
    "Keep-Alive: 115",
    "Connection: keep-alive",
    "X-Requested-With: XMLHttpRequest",
    "Referer: http://www.netvibes.com/",
    "Cookie: "
);

curl_setopt($resource, CURLOPT_HTTPHEADER, $header);

curl_setopt($resource, CURLOPT_POST, true);
            
curl_setopt($resource, CURLOPT_RETURNTRANSFER, true);

curl_setopt($resource, CURLOPT_POST, true);

curl_setopt($resource, CURLOPT_HEADER, false);

// $_response = curl_exec($resource);

while ($offset < 1000)
{
    $public_url = "http://www.netvibes.com/rest/timeline?offset=100&format=atom&query=status%3Apublic";
    
    $private_url = "http://www.netvibes.com/rest/timeline?offset={$offset}&format=json&query=status%3Aprivate";

    $private_handler = fopen(dirname(__FILE__)."/../feeds/netvibes/netvibes-private-feed-offset-".str_pad($offset, 4, '0', STR_PAD_LEFT).".json", "a+");

    if ($offset < 110)
    
        $public_handler = fopen(dirname(__FILE__)."/../feeds/netvibes/netvibes-public-feed-offset-".str_pad($offset, 4, '0', STR_PAD_LEFT).".json", "a+");

    curl_setopt($resource, CURLOPT_URL, $private_url);

    $private_response = curl_exec($resource);
    
    curl_setopt($resource, CURLOPT_URL, $public_url);

    $public_response = curl_exec($resource);

    dumper::log(
        __METHOD__,
        array(
            $private_url
        ),
        false
    );

    fwrite($private_handler, $private_response);

    if ($offset < 110)
    
        fwrite($public_handler, $public_response);

    $offset += 20;
/*
    if ($offset > 50)

        exit();
*/
    fclose($private_handler);

    if ($offset < 110)

        fclose($public_handler);
}

curl_close($resource);