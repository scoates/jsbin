<?php
// database settings
define('JSBIN_DB_NAME', 'jsbin');
define('JSBIN_DB_USER', 'jsbin');  // Your MySQL username
define('JSBIN_DB_PASSWORD', ''); // ...and password
define('JSBIN_DB_HOST', 'localhost');  // 99% chance you won't need to change this value

// change this to suit your offline detection
define('JSBIN_OFFLINE', is_dir('/Users/'));

define('JSBIN_VERSION', JSBIN_OFFLINE ? 'debug' : '2.1.0');

define('JSBIN_GA_TRACKER_ID', 'UA-1656750-13');
