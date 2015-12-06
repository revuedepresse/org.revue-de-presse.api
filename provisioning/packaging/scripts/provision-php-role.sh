#/bin/bash

source vendor/devobs/bin/activate

PHP_FPM_POOL=_twitter vendor/devobs/bin/ansible-playbook --user=vagrant --connection=ssh --timeout=30 --limit=all \
--inventory-file=provisioning/inventories/dev provisioning/playbook.yml  \
--private-key=./.vagrant/machines/default/virtualbox/private_key -t php

deactivate
