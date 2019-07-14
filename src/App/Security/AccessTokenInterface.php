<?php

namespace App\Security;

interface AccessTokenInterface
{
    public function getAccessToken();

    public function getAccessTokenSecret();

    public function getConsumerKey();

    public function getConsumerSecret();
}
