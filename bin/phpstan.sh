#!/usr/bin/env bash

BASEDIR=$(cd `dirname $0` && pwd)
PLUGIN_DIR=$(dirname "$BASEDIR")
SHOPWARE_DIR=$(dirname "$PLUGIN_DIR")/../../

/usr/bin/php7.2 "$SHOPWARE_DIR"/vendor/bin/phpstan analyse -c "$PLUGIN_DIR"/phpstan.neon;

