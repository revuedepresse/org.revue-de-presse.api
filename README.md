Weaving The Web Experiments
========================

1) Sitemap
--------------------------------

* Documentation - ([documentation]({{ baseurl }}/doc/readme))
* JSON store inspection - ([documentation]({{ baseurl }}/0/1/1))
* Social Media Dashboard - ([dashboard]({{ baseurl }}/documents))
* Sign in with Twitter - ([sign in]({{ baseurl }}/twitter/connect))

2) Testing
--------------------------------

### Running test suite ###

From project directory, run following command:

    phpunit -c ./app

### Testing controllers ###

Testing controllers and matching routes requires updating ``basic_auth_pattern`` parameter in ``app/config/config_test.yml``

### Testing commands ###

To load data fixtures before testing a command class:

 * Declare a command test class extending ``WTW\CodeGeneration\QualityAssuranceBundle\Test\CommandTestCase``
 * Implement a special method ``requiredFixtures`` which return value evaluates to ``true``.

### Testing the mobile application ###

    karma start tests/karma.config.js

3) Requirements
--------------------------------

Follow [dotdeb instructions](http://www.dotdeb.org/instructions/) to add new repositories to your apt source list

    deb http://packages.dotdeb.org wheezy all
    deb-src http://packages.dotdeb.org wheezy all

    deb http://packages.dotdeb.org wheezy-php55 all
    deb-src http://packages.dotdeb.org wheezy-php55 all

    wget http://www.dotdeb.org/dotdeb.gpg
    cat dotdeb.gpg | sudo apt-key add -

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

    # List user
    rabbitmqadmin list users -u weaver -p '***'

    # Delete default user
    rabbitmqadmin delete user name=guest

Elastic Search

    # Downloads .tar.gz archive and extract its content to /usr/share/elasticsearch

    In configuration (/etc/elasticsearch/elasticsearch.yml), uncomment following directives
    cluster.name: elastic-search-libran

Kibana

    # Download archive and extract its content to /usr/share/elasticsearch/kibana
    https://download.elasticsearch.org/kibana/kibana/kibana-latest.tar.gz

Supervisor

    ## Debian
    apt-get install supervisor

    # After updating configuration of data collection workers
    supervisor reread

    supervisor update

4) Commands
--------------------------------

From project root directory, execute the following commands to transform

    # data collected about GitHub repositories
    php app/console wtw:api:manage:transformation --process_isolation --save [--type=repositories]

    # data collected from personal Facebook newsfeed
    php app/console wtw:api:manage:transformation --process_isolation --save --type=feed

    # data collected from personal Twitter user stream
    php app/console wtw:api:manage:transformation --process_isolation --save --type=user_stream

Messaging

    # To produce a message
    app/console wtw:amqp:twitter:produce:user_timeline [--oauth_token=xxxx] [--oauth_secret=xxxx] [--log] --screen_name=thierrymarianne

    # To consume the first message ever produced before
    app/console rabbitmq:consumer -m 130 weaving_the_web_amqp.twitter.user_status

User management

    # Promote user
    app/console fos:user:promote --super gordon

    # Activate user
    app/console fos:user:activate

Status Mapping

    # Update statuses
    app/console wtw:das:map:sts ./src/WeavingTheWeb/Bundle/DashboardBundle/Resources/closures/updateStatusCreatedAt.php

Migrations

    # The admin connection and ORM are specially configured to handle migrations
    app/console doctrine:migrations:migrate --em=admin

5) Perspectives

--------------------------------

Examples

    # Update facebook perspective
    UPDATE weaving_perspective
    SET per_value =
        CONCAT(
            '# Show links from Facebook ', "\n",
            'SELECT ', "\n",
            '# picture as img_Thumbnail,', "\n",
            'name AS Title,', "\n",
            'description as Description,', "\n",
            'count(link) hid_count_, ', "\n",
            'link as lnk_Source, ', "\n",
            '#message as Message,', "\n",
            '#caption as Caption, ', "\n",
            'REPLACE(SUBSTRING(createdTime, 1, 10), "-", "/") as "Date?",', "\n",
            'REPLACE(SUBSTRING(createdTime, 12, 5), "-", "/") as "Time?"', "\n",
            'FROM weaving_facebook_link', "\n",
            'WHERE LENGTH(message) > 0 ', "\n",
            'AND LENGTH(description) > 0', "\n",
            'AND LENGTH(caption) > 0', "\n",
            'AND link not like "%bible%" > 0', "\n",
            'GROUP BY link ', "\n",
            'ORDER BY createdTime DESC, hid_count_ desc', "\n",
            'LIMIT 0,50'
        )
    WHERE per_id = 52;

    # Update default perspective
    UPDATE weaving_perspective
    SET per_value =
        CONCAT(
            '# Show administration panel', "\n",
            'SELECT per_id as id,', "\n",
            'per_name as name,', "\n",
            'SUBSTRING(per_hash, 1, 7) as hash,', "\n",
            'per_value AS pre_sql,', "\n",
            'per_name as hid_name,', "\n",
            'per_value as btn_sql,', "\n",
            'CONCAT(	"https://## FILL HOSTNAME ##/perspective/",', "\n",
            ' 	SUBSTRING(per_hash, 1, 7)', "\n",
            ') AS lnk_share_link', "\n",
            'FROM weaving_perspective', "\n",
            'WHERE per_name IS NOT NULL', "\n",
            'AND per_value LIKE "# SHOW%"', "\n",
            'ORDER BY per_id DESC'
        )
    WHERE per_type = 0 AND per_id = 29;

6) Known issues
--------------------------------

Running WeavingTheWebApiBundle test suite sequentially fails with following message:

    posix_isatty(): could not use stream of type 'MEMORY'

A solution consists in running the tests in parallel:

    ant -f build.xml phpunit-isolated

Try to avoid values containing '&' for environment variables to be injected to shell scripts
(or figure out how to escape properly special characters when using capistrano).
