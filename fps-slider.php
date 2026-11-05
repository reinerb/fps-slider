<?php
/**
 * Plugin Name: FPS Slider
 * Description: Embla carousel via the [fps-slider] shortcode.
 * Version:     1.0.0
 * Requires PHP: 8.0
 */

defined('ABSPATH') || exit;

require_once __DIR__ . '/vendor/autoload.php';

(new FpsSlider\Plugin(__FILE__))->boot();
