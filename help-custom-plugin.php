<?php

/**
 * Plugin Name: Help Custom Plugin
 * Description: Custom plugin designed for Help Apoteke, contains ERP sync.
 * Version: 1.0.0
 * Author: Nikola
 */

namespace HelpCustomPlugin;

if (!defined('ABSPATH')) exit;

// Load Composer Autoloader
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

require_once __DIR__ . '/includes/class-external-api-client.php';
require_once __DIR__ . '/includes/class-sync-manager.php';

/**
 * Initialize the plugin
 */
add_action('plugins_loaded', function () {
    // wp_die('Plugin is loaded!');
});
