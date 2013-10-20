#!/bin/bash

# MESSAGES=1
# MEMORY_LIMIT=64
/usr/bin/php $PROJECT_DIR/app/console wtw:amqp:twitter:produce:user_timeline --oauth_token=## FILL ME ## --oauth_secret=n## FILL ME ## --log --screen_name=$SCREEN_NAME
