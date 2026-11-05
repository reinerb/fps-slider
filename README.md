# FPS Slider

A WordPress plugin that adds an image carousel via the `[fps-slider]` shortcode, built on [Embla Carousel](https://www.embla-carousel.com/). Images and settings are managed with ACF Pro.

## Requirements

- WordPress 6.3+
- PHP 8.0+
- [Advanced Custom Fields PRO](https://www.advancedcustomfields.com/pro/). The plugin won't load without it and shows an admin notice instead.

## Installation

1. Download `fps-slider.zip` from the [latest release](https://github.com/reinerb/fps-slider/releases/latest). Use this file, not GitHub's "Source code" archive, which doesn't include the built assets or PHP dependencies.
2. In WordPress, go to **Plugins → Add New → Upload Plugin**, upload the zip and activate.

After that, new releases appear in **Plugins** and **Dashboard → Updates** like any other plugin update. Sites check every 12 hours, or immediately via the plugin's **Check for updates** link on the Plugins screen.

Optionally, on hosts where many sites share an IP address, add a GitHub token to `wp-config.php` to avoid GitHub's rate limit of 60 API requests per hour per IP. Use a fine-grained personal access token with public read-only access:

```php
define('FPS_SLIDER_GITHUB_TOKEN', 'github_pat_...');
```

## Usage

1. **Settings → FPS Slider**
   - **Pages:** choose which pages get slider fields.
   - **Slider defaults:** autoplay, time per slide, slide or fade transition, aspect ratio, and previous/next buttons.
2. **Edit one of those pages**
   - Add images to **Slider images**. Set alt text on each image in the media library.
   - Optionally switch on **Customize settings** to override the defaults for that page.
3. **Add `[fps-slider]`** anywhere in the page's content.

The slider pauses on hover and keyboard focus, has a pause button whenever autoplay is on, and starts paused for visitors who prefer reduced motion.

### Styling

Override these CSS custom properties on `.fps-slider` from your theme:

| Property | Default |
| --- | --- |
| `--fps-slider-control-size` | `2.5rem` |
| `--fps-slider-control-fg` | `#fff` |
| `--fps-slider-control-bg` | `rgb(0 0 0 / 0.55)` |
| `--fps-slider-control-bg-hover` | `rgb(0 0 0 / 0.8)` |

The aspect ratio (`--fps-slider-aspect-ratio`) is set inline from the slider's settings, so overriding it from CSS needs `!important`.

## Development

Requires Node.js with [pnpm](https://pnpm.io/) and [Composer](https://getcomposer.org/).

```sh
composer install
pnpm install
pnpm dev        # rebuild dist/ on change, with source maps
pnpm build      # production build
pnpm typecheck
```

`dist/` and `vendor/` are git-ignored. The release workflow builds them into the release zip.

A git checkout never offers to update itself. This stops WordPress from overwriting your working copy with a release.

| Path | Contents |
| --- | --- |
| `fps-slider.php` | Plugin header and bootstrap |
| `src/Plugin.php` | Checks for ACF Pro, then registers everything else |
| `src/Shortcode.php` | `[fps-slider]` markup |
| `src/Assets.php` | Enqueues `dist/` only on requests that render the shortcode |
| `src/Options.php` | Settings → FPS Slider options page |
| `src/Fields.php` | Per-page fields: images and setting overrides |
| `src/Settings.php` | Setting definitions, and resolving defaults vs. per-page overrides |
| `src/Updater.php` | Updates from GitHub releases |
| `assets/` | TypeScript and CSS source, built by Vite |

## Releasing

Releases are built and published by GitHub Actions (`.github/workflows/release.yml`) when a version tag is pushed.

1. **Bump the version** in the `Version:` line of the header in `fps-slider.php`, using [semantic versioning](https://semver.org/): `1.0.1` for fixes, `1.1.0` for new features, `2.0.0` for breaking changes. This header is the only version that matters; the one in `package.json` isn't used.

2. **Commit and push** to `main`:

   ```sh
   git commit -am "release 1.0.1"
   git push
   ```

3. **Tag the commit and push the tag.** The tag must be `v` plus the exact header version, or the workflow fails:

   ```sh
   git tag v1.0.1
   git push origin v1.0.1
   ```

4. **Watch the run** on the repo's [Actions tab](https://github.com/reinerb/fps-slider/actions). It checks the tag against the header, type-checks, builds the assets, installs production PHP dependencies, and publishes a release with `fps-slider.zip` attached and generated release notes.

5. **Sites pick it up** within 12 hours, or immediately via **Check for updates**.

### If the release workflow fails

Fix the problem and commit it. Then delete the tag, and the release if one was created, and tag again:

```sh
gh release delete v1.0.1 --yes        # only if the release was created
git tag -d v1.0.1
git push origin :refs/tags/v1.0.1
git tag v1.0.1
git push origin v1.0.1
```

Only do this for a release no site has installed yet. Once a version is out, release a fix as a new version instead.
