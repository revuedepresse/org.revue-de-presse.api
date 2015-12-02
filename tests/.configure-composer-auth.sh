#!/bin/bash

sed -e 's/"github.com": ""/"github.com": "'$COMPOSER_AUTH'"/' provisioning/files/auth.json.dist > ~/.composer/auth.json
echo 'Added authentication file to composer directory'
