# Requirements

Follow [dotdeb instructions](http://www.dotdeb.org/instructions/) to add new repositories to your apt source list

    deb http://packages.dotdeb.org wheezy all
    deb-src http://packages.dotdeb.org wheezy all

    deb http://packages.dotdeb.org wheezy-php55 all
    deb-src http://packages.dotdeb.org wheezy-php55 all

    wget http://www.dotdeb.org/dotdeb.gpg
    cat dotdeb.gpg | sudo apt-key add -

JavaScript dependencies

    # assets pre-compilation
    npm install -g less

    # assets management
    npm install -g bower
    
    # installing karma, launchers and reporters for testing
    cd / && npm install

Redis server and redis extension for PHP

    # Install Redis
    apt-get install redis-server php5-redis

APCU extension for PHP

    apt-get install php5-apcu

RabbiMQ server

    # Install RabbitMQ server
    apt-get install rabbitmq-server

    # Enable mod_proxy and mod_proxy_http
    a2enmod proxy proxy_http

    # Enable RabbitMQ management plugin
    rabbitmq-plugins enable rabbitmq_management

    # List exchanges
    rabbitmqctl list_exchanges name

    # List consumers
    rabbitmqctl list_consumers -p /weaving_the_web

    # List channels
    rabbitmqctl list_channels  -p /weaving_the_web

    # List queues
    rabbitmqctl list_queues -p /weaving_the_web

    # Add vhost
    rabbitmqctl add_vhost /weaving_the_web

    # Declare user
    rabbitmqadmin declare user name=weaver password='***' tags=administrator,management,monitoring

    # OR in order to add a user
    rabbitmqctl add_user weaver '***'

    # AND to set his user tags
    rabbitmqctl set_user_tags weaver administrator,management,monitoring

    # Sets permissions
    rabbitmqctl set_permissions -p /weaving_the_web weaver ".*" ".*" ".*"

    # Delete a virtual host
    rabbitmqctl delete_vhost weaving_the_web

    # List user
    rabbitmqadmin list users -u weaver -p '***'

    # Delete default user
    rabbitmqadmin delete user name=guest

Elastic Search

    # Downloads .tar.gz archive and extract its content to /usr/share/elasticsearch

    In configuration (/etc/elasticsearch/elasticsearch.yml), uncomment following directives
    cluster.name: elastic-search-libran

    # Check cluster health
    curl -XGET 'http://localhost:9200/_cluster/health?pretty=true'

    # Check count of indexed document
    curl -XGET 'http://localhost:9200/_nodes/stats' | python -mjson.tool | grep -A 5 -B 5 '"docs"'

    # List indices
    curl -XGET http://localhost:9200/_aliases?pretty=1

    # Output insight about on-going index shard recoveries
    curl -XGET http://localhost:9200/_recovery?pretty=true

Kibana

    # Download archive and extract its content to /usr/share/elasticsearch/kibana
    https://download.elasticsearch.org/kibana/kibana/kibana-latest.tar.gz

Supervisor

    ## Debian
    apt-get install supervisor

    # After updating configuration of data collection workers
    supervisor reread

    supervisor update
