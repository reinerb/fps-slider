<?php

namespace FpsSlider;

defined('ABSPATH') || exit;

use Extended\ACF\Fields\PostObject;
use Extended\ACF\Fields\Tab;
use Extended\ACF\Location;

/**
 * Settings → FPS Slider: which pages get slider fields, and the default
 * slider settings. Requires ACF Pro (options pages).
 */
final class Options
{
    public const SLUG  = 'fps-slider';
    public const PAGES = 'fps_slider_pages';

    public function register(): void
    {
        add_action('acf/init', [$this, 'registerPage']);
    }

    public function registerPage(): void
    {
        if (!function_exists('acf_add_options_page')) {
            return;
        }

        acf_add_options_page([
            'page_title'  => 'FPS Slider',
            'menu_title'  => 'FPS Slider',
            'menu_slug'   => self::SLUG,
            'parent_slug' => 'options-general.php',
            'capability'  => 'manage_options',
            'redirect'    => false,
        ]);

        register_extended_field_group([
            'title'    => 'FPS Slider settings',
            'location' => [Location::where('options_page', self::SLUG)],
            'fields'   => [
                Tab::make('Pages'),
                PostObject::make('Pages with a slider', self::PAGES)
                    ->helperText('The slider fields appear on these pages. Add the <code>[fps-slider]</code> shortcode to a page’s content to display its slider.')
                    ->postTypes(['page'])
                    ->multiple()
                    ->nullable()
                    ->format('id'),
                Tab::make('Slider defaults'),
                ...(new Settings())->fields(),
            ],
        ]);
    }

    public static function url(): string
    {
        return admin_url('options-general.php?page=' . self::SLUG);
    }

    /**
     * @return list<int>
     */
    public static function pageIds(): array
    {
        if (!function_exists('get_field')) {
            return [];
        }

        $ids = get_field(self::PAGES, 'option') ?: [];

        return array_values(array_filter(array_map('intval', (array) $ids)));
    }
}
