# Installation

## Development environment

Install the latest version of VirtualBox available from [https://www.virtualbox.org/](https://www.virtualbox.org)

Install the latest version of Ansible available from [http://docs.ansible.com/ansible/intro_installation.html](http://docs.ansible.com/ansible/intro_installation.html)

Install the latest version of Vagrant available from [https://www.vagrantup.com/downloads.html](https://www.vagrantup.com/downloads.html)

Install provisioning roles using Ansible galaxy command

```
ansible-galaxy install -r provisioning/requirements.yml
```

From the root directory of the project, run the following command to set up a development environment

```
[COMPOSER_AUTH=provisioning/files/auth.json] vagrant up [--provider=virtualbox]
```

The execution of the command might take a little while as a pre-packaged virtual machine needs to be downloaded  
before any required configuration files and services can be installed.

To prevent hitting the github API rate limit when downloading dependencies, set the `COMPOSER_AUTH` environment variable
containing the path to an authorization configuration file for `Composer`. When this variable has been set,
the corresponding file will be uploaded the virtual machine and it will be copied to composer directory.
When deploying the application with `Capistrano`, `composer` will take benefit of OAuth authentication 
for accessing GitHub API at PHP dependencies installation. 

The `provider` option might need to be passed when the host machine supports other providers than virtualbox.

## Application deployment

Install Ruby using rvm by following instructions from [https://rvm.io](https://rvm.io)

Install Capistrano and other dependencies required to deploy the application to the vagrant box installed before

```
bundle install --path vendor/bundle
```

Deploy the application in the Vagrant box

```
# Copy the export environment variables script first
source ./config/export-development-environment-variables.sh && cap development deploy
```

The first deployment might take a couple of minutes before npm and composer caches have been populated.  

## Known issues

**Are you encountering "Could not authenticate against github.com" error message when installing PHP dependencies?**

Follow instructions from `Composer` documentation available at [https://getcomposer.org/doc/articles/troubleshooting.md#api-rate-limit-and-oauth-tokens](https://getcomposer.org/doc/articles/troubleshooting.md#api-rate-limit-and-oauth-tokens)  

These instructions have to be followed from within the vagrant box.

Fill the `github.com` value in `provisioning/files/auth.json`

```
# Prepare OAuth authorization configuration for composer
export TOKEN='grab your token from github.com before copying it here'
sed -e 's/"github.com": ""/"github.com": "'$TOKEN'"/' provisioning/files/auth.json.dist \
> provisioning/files/auth.json 

# Copy authorization configuration file to composer directory in vagrant home directory
COMPOSER_AUTH=provisioning/files/auth.json vagrant provision --provision-with=file
```

**Are you encountering some issue when provisioning the virtual machine with Ansible?**

Run the provisioning command manually with Ansible

```
ansible-playbook --user=vagrant --connection=ssh --timeout=30 --limit=all \
--inventory-file=provisioning/inventories/dev provisioning/playbook.yml \
--private-key=./.vagrant/machines/default/virtualbox/private_key \
-vvvv
```

Have you destroyed a virtual machine before trying again the command above?
You would need to clean up your `~/.ssh/known_hosts` file still containing a reference 
to the previously identified virtual machine.

A manual SSH connection would provide you with the exact line which might be incriminated

```
ssh -i ./.vagrant/machines/default/virtualbox/private_key vagrant@10.9.8.2 -o IdentitiesOnly=yes
```

Same goes true for connections relying on vagrant port forwarding

```
ssh -i ./.vagrant/machines/default/virtualbox/private_key vagrant@127.0.0.1 -p 2222 -o IdentitiesOnly=yes
```

**Would you like to run manually some provisioning steps again?**

Run shell provisioning

```
vagrant ssh -c 'test -e ~vagrant/.ensure_required_files_exist && rm -f ~vagrant/.ensure_required_files_exist' 
vagrant provision --provision-with=shell
```

Run file provisioning (to replace GitHub OAuth authentication token in composer directory of the virtual machine)

```
COMPOSER_AUTH=provisioning/files/auth.json vagrant provision --provision-with=file 
```

Run Ansible provisioning

```
vagrant provision --provision-with=ansible
```

**How to prevent the provisioning requirements from being installed globally in your development workstation?**

The latest version of `Ansible` can be installed via 
 * [`pip`](https://pip.pypa.io/en/latest/installing/#install-pip) and
 * [`virtualenv`](http://virtualenv.readthedocs.org/en/latest/installation.html).

```
# Create a virtual environment
virtualenv vendor/devobs

# Activate virtual environment
source vendor/devobs/bin/activate

# Download dependencies
pip install -r provisioning/ansible.txt

# Check the installed version of Ansible
vendor/devobs/bin/ansible --version

# Deactivate the virtual environment
deactivate
```

