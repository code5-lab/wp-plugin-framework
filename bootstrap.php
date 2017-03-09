<?php

/**
 * Ensure this is only ran once.
 */
if (defined('COMPONTO_FRAMEWORK')) {
    return;
}
define('COMPONTO_FRAMEWORK', microtime(true));

$instance = \Com\Componto\Framework\Application::getInstance();