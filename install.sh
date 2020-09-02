#!/bin/bash

if test -f /usr/local/bin/localhost; then
    sudo rm /usr/local/bin/localhost
fi

curl -o /usr/local/bin/localhost https://raw.githubusercontent.com/philipnewcomer/localhost/master/builds/localhost

chmod +x /usr/local/bin/localhost

echo "localhost successfully installed to /usr/local/bin/localhost. Installing dependencies..."

localhost install

echo "localhost has been successfully installed."
