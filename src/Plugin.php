<?php

namespace FpsSlider;

defined('ABSPATH') || exit;

class Plugin {
    public function __construct(private string $file) {
    }

    public function boot(): void {
        // Registered regardless of ACF, so a fix can still be delivered to a
        // site where the plugin can't load.
        (new Updater($this->file))->register();

        // Late enough to see ACF Pro however it's loaded: as a plugin, a
        // must-use plugin, or bundled in the theme's functions.php.
        add_action('after_setup_theme', [$this, 'load'], 20);
    }

    public function load(): void {
        if (!function_exists('acf_is_pro') || !acf_is_pro()) {
            add_action('admin_notices', [$this, 'missingAcfProNotice']);

            // Swallow the shortcode so visitors don't see "[fps-slider]" as text.
            add_shortcode(Shortcode::TAG, '__return_empty_string');

            return;
        }

        (new Assets($this->file))->register();
        (new Options())->register();
        (new Fields())->register();
        (new Shortcode())->register();
    }

    public function missingAcfProNotice(): void {
        if (!current_user_can('activate_plugins')) {
            return;
        }

        printf(
            '<div class="notice notice-error"><p>%s</p></div>',
            esc_html__('FPS Slider requires Advanced Custom Fields PRO. Install and activate it to use the slider.', 'fps-slider'),
        );
    }
}
