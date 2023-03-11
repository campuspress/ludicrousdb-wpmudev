#!/bin/bash

./tests/bin/install-wp-tests.sh ludicrousdb root password mysql

WP_CORE_DIR=${WP_CORE_DIR-/tmp/wordpress/}
WP_CORE_DIR=$(echo $WP_CORE_DIR | sed "s:/\+$::")

mkdir -p "${WP_CORE_DIR}/wp-content/plugins/ludicrousdb"
cp "${PWD}/ludicrousdb.php" "${WP_CORE_DIR}/wp-content/plugins/ludicrousdb"
cp -r "${PWD}/ludicrousdb" "${WP_CORE_DIR}/wp-content/plugins/ludicrousdb"
cp ludicrousdb/drop-ins/db*.php "${WP_CORE_DIR}/wp-content/"
