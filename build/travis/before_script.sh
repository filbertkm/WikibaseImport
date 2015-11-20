#! /bin/bash

set -x

originalDirectory=$(pwd)

composer self-update

cd ..

# checkout mediawiki
wget https://github.com/wikimedia/mediawiki/archive/master.tar.gz
tar -zxf master.tar.gz
rm master.tar.gz
mv mediawiki-master wiki

# checkout wikibase
wget https://github.com/wikimedia/mediawiki-extensions-Wikibase/archive/master.tar.gz
tar -zxf master.tar.gz
rm master.tar.gz
mv mediawiki-extensions-Wikibase-master wiki/extensions/Wikibase

cd wiki

if [ $DBTYPE == "mysql" ]
  then
    mysql -e 'CREATE DATABASE its_a_mw;'
fi

composer install --no-dev
php maintenance/install.php --dbtype $DBTYPE --dbuser root --dbname its_a_mw --dbpath $(pwd) --pass nyan TravisWiki admin

cd extensions

cp -r $originalDirectory WikibaseImport

cd WikibaseImport
composer install --dev --no-interaction --prefer-source

cd ../Wikibase
composer install --prefer-source

cd ../..

echo 'error_reporting(E_ALL| E_STRICT);' >> LocalSettings.php
echo 'ini_set("display_errors", 1);' >> LocalSettings.php
echo '$wgShowExceptionDetails = true;' >> LocalSettings.php
echo '$wgDevelopmentWarnings = true;' >> LocalSettings.php
echo '$wgLanguageCode = "en";' >> LocalSettings.php

echo 'require_once __DIR__ . "/extensions/Wikibase/repo/Wikibase.php";' >> LocalSettings.php
echo 'require_once __DIR__ . "/extensions/Wikibase/repo/ExampleSettings.php";' >> LocalSettings.php
echo 'require_once __DIR__ . "/extensions/Wikibase/client/WikibaseClient.php";' >> LocalSettings.php
echo 'require_once __DIR__ . "/extensions/WikibaseImport/WikibaseImport.php";' >> LocalSettings.php
echo '$wgWBClientSettings["siteGlobalID"] = "enwiki";' >> LocalSettings.php

php maintenance/update.php --quick
