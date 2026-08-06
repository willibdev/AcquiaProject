#!/bin/sh
#
# Cloud Hook: Import config
#
# Run database updates and import config in all environments post code deploy.
# `drush deploy` runs updatedb, config:import, deploy hooks, and a single
# cache:rebuild in the correct order with one Drupal bootstrap.

# Map the script inputs to convenient names.
site=$1
target_env=$2
drush_alias=$site'.'$target_env

if drush @$drush_alias status 2>/dev/null | grep -q "Successful"; then
  # Only run deployment tasks if Drupal is installed.
  drush @$drush_alias deploy --yes
fi
