<?php
/*
Plugin Name: City/State-Based SEO Structure Generator and Handler
Description: Generates City and State posts with custom format and handles City or State name based shortcodes.
Version: 1.2.1
Author: Roy Koljonen
*/

// Prevent direct file access
if (!defined('ABSPATH')) {
    exit;
}

require_once plugin_dir_path(__FILE__) . 'generate-city-state-pages.php';

require_once plugin_dir_path(__FILE__) . 'state-page-handler.php';
