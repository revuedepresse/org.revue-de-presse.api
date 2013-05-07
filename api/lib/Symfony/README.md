Weaving the web Experiments
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

3) Commands
--------------------------------

From project root directory, execute the following commands to transform

    # data collected about GitHub repositories
    php app/console wtw:api:manage:transformation --process_isolation --save [--type=repositories]

    # data collected from personal Facebook newsfeed
    php app/console wtw:api:manage:transformation --process_isolation --save --type=feed

    # data collected from personal Twitter user stream
    php app/console wtw:api:manage:transformation --process_isolation --save --type=user_stream
