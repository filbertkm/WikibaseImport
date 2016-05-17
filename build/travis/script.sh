#! /bin/bash

set -x

cd ../wiki/tests/phpunit
php phpunit.php --group WikibaseImport
