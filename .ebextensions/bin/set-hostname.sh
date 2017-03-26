#!/bin/bash

readonly INSTANCE_ID=$(curl --silent --location http://169.254.169.254/latest/meta-data/instance-id)
readonly REGION=$(curl --silent --location http://169.254.169.254/latest/dynamic/instance-identity/document | grep region | awk -F\" '{print $4}')
readonly EB_ENV_TAG=$(aws ec2 describe-tags --filters "Name=resource-id,Values=$INSTANCE_ID" "Name=key,Values=elasticbeanstalk:environment-name" --region $REGION --output text | cut -f5)
readonly EB_PUBLIC_IP=$(curl --silent --location http://169.254.169.254/latest/meta-data/public-ipv4)

NEW_HOSTNAME="$EB_ENV_TAG"-"$EB_PUBLIC_IP"

# Set Hostname
sudo hostname "$NEW_HOSTNAME"
echo -e "$NEW_HOSTNAME" | sudo tee /etc/hostname

# Restart newrelic, if present
chkconfig --list newrelic-sysmond &> /dev/null && sudo service newrelic-sysmond restart
