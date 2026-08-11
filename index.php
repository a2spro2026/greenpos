<?php

/**
 * Production front controller.
 *
 * Used when the web server document root is this directory (same folder as .env)
 * instead of /public. Forwards the request to Laravel's public entry point.
 */

require __DIR__.'/public/index.php';
