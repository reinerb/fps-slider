<?php
/**
 * Plugin Name:       FPS Slider
 * Description:       Embla carousel via the [fps-slider] shortcode.
 * Version:           1.0.0
 * Requires at least: 6.3
 * Requires PHP:      8.0
 * Update URI:        false
 */

defined('ABSPATH') || exit;

// Fail softly rather than fatally if dependencies haven't been installed.
if (!is_readable(__DIR__ . '/vendor/autoload.php')) {
    add_action('admin_notices', static function (): void {
        if (current_user_can('activate_plugins')) {
            echo '<div class="notice notice-error"><p>'
                . esc_html__('FPS Slider is missing its dependencies. Run composer install in the plugin folder.', 'fps-slider')
                . '</p></div>';
        }
    });

    return;
}

require_once __DIR__ . '/vendor/autoload.php';

(new FpsSlider\Plugin(__FILE__))->boot();
