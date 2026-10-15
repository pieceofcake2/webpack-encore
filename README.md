# CakePHP 2.x Webpack Encore Plugin

[![GitHub License](https://img.shields.io/github/license/pieceofcake2/webpack-encore?label=License)](LICENSE)
[![Packagist Version](https://img.shields.io/packagist/v/pieceofcake2/webpack-encore?label=Packagist)](https://packagist.org/packages/pieceofcake2/webpack-encore)
![PHP](https://img.shields.io/packagist/dependency-v/pieceofcake2/webpack-encore/php?logo=php&logoColor=%23FFFFFF&label=PHP&labelColor=%23777BB4&color=%23FFFFFF)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%205-brightgreen?label=PHPStan)](https://github.com/pieceofcake2/webpack-encore/actions/workflows/CI.yml)
![CakePHP](https://img.shields.io/packagist/dependency-v/pieceofcake2/webpack-encore/pieceofcake2/cakephp?logo=cakephp&logoColor=%23FFFFFF&label=CakePHP&labelColor=%23D33C43&color=%23FFFFFF)
[![CI](https://img.shields.io/github/actions/workflow/status/pieceofcake2/webpack-encore/CI.yml?label=CI)](https://github.com/pieceofcake2/webpack-encore/actions/workflows/CI.yml)
[![Codecov](https://img.shields.io/codecov/c/gh/pieceofcake2/webpack-encore?label=Coverage)](https://codecov.io/gh/pieceofcake2/webpack-encore)

pieceofcake2/webpack-encore integrates Symfony Webpack Encore-built assets (CSS, JS, images)
into a CakePHP 2 application. Its helper reads `entrypoints.json` and `manifest.json` to
resolve fingerprinted asset filenames and output `<link>`, `<script>` or `<img>` tags.

## Documentation

- Website: https://pieceofcake2.github.io/webpack-encore/
- Markdown: [docs/README.md](docs/README.md) (for AI tools and offline reading)

The Markdown documentation is also included in the Composer package, in the plugin's directory: `plugins/WebpackEncore/docs/README.md` (the location depends on your `installer-paths`). Install the package with `--dev` if you only need it for development.

## Requirements

* PHP 8.0+
* pieceofcake2/cakephp 2.12+

## Installation

```bash
composer require pieceofcake2/webpack-encore
```

Then build your Encore assets and load the `WebpackEncore.Encore` helper.
See [Installation](docs/README.md#installation).

## Contributing

See the [contribution guidelines](https://github.com/pieceofcake2/.github/blob/master/CONTRIBUTING.md).

## License

MIT License. See [LICENSE](LICENSE).
