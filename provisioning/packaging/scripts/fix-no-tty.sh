#!/bin/bash

sed -i '/tty/!s/mesg n/tty -s \\&\\& mesg n/' /root/.profile
