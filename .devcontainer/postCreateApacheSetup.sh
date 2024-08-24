#!/bin/bash
#
source $workspaceFolder/.devcontainer/.env

echo ">>> Apache2 Configuration"
envsubst < $workspaceFolder/.devcontainer/include/001-friendica.conf > /tmp/001-friendica.conf

# Create a self-signed SSL certificate
sudo openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
   -keyout /etc/ssl/private/friendica.key \
   -out /etc/ssl/certs/friendica.crt \
   -subj "/C=US/ST=State/L=City/O=Organization/CN=$ServerAlias" \
   -addext "subjectAltName = DNS:$ServerAlias, DNS:$ServerName"

sudo chmod +rx /etc/ssl/private
sudo chmod 644 /etc/ssl/private/friendica.key
sudo chmod 644 /etc/ssl/certs/friendica.crt

sudo cp /tmp/001-friendica.conf /etc/apache2/sites-available/001-friendica.conf
sudo a2enmod rewrite actions ssl
sudo a2ensite 001-friendica
sudo a2dissite 000-default

echo 'ServerName 127.0.0.1' | sudo tee -a /etc/apache2/apache2.conf

exit 0
