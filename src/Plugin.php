<?php

namespace FpsSlider;

defined('ABSPATH') || exit;

class Plugin {
    public function __construct(private string $file) {
    }

    public function boot(): void {
        (new Assets($this->file))->register();
        (new Options())->register();
        (new Fields())->register();
        (new Shortcode())->register();
    }
}
