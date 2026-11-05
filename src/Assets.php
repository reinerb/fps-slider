<?php

namespace FpsSlider;

defined('ABSPATH') || exit;

use WP_Post;

/**
 * Registers the Vite build output (dist/) and enqueues it only on requests
 * that actually render the [fps-slider] shortcode.
 *
 * - On singular views whose content contains the shortcode, assets are
 *   enqueued during wp_enqueue_scripts so the stylesheet lands in <head>.
 * - Anywhere else (widgets, template parts, archives, block themes), assets
 *   are enqueued as the shortcode renders. The script is a footer script, and
 *   WordPress prints late-enqueued styles in the footer.
 */
final class Assets
{
    public const HANDLE = 'fps-slider';

    private const SHORTCODE = 'fps-slider';
    private const DIST = 'dist/';

    public function __construct(private string $pluginFile)
    {
    }

    public function register(): void
    {
        add_action('wp_enqueue_scripts', [$this, 'enqueueForQueriedPost']);
        add_filter('do_shortcode_tag', [$this, 'enqueueForShortcode'], 10, 2);
    }

    public function enqueueForQueriedPost(): void
    {
        $post = get_queried_object();

        if (is_singular() && $post instanceof WP_Post && has_shortcode($post->post_content, self::SHORTCODE)) {
            $this->enqueue();
        }
    }

    /**
     * Pass-through filter: leaves the shortcode output untouched.
     */
    public function enqueueForShortcode(string $output, string $tag): string
    {
        if ($tag === self::SHORTCODE) {
            $this->enqueue();
        }

        return $output;
    }

    public function enqueue(): void
    {
        $this->registerAssets();

        wp_enqueue_style(self::HANDLE);
        wp_enqueue_script(self::HANDLE);
    }

    /**
     * Idempotent, so enqueue() is safe to call before wp_enqueue_scripts
     * (block themes render content before wp_head).
     */
    private function registerAssets(): void
    {
        if (!wp_style_is(self::HANDLE, 'registered') && ($css = $this->asset('fps-slider.css'))) {
            wp_register_style(self::HANDLE, $css['url'], [], $css['version']);
        }

        if (!wp_script_is(self::HANDLE, 'registered') && ($js = $this->asset('fps-slider.js'))) {
            wp_register_script(self::HANDLE, $js['url'], [], $js['version'], [
                'in_footer' => true,
                'strategy'  => 'defer',
            ]);
        }
    }

    /**
     * @return array{url: string, version: string}|null Null if the file hasn't been built.
     */
    private function asset(string $file): ?array
    {
        $path = plugin_dir_path($this->pluginFile) . self::DIST . $file;

        if (!is_file($path)) {
            return null;
        }

        return [
            'url'     => plugin_dir_url($this->pluginFile) . self::DIST . $file,
            'version' => (string) filemtime($path),
        ];
    }
}