# CakePHP 2.x Webpack Encore Plugin

[![GitHub License](https://img.shields.io/github/license/pieceofcake2/webpack-encore?label=License)](LICENSE)
[![Packagist Version](https://img.shields.io/packagist/v/pieceofcake2/webpack-encore?label=Packagist)](https://packagist.org/packages/pieceofcake2/webpack-encore)
![PHP](https://img.shields.io/packagist/dependency-v/pieceofcake2/webpack-encore/php?logo=php&logoColor=%23FFFFFF&label=PHP&labelColor=%23777BB4&color=%23FFFFFF)
![CakePHP](https://img.shields.io/packagist/dependency-v/pieceofcake2/webpack-encore/pieceofcake2/cakephp?logo=cakephp&logoColor=%23FFFFFF&label=CakePHP&labelColor=%23D33C43&color=%23FFFFFF)
[![Tests](https://img.shields.io/github/actions/workflow/status/pieceofcake2/webpack-encore/tests.yml?label=Tests)](https://github.com/pieceofcake2/webpack-encore/actions/workflows/tests.yml)
[![Codecov](https://img.shields.io/codecov/c/gh/pieceofcake2/webpack-encore?label=Coverage)](https://codecov.io/gh/pieceofcake2/webpack-encore)

This helper allows you to integrate Symfony Webpack Encore–built assets (CSS, JS, images) into a CakePHP 2 application. It reads `entrypoints.json` and `manifest.json` to resolve fingerprinted asset filenames and output proper `<link>`, `<script>`, or `<img>` tags.

## Installation

0. **Install plugin**:
   ```bash
   composer require pieceofcake2/webpack-encore
   ```

1. **Build your Encore assets** (e.g., with Symfony Encore or webpack). From your project root, run:
   ```bash
   npm install
   npm run encore dev
   ```
   This should generate:
    - `webroot/build/entrypoints.json`
    - `webroot/build/manifest.json`
    - The `webroot/build/` directory containing compiled `.js`, `.css`, and image files.

2. **Copy `EncoreHelper.php` into your CakePHP app** under:
   ```text
   app/View/Helper/EncoreHelper.php
   ```

3. **Load the helper in your controller** (e.g., `AppController`):
   ```php
   // app/Controller/AppController.php
   App::uses('Controller', 'Controller');

   class AppController extends Controller {
       public $helpers = ['Html', 'WebpackEncore.Encore'];
   }
   ```
   Now you can use `$this->Encore` in your views.

---

## Configuration (Configure)

By default, the helper expects:
- `entrypoints.json` at `webroot/build/entrypoints.json`
- `manifest.json` at `webroot/build/manifest.json`

If you store these files in different locations, add the following to `app/Config/bootstrap.php`:

```php
// app/Config/bootstrap.php

// Full filesystem path to entrypoints.json
Configure::write('WebpackEncore.entrypointsPath', WWW_ROOT . 'build/entrypoints.json');
// Full filesystem path to manifest.json
Configure::write('WebpackEncore.manifestPath',    WWW_ROOT . 'build/manifest.json');
```

Replace the paths accordingly if they differ.

---

## Helper Methods

### 1. `asset($assetPath)`

Resolves a single logical asset path (as a key in `manifest.json`) to the final fingerprinted URL. Returns a web-accessible path (including leading slash).

```php
// Given manifest.json contains:
// {
//   "build/images/logo.png": "/build/images/logo.3eed42.png"
// }

echo $this->Encore->asset('build/images/logo.png');
// Outputs: /build/images/logo.3eed42.png
```

### 2. `image($assetPath, array $htmlAttributes = [])`

Renders an `<img>` tag for a given image asset by resolving through `manifest.json`.

```php
// Outputs: <img src="/build/images/cake_logo.3eed42.png" alt="CakePHP" />
echo $this->Encore->image('build/images/cake_logo.png', ['alt' => 'CakePHP']);
```

### 3. `css($assetPaths, array $options = [])`

Renders one or more `<link>` tags for CSS assets. The first argument can be a string or an array of strings. Each path is resolved via `manifest.json`, then passed to `HtmlHelper::css()`.

```php
// Single CSS file
echo $this->Encore->css('build/app.css', ['inline' => false]);

// Multiple CSS files
echo $this->Encore->css([
    'build/vendors.3d4e5f.css',
    'build/app.abc123.css'
], ['inline' => false]);
```

### 4. `script($assetPaths, array $options = [])`

Renders one or more `<script>` tags for JavaScript assets. Accepts a string or an array of strings. Each is resolved via `manifest.json`, then passed to `HtmlHelper::script()`.

```php
// Single JS file
echo $this->Encore->script('build/app.js', ['block' => true]);

// Multiple JS files
echo $this->Encore->script([
    'build/runtime.0a1b2c.js',
    'build/vendors.3d4e5f.js',
    'build/app.123abc.js'
], ['block' => true]);
```

### 5. `entryLinkTags($entryName, array $options = [])`

Reads `entrypoints.json` and renders `<link>` tags for all CSS files associated with the given entry (e.g., `app`, `dashboard`).

```php
// entrypoints.json example:
// {
//   "entrypoints": {
//     "app": {
//       "css": ["/build/vendors.3d4e5f.css","/build/app.abc123.css"],
//       "js": ["/build/runtime.0a1b2c.js","/build/vendors.3d4e5f.js","/build/app.123abc.js"]
//     }
//   }
// }

echo $this->Encore->entryLinkTags('app', ['inline' => false]);
// Outputs two <link> tags for the app entry's CSS files
```

### 6. `entryScriptTags($entryName, array $options = [])`

Reads `entrypoints.json` and renders `<script>` tags for all JS files associated with the given entry.

```php
echo $this->Encore->entryScriptTags('app', ['block' => true]);
// Outputs three <script> tags for the app entry's JS files
```


## Example Layout Integration

In `app/View/Layouts/default.ctp`:

```php
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?php echo $this->fetch('title'); ?></title>

    <!-- Load all CSS for the 'app' entry -->
    <?php echo $this->Encore->entryLinkTags('app', ['inline' => false]); ?>
</head>
<body>
    <header>
        <!-- Render an image via manifest -->
        <?php echo $this->Encore->image('build/images/cake_logo.png', ['alt' => 'CakePHP']); ?>
    </header>

    <div class="content">
        <?php echo $this->fetch('content'); ?>
    </div>

    <footer>
        <!-- Footer content -->
    </footer>

    <!-- Load all JS for the 'app' entry -->
    <?php echo $this->Encore->entryScriptTags('app', ['block' => true]); ?>
</body>
</html>
```

- `<head>`: `entryLinkTags('app')` outputs `<link>` tags for all CSS associated with the `app` entry.
- `<body>`: `entryScriptTags('app')` outputs `<script>` tags for all JS associated with the `app` entry.
- The `image()` method resolves and renders an `<img>` tag for an image asset.


## Notes

- Always rebuild your assets (run `npm run encore dev` or `npm run encore production`) after changing frontend code to update `entrypoints.json` and `manifest.json`.
- This helper expects the paths in both JSON files to start with `/build/...`. If you use a different directory, adjust the JSON outputs or change `Configure::write('Encore.entrypointsPath', ...)` and `Configure::write('Encore.manifestPath', ...)` accordingly.
- The helper loads and caches both JSON files once in its constructor for performance. Subsequent calls will not re-read the files.


## License

This plugin is released under the MIT License. You are free to adapt and redistribute it in compliance with the license.

