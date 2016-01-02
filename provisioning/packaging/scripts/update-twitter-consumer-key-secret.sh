#!/bin/bash

# replace consumer key in export environment variables script
sed -e "s/SYMFONY__API__TWITTER__CONSUMER_KEY=\"_\"/SYMFONY__API__TWITTER__CONSUMER_KEY=\""$CONSUMER_KEY"\"/g" \
config/export-development-environment-variables.sh.dist > /tmp/export-development-environment-variables.sh.tpl

# replace consumer secret in export environment variables script
sed -e "s/SYMFONY__API__TWITTER__CONSUMER_SECRET=\"_\"/SYMFONY__API__TWITTER__CONSUMER_SECRET=\""$CONSUMER_SECRET"\"/g" \
/tmp/export-development-environment-variables.sh.tpl > config/export-development-environment-variables.sh

# replace script in vagrant box
vagrant ssh -c 'cp /vagrant/config/export-development-environment-variables.sh /var/deploy/devobs/current/config'

# replace consumer key in pool template
sed -e "s/SYMFONY__API__TWITTER__CONSUMER_KEY]='_'/SYMFONY__API__TWITTER__CONSUMER_KEY]='"$CONSUMER_KEY"'/g" \
provisioning/roles/php/templates/pool.conf.tpl > provisioning/roles/php/templates/pool.conf.tpl_

# replace consumer secret in pool template
sed -e "s/SYMFONY__API__TWITTER__CONSUMER_SECRET]='_'/SYMFONY__API__TWITTER__CONSUMER_SECRET]='"$CONSUMER_SECRET"'/g" \
provisioning/roles/php/templates/pool.conf.tpl_ > provisioning/roles/php/templates/pool_twitter.conf.tpl

# remove temporary file
rm provisioning/roles/php/templates/pool.conf.tpl_

# run the tasks tagged with "php"
PHP_FPM_POOL=_twitter vendor/devobs/bin/ansible-playbook --user=vagrant --connection=ssh --timeout=30 --limit=all \
--inventory-file=provisioning/inventories/dev provisioning/playbook.yml  \
--private-key=./.vagrant/machines/default/virtualbox/private_key -t php
