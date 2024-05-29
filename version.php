<?php

defined('MOODLE_INTERNAL') || die();

$plugin->version   = 2024052901;        // The current plugin version (Date: YYYYMMDDXX).
$plugin->requires  = 2023100400;        // Requires this Moodle version.
$plugin->component = 'datafield_harpiainteraction';  // Full name of the plugin (used for diagnostics)
$plugin->maturity = MATURITY_ALPHA;

$plugin->dependencies = [
    'local_harpiaajax' => 2024052901,
];
