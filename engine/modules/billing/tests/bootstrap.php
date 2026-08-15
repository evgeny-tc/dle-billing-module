<?php

define('DATALIFEENGINE', true);
define('BILLING_MODULE', true);
define('ROOT_DIR', dirname(__DIR__, 4));
define('ENGINE_DIR', ROOT_DIR . '/engine');
define('MODULE_PATH', ENGINE_DIR . '/modules/billing');
define('MODULE_DATA', ENGINE_DIR . '/data/billing');
define('USERPREFIX', 'dle');
define('PREFIX', 'dle');

require_once MODULE_PATH . '/helpers/autoloader.php';
require_once __DIR__ . '/FakeDb.php';
