<?php
// Reference only. Generate config.php with Moodle's official installer.
// Never commit or distribute a populated config.php.
unset($CFG);
global $CFG;
$CFG = new stdClass();
$CFG->dbtype = 'mariadb';
$CFG->dblibrary = 'native';
$CFG->dbhost = 'REPLACE_WITH_XNEELO_DATABASE_HOST';
$CFG->dbname = 'REPLACE_WITH_DATABASE_NAME';
$CFG->dbuser = 'REPLACE_WITH_DATABASE_USER';
$CFG->dbpass = 'REPLACE_WITH_DATABASE_PASSWORD';
$CFG->prefix = 'ss_';
$CFG->dboptions = ['dbpersist' => 0, 'dbport' => '', 'dbsocket' => '', 'dbcollation' => 'utf8mb4_unicode_ci'];
$CFG->wwwroot = 'https://REPLACE_WITH_YOUR_DOMAIN';
$CFG->dataroot = '/usr/home/REPLACE_FTP_USER/skyscholar-data';
$CFG->admin = 'admin';
$CFG->directorypermissions = 02770;
$CFG->cookiesecure = true;
$CFG->cookiehttponly = true;
$CFG->disableupdateautodeploy = true;
$CFG->preventexecpath = true;
$CFG->debug = 0;
$CFG->debugdisplay = 0;
$CFG->cronclionly = true;
require_once(__DIR__ . '/lib/setup.php');
