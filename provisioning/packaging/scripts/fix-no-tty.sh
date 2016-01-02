#!/bin/bash

action=fixed_no_tty
if [ ! -e ~root/.$action ]
then
    sudo sed -i 's|mesg n|tty -s \&\& mesg n|' /root/.profile
    sudo touch ~root/.$action
fi

