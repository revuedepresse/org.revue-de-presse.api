Weaving The Web Experiments
========================

1) Sitemap
--------------------------------

* Documentation - ([documentation]({{ baseurl }}/doc/readme))
* JSON store inspection - ([documentation]({{ baseurl }}/0/1/1))
* Social Media Dashboard - ([dashboard]({{ baseurl }}/documents))
* Sign in with Twitter - ([sign in]({{ baseurl }}/twitter/connect))

2) Tests
--------------------------------

From project directory, run following command:

    phpunit -c ./app

Testing controllers and matching routes requires updating ``basic_auth_pattern`` parameter in ``app/config/config_test.yml``

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

4) Known issues
--------------------------------

Running WeavingTheWebApiBundle test suite sequentially fails with following message:

    posix_isatty(): could not use stream of type 'MEMORY'

A solution consists in running the tests in parallel:

    ant -f build.xml phpunit-isolated
