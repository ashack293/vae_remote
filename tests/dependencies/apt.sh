#!/bin/bash
set -e

pkgs=(libantlr3c-dev libzmq3-dev libboost-filesystem-dev libboost-program-options-dev libboost-system-dev libboost-thread-dev libpcre3-dev libmemcached-dev libmysqlcppconn-dev libjemalloc-dev)

# Work from the directory CI will cache
mkdir -p ~/apt-cache
cd ~/apt-cache

# check we have a deb for each package
useCache=false
for pkg in "${pkgs[@]}"; do
    if ! ls | grep "^${pkg}"; then
        useCache=false
    fi
done

set -x

if [ ${useCache} == true ]; then
    sudo dpkg -i *.deb
    exit 0
fi

rm -rf ~/apt-cache/*
sudo add-apt-repository -y ppa:chris-lea/zeromq
sudo apt-get update
sudo apt-get install "${pkgs[@]}"

cp -v /var/cache/apt/archives/*.deb ~/apt-cache
