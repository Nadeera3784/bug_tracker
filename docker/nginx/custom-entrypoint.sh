#!/bin/sh
set -e

handle_error_entrypoint() {
  echo "Error in custom-entrypoint.sh. Sleeping for ${EXIT_WAIT_TIME} seconds then will exit ..."
  sleep ${EXIT_WAIT_TIME}
  echo "Exiting..."
}
trap 'handle_error_entrypoint' ERR

echo "Running custom-entrypoint.sh"

EXIT_WAIT_TIME=5

if [ ! -z "${CONTAINER_ERROR_WAIT_TIME}" ]; then
  EXIT_WAIT_TIME=${CONTAINER_ERROR_WAIT_TIME}
fi

export EXIT_WAIT_TIME=${EXIT_WAIT_TIME}

#Printing env variables for debuggging purposes
#Note: this should be enabled only for debugging purposes
if [ "${CONTAINER_PRINT_ENV_VARIABLES}" == "true" ]; then
  echo "Environment variables: "
  printenv
fi

echo "Changing ownership of /var/www/storage"
chown -R nginx:nginx /var/www/storage
chmod -R ugo+rw /var/www/storage

# Welcome Message
echo "Default startup script with $@"
exec "$@"

#DEBUG ONLY
#sleep 300s