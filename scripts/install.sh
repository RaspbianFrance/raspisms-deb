#!/bin/bash
set -e
trap error_report ERR

trap error_report ERR

### Functions ###
error_report () {
    printf "\n\nError on line $(caller)\n\n";
    exit 1
}

apt update -y
apt install -y build-essential devscripts debhelper

