# Installation

## Development environment

Install the latest version of VirtualBox from 
[https://www.virtualbox.org/](https://www.virtualbox.org/)

Install the latest version of Ansible from 
[http://docs.ansible.com/ansible/intro_installation.html](http://docs.ansible.com/ansible/intro_installation.html)

Install the latest version of Vagrant from 
[https://www.vagrantup.com/downloads.html](https://www.vagrantup.com/downloads.html)

Install provisioning roles using Ansible galaxy command

```
ansible-galaxy install -r provisioning/requirements.yml
```

From the root directory of the project, run the following command to set up a development environment

```
vagrant up --provider=virtualbox
```

The execution of the command might take a little while as a pre-packaged virtual machine needs to be downloaded  
before any required configuration files and services can be installed.

## Application deployment

Install Ruby using [rvm](https://rvm.io/)

Install Capistrano

```
bundle install --path vendor/bundle
```

Deploy the application in the Vagrant box

```
source ./config/export-development-environment-variables && cap development deploy
```

The first deployment might take a couple of minutes before npm and composer caches have been populated.  

## Known issues

**Encountering "Could not authenticate against github.com" error message when installing PHP dependencies?**

Follow instructions from [https://getcomposer.org/doc/articles/troubleshooting.md#api-rate-limit-and-oauth-tokens](https://getcomposer.org/doc/articles/troubleshooting.md#api-rate-limit-and-oauth-tokens)
These instructions have to be followed from within the vagrant box.

Fill the "github.com" value in `provisioning/files/auth.json`

```
# Prepare OAuth authorization configuration for composer
export TOKEN='grab your token from github.com before copying it here'
sed -e 's/"github.com": ""/"github.com": "'$TOKEN'"/' provisioning/files/auth.json.dist > provisioning/files/auth.json 

# Copy authorization configuration file to composer directory in vagrant home directory
COMPOSER_AUTH=provisioning/files/auth.json vagrant provision --provision-with=file
```



