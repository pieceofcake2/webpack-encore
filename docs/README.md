# Webpack Encore Documentation

pieceofcake2/webpack-encore provides a view helper that integrates
[Symfony Webpack Encore](https://github.com/symfony/webpack-encore)-built assets (CSS, JS,
images) into a CakePHP 2 application. It reads `entrypoints.json` and `manifest.json` to
resolve fingerprinted asset filenames and output proper `<link>`, `<script>` or `<img>` tags.

- [Installation](#installation)
- [Configuration](#configuration)
- [Helper Methods](#helper-methods)
- [Example Layout](#example-layout)
- [Notes](#notes)

## Installation

1. **Install the plugin** with [Composer](https://getcomposer.org/):

   ```bash
   composer require pieceofcake2/webpack-encore
   ```

   The plugin has the `cakephp-plugin` type, so it is installed into your plugins directory
   by `composer/installers`. Set `installer-paths` in your application's `composer.json` so
   it is installed into the right place:

   | Layout | `installer-paths` |
   | --- | --- |
   | Modern (`config/`, `src/`, `templates/`, `plugins/`) | `"plugins/{$name}/": ["type:cakephp-plugin"]` |
   | Legacy (`app/Config/`, `app/Plugin/`) | `"app/Plugin/{$name}/": ["type:cakephp-plugin"]` |

   Load the plugin in `config/bootstrap.php` (legacy layout: `app/Config/bootstrap.php`).
   If you already use `CakePlugin::loadAll()`, this is not necessary:

   ```php
   CakePlugin::load('WebpackEncore');
   ```

2. **Build your Encore assets** into the `build/` directory of your webroot
   (`webroot/build/`, legacy layout: `app/webroot/build/`). From your project root, run:

   ```bash
   npm install
   npm run encore dev
   ```

   This should generate:

   - `webroot/build/entrypoints.json`
   - `webroot/build/manifest.json`
   - the compiled `.js`, `.css` and image files in `webroot/build/`

3. **Load the helper** in your controller, e.g. `src/Controller/AppController.php` (legacy
   layout: `app/Controller/AppController.php`):

   ```php
   App::uses('Controller', 'Controller');

   class AppController extends Controller
   {
       public $helpers = ['Html', 'WebpackEncore.Encore'];
   }
   ```

   Now you can use `$this->Encore` in your views.

## Configuration

By default, the helper expects:

- `entrypoints.json` at `WWW_ROOT . 'build/entrypoints.json'`
- `manifest.json` at `WWW_ROOT . 'build/manifest.json'`

If you store these files in different locations, set their full filesystem paths in
`config/bootstrap.php` (legacy layout: `app/Config/bootstrap.php`):

```php
// Full filesystem path to entrypoints.json
Configure::write('WebpackEncore.entrypointsPath', WWW_ROOT . 'build/entrypoints.json');
// Full filesystem path to manifest.json
Configure::write('WebpackEncore.manifestPath', WWW_ROOT . 'build/manifest.json');
```

## Helper Methods

### `asset($assetPath)`

Resolves a single logical asset path (a key in `manifest.json`) to the final fingerprinted URL.
Paths that are not in the manifest are returned unchanged.

```php
// Given manifest.json contains:
// {
//   "build/images/logo.png": "/build/images/logo.3eed42.png"
// }

echo $this->Encore->asset('build/images/logo.png');
// Outputs: /build/images/logo.3eed42.png
```

### `image($assetPath, array $htmlAttributes = [])`

Renders an `<img>` tag for an image asset resolved through `manifest.json`.

```php
// Outputs: <img src="/build/images/cake_logo.3eed42.png" alt="CakePHP" />
echo $this->Encore->image('build/images/cake_logo.png', ['alt' => 'CakePHP']);
```

### `css($assetPaths, array $options = [])`

Renders one or more `<link>` tags for CSS assets. The first argument can be a string or an
array of strings. Each path is resolved via `manifest.json`, then passed to
`HtmlHelper::css()` with the options.

```php
// Single CSS file
echo $this->Encore->css('build/app.css');

// Multiple CSS files
echo $this->Encore->css([
    'build/vendors.css',
    'build/app.css',
]);
```

### `script($assetPaths, array $options = [])`

Renders one or more `<script>` tags for JavaScript assets. Accepts a string or an array of
strings. Each path is resolved via `manifest.json`, then passed to `HtmlHelper::script()`
with the options.

```php
// Single JS file
echo $this->Encore->script('build/app.js');

// Multiple JS files
echo $this->Encore->script([
    'build/runtime.js',
    'build/vendors.js',
    'build/app.js',
], ['defer' => true]);
```

Like with `HtmlHelper`, pass `['inline' => false]` to append the tags to the `css` or `script`
view block instead of returning them, or `['block' => 'name']` to append them to another block.

### `entryLinkTags($entryName, array $options = [])`

Reads `entrypoints.json` and renders `<link>` tags for all CSS files of the given entry
(e.g. `app`, `dashboard`). By default the tags are appended to the `css` view block; pass
`['inline' => true]` to get them as a string.

```php
// entrypoints.json example:
// {
//   "entrypoints": {
//     "app": {
//       "css": ["/build/vendors.3d4e5f.css", "/build/app.abc123.css"],
//       "js": ["/build/runtime.0a1b2c.js", "/build/vendors.3d4e5f.js", "/build/app.123abc.js"]
//     }
//   }
// }

// Appends two <link> tags for the app entry's CSS files to the css block
$this->Encore->entryLinkTags('app');
```

### `entryScriptTags($entryName, array $options = [])`

Reads `entrypoints.json` and renders `<script defer>` tags for all JS files of the given entry.
By default the tags are appended to the `script` view block; pass `['inline' => true]` to get
them as a string, or `['defer' => false]` to omit `defer`.

```php
// Appends three <script> tags for the app entry's JS files to the script block
$this->Encore->entryScriptTags('app');
```

## Example Layout

In `templates/Layouts/default.ctp` (legacy layout: `app/View/Layouts/default.ctp`):

```php
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?= $this->fetch('title') ?></title>

    <?php
    // Add the CSS and JS of the 'app' entry to the css and script blocks
    $this->Encore->entryLinkTags('app');
    $this->Encore->entryScriptTags('app');
    ?>
    <?= $this->fetch('css') ?>
    <?= $this->fetch('script') ?>
</head>
<body>
    <header>
        <!-- Render an image via manifest -->
        <?= $this->Encore->image('build/images/cake_logo.png', ['alt' => 'CakePHP']) ?>
    </header>

    <div class="content">
        <?= $this->fetch('content') ?>
    </div>
</body>
</html>
```

- `entryLinkTags('app')` adds `<link>` tags for all CSS of the `app` entry to the `css` block.
- `entryScriptTags('app')` adds `<script defer>` tags for all JS of the `app` entry to the
  `script` block. As the scripts are deferred, they can be loaded in `<head>`.
- The `image()` method resolves and renders an `<img>` tag for an image asset.

## Notes

- Rebuild your assets (`npm run encore dev` or `npm run encore production`) after changing
  frontend code to update `entrypoints.json` and `manifest.json`.
- The helper expects the paths in both JSON files to start with `/build/...`. If you use a
  different directory, adjust the JSON outputs or set `WebpackEncore.entrypointsPath` and
  `WebpackEncore.manifestPath` accordingly.
- The helper reads each JSON file when an asset is first requested, and caches it for the rest
  of the request. Loading the helper does not require the files, but rendering an asset before
  the first build throws a `RuntimeException` that names the missing file.
