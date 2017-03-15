<?php

/**
 * Ensure this is only ran once.
 */
if (defined('CODE_FIVE_FRAMEWORK')) {
    return;
}
define('CODE_FIVE_FRAMEWORK', microtime(true));

$instance = \Com\CodeFive\Framework\Application::getInstance();