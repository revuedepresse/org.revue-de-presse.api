# API consumption

## Messages production

Set up RabbitMQ message fabric

```
# You should see a message like "Setting up the Rabbit MQ fabric"
source bin/setup-messaging
```

Produce message to fetch statuses from the timeline of all users connected to Twitter

```
source bin/produce-user-status-messages
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


## Web administration

Access the following URL [http://10.9.8.2:15672/](http://10.9.8.2:15672/) and  
log in to the administration panel as `default` RabbitMQ user in development box.
