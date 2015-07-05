#!/bin/bash

echo "#########################################"
echo "`date` => Debut de la mise a jour"
echo "#########################################"


# Stop service
/etc/init.d/codendi stop
/sbin/service httpd stop

# Upgrade packages
yum -y update tuleap*

# Apply data upgrades
/usr/lib/forgeupgrade/bin/forgeupgrade --config=/etc/codendi/forgeupgrade/config.ini update

# Restart service
/sbin/service httpd start
/etc/init.d/codendi start

echo "#########################################"
echo "`date` => Fin de la mise a jour"
echo "#########################################"
