<?php

namespace FpsSlider;

defined('ABSPATH') || exit;

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;
use YahnisElsts\PluginUpdateChecker\v5p7\Vcs\Api;

/**
 * Updates from GitHub releases of the public reinerb/fps-slider repo.
 *
 * Installs the built fps-slider.zip attached to each release by the release
 * workflow, never the source archive (which lacks dist/ and vendor/).
 *
 * No configuration needed. Optionally, a token raises GitHub's API rate limit
 * from 60 requests/hour per IP, which only matters on hosts where many sites
 * share an IP:
 *
 *   define('FPS_SLIDER_GITHUB_TOKEN', 'github_pat_...');
 *
 * Use a fine-grained personal access token with public read-only access.
 */
final class Updater
{
    private const REPOSITORY = 'https://github.com/reinerb/fps-slider/';
    private const TOKEN      = 'FPS_SLIDER_GITHUB_TOKEN';
    private const ASSET      = '/^fps-slider\.zip$/';

    public function __construct(private string $pluginFile)
    {
    }

    public function register(): void
    {
        // Never offer to replace a development checkout with a release zip.
        if (is_dir(dirname($this->pluginFile) . '/.git')) {
            return;
        }

        $checker = PucFactory::buildUpdateChecker(self::REPOSITORY, $this->pluginFile, 'fps-slider');
        $checker->getVcsApi()->enableReleaseAssets(self::ASSET, Api::REQUIRE_RELEASE_ASSETS);

        $token = defined(self::TOKEN) ? constant(self::TOKEN) : '';

        if (is_string($token) && $token !== '') {
            $checker->setAuthentication($token);
        }
    }
}
