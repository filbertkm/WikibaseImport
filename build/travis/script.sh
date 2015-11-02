#! /bin/bash

set -x

cd ../wiki/tests/phpunit
php phpunit.php -c ../../extensions/WikibaseImport/phpunit.xml.dist
