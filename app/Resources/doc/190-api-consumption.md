# API consumption

## Messages production

Set up RabbitMQ message fabric

```
# You should see a message like "Setting up the Rabbit MQ fabric"
source bin/setup-messaging
```

Produce a message to fetch statuses from the timeline of a user,
which screen name has been passed as the first command option

```
SCREEN_NAME=dev_obs
source bin/produce-user-status-messages $SCREEN_NAME
```

## Messages consumption

Consume previously produced messages

```
source bin/consume-user-status-messages
```

## Command-line administration

Download RabbitMQ administration binary from [http://10.9.8.2:15672/cli/](http://10.9.8.2:15672/cli/)

```
wget http://10.9.8.2:15672/cli/rabbitmqadmin /usr/local/bin/rabbitmqadmin
```
