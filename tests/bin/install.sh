#!/bin/bash

DB_HOST=${1:-"localhost"}
DB_PASS=${2:-""}

./tests/bin/install-wp-tests.sh ludicrousdb root "$DB_PASS" $DB_HOST

echo "WordPress set up, setting up LudicrousDB"

WP_CORE_DIR=${WP_CORE_DIR-/tmp/wordpress/}
WP_CORE_DIR=$(echo $WP_CORE_DIR | sed "s:/\+$::")

mkdir -p "${WP_CORE_DIR}/wp-content/plugins/ludicrousdb"
cp "${PWD}/ludicrousdb.php" "${WP_CORE_DIR}/wp-content/plugins/ludicrousdb"
cp -r "${PWD}/ludicrousdb" "${WP_CORE_DIR}/wp-content/plugins/ludicrousdb"
cp ludicrousdb/drop-ins/db*.php "${WP_CORE_DIR}/wp-content/"
