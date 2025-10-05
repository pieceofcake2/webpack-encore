<?php

App::uses('AppHelper', 'View/Helper');

/**
 * @property HtmlHelper $Html
 */
class EncoreHelper extends AppHelper
{
    public $helpers = ['Html'];

    /**
     * Strip the request's webroot prefix from the beginning of each path if present.
     *
     * @param array $paths
     * @return array
     */
    protected function stripWebrootPrefix(array $paths): array
    {
        $prefix = $this->request->webroot;
        return array_map(function ($path) use ($prefix) {
            return str_starts_with($path, $prefix) ? '/'.substr($path, strlen($prefix)) : $path;
        }, $paths);
    }

    /**
     * Full filesystem path to Encore's entrypoints.json.
     */
    protected string $entrypointsPath;

    /**
     * Cached entrypoints data.
     */
    protected array $entrypoints = [];

    /**
     * Full filesystem path to Encore's manifest.json.
     */
    protected string $manifestPath;

    /**
     * Cached manifest data.
     */
    protected array $manifest = [];

    /**
     * Constructor: sets up entrypointsPath and manifestPath from Configure, or defaults.
     */
    public function __construct(View $View, $settings = array())
    {
        parent::__construct($View, $settings);

        // Read full filesystem paths from Configure or default to WWW_ROOT + build
        $this->entrypointsPath = Configure::read('WebpackEncore.entrypointsPath')
            ?: WWW_ROOT . 'build/entrypoints.json';
        $this->manifestPath = Configure::read('WebpackEncore.manifestPath')
            ?: WWW_ROOT . 'build/manifest.json';
        // Eagerly load both
        $this->loadEntrypoints();
        $this->loadManifest();
    }

    /**
     * Load and cache the entrypoints.json data.
     *
     * @throws RuntimeException
     */
    protected function loadEntrypoints(): void
    {
        if ($this->entrypoints) {
            return;
        }

        $fullPath = $this->entrypointsPath;
        if (!file_exists($fullPath)) {
            throw new RuntimeException("Encore entrypoints file not found: {$fullPath}");
        }
        $json = file_get_contents($fullPath);
        $data = json_decode($json, true);
        if (JSON_ERROR_NONE !== json_last_error() || !isset($data['entrypoints'])) {
            throw new RuntimeException('Invalid Encore entrypoints.json format');
        }
        $this->entrypoints = $data['entrypoints'];
    }

    /**
     * Load and cache the manifest.json data.
     *
     * @throws RuntimeException
     */
    protected function loadManifest(): void
    {
        if ($this->manifest) {
            return;
        }
        $fullPath = $this->manifestPath;
        if (!file_exists($fullPath)) {
            throw new RuntimeException("Encore manifest file not found: {$fullPath}");
        }
        $json = file_get_contents($fullPath);
        $data = json_decode($json, true);
        if (JSON_ERROR_NONE !== json_last_error() || !is_array($data)) {
            throw new RuntimeException('Invalid Encore manifest.json format');
        }
        $this->manifest = $data;
    }

    /**
     * Render <link> tags for the given entry's CSS files.
     */
    public function entryLinkTags(string $entryName, array $options = [])
    {
        $this->loadEntrypoints();
        if (empty($this->entrypoints[$entryName]['css'])) {
            return '';
        }
        // Resolve and return all CSS tags in one call
        $options = array_merge(['inline' => false], $options);
        $resolvedList = $this->entrypoints[$entryName]['css'];
        $resolvedList = $this->stripWebrootPrefix($resolvedList);
        return $this->Html->css(
            $resolvedList,
            $options
        );
    }

    /**
     * Render <script> tags for the given entry's JS files.
     */
    public function entryScriptTags(string $entryName, array $options = [])
    {
        $this->loadEntrypoints();
        if (empty($this->entrypoints[$entryName]['js'])) {
            return '';
        }
        // Default defer => true and inline => false unless overridden
        $options = array_merge(['defer' => true, 'inline' => false], $options);
        // Resolve and return all JS tags in one call
        $resolvedList = $this->entrypoints[$entryName]['js'];
        $resolvedList = $this->stripWebrootPrefix($resolvedList);
        return $this->Html->script(
            $resolvedList,
            $options
        );
    }

    /**
     * Resolve a single asset path via manifest.json.
     * E.g. asset('build/images/logo.png') => '/build/images/logo.3eed42.png'.
     *
     * @param string $assetPath Logical asset path as key in manifest (e.g. 'build/images/logo.png')
     * @return string Web-accessible path (prefixed with '/')
     */
    public function asset($assetPath)
    {
        // manifest was already loaded in constructor
        if (isset($this->manifest[$assetPath])) {
            $resolved = $this->manifest[$assetPath];
        } else {
            // if not found, use original
            $resolved = $assetPath;
        }
        // Ensure leading slash for web path
        return $resolved;
    }

    /**
     * Render an <img> tag for a given image asset by resolving through manifest.json.
     *
     * @param string $assetPath Logical asset path (e.g. 'build/images/cake_logo.png')
     * @param array  $htmlAttributes Additional HTML attributes for the <img> tag
     * @return string
     */
    public function image(string $assetPath, array $htmlAttributes = []): string
    {
        // Resolve via manifest
        $resolved = $this->asset($assetPath);
        // Delegate to HtmlHelper
        return $this->Html->image($resolved, $htmlAttributes);
    }

    /**
     * Render <link> tags for one or more CSS assets by resolving through manifest.json.
     *
     * @param array|string $assetPaths Logical asset path or array of paths
     * @param array  $options   Additional options for HtmlHelper::css
     * @return mixed
     */
    public function css(array|string $assetPaths, array $options = [])
    {
        // Normalize to array
        $paths = (array)$assetPaths;
        $resolvedList = [];
        foreach ($paths as $path) {
            $resolvedList[] = $this->asset($path);
        }
        $resolvedList = $this->stripWebrootPrefix($resolvedList);
        // Delegate to HtmlHelper once with the array of resolved paths
        return $this->Html->css($resolvedList, $options);
    }

    /**
     * Render <script> tags for one or more JS assets by resolving through manifest.json.
     *
     * @param array|string $assetPaths Logical asset path or array of paths
     * @param array  $options   Additional options for HtmlHelper::script
     * @return mixed
     */
    public function script(array|string $assetPaths, array $options = [])
    {
        // Normalize to array
        $paths = (array)$assetPaths;
        $resolvedList = [];
        foreach ($paths as $path) {
            $resolvedList[] = $this->asset($path);
        }
        $resolvedList = $this->stripWebrootPrefix($resolvedList);
        // Delegate to HtmlHelper once with the array of resolved paths
        return $this->Html->script($resolvedList, $options);
    }
}
