#!/bin/bash

# Setup the the box. This runs as root
apt-get -y update

apt-get -y install \
curl \
libyaml-dev \
python-dev \
python-virtualenv

cat > ~vagrant/ansible.txt <<DEPENDENCIES
ansible==1.9.4
ecdsa==0.13
Jinja2==2.8
MarkupSafe==0.23
paramiko==1.16.0
pycrypto==2.6.1
PyYAML==3.11
wheel==0.24.0
DEPENDENCIES

# Download dependencies
pip install -r ~vagrant/ansible.txt

mkdir -p /tmp/packer-provisioner-ansible-local

mkdir -p /tmp/packer-provisioner-ansible-local/vars

chmod 777 /tmp/packer-provisioner-ansible-local

chmod 777 /tmp/packer-provisioner-ansible-local/vars
