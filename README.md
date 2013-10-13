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

3) Requirements
--------------------------------

Follow [dotdeb instructions](http://www.dotdeb.org/instructions/) to add new repositories to your apt source list

    deb http://packages.dotdeb.org wheezy all
    deb-src http://packages.dotdeb.org wheezy all

    deb http://packages.dotdeb.org wheezy-php55 all
    deb-src http://packages.dotdeb.org wheezy-php55 all

    wget http://www.dotdeb.org/dotdeb.gpg
    cat dotdeb.gpg | sudo apt-key add -

    apt-get install redis-server php5-redis php5-apcu

3) Commands
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

Rabbitmq

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

User management

    # Promote user
    app/console fos:user:promote --super gordon

    # Activate user
    app/console fos:user:activate

4) Known issues
--------------------------------

Running WeavingTheWebApiBundle test suite sequentially fails with following message:

    posix_isatty(): could not use stream of type 'MEMORY'

A solution consists in running the tests in parallel:

    ant -f build.xml phpunit-isolated

Try to avoid values containing '&' for environment variables to be injected to shell scripts
(or figure out how to escape properly special characters when using capistrano).
