<?php

declare(strict_types=1);

namespace Orchestra\Asset;

use Collective\Html\HtmlBuilder;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class Dispatcher
{
    /**
     * Filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * Html builder instance.
     *
     * @var \Collective\Html\HtmlBuilder
     */
    protected $html;

    /**
     * Dependency resolver instance.
     *
     * @var \Orchestra\Asset\DependencyResolver
     */
    protected $resolver;

    /**
     * Public path location.
     *
     * @var string
     */
    protected $path;

    /**
     * Use asset versioning.
     *
     * @var bool
     */
    public $useVersioning = false;

    /**
     * Create a new asset dispatcher instance.
     */
    public function __construct(
        Filesystem $files,
        HtmlBuilder $html,
        DependencyResolver $resolver,
        string $path
    ) {
        $this->files = $files;
        $this->html = $html;
        $this->resolver = $resolver;
        $this->path = $path;
    }

    /**
     * Enable asset versioning.
     */
    public function addVersioning(): void
    {
        $this->useVersioning = true;
    }

    /**
     * Disable asset versioning.
     */
    public function removeVersioning(): void
    {
        $this->useVersioning = false;
    }

    /**
     * Dispatch assets by group.
     */
    public function run(string $group, array $assets = [], ?string $prefix = null): string
    {
        $html = '';

        if (! isset($assets[$group]) || count($assets[$group]) === 0) {
            return $html;
        }

        $prefix === null || $this->path = rtrim($prefix, '/');

        foreach ($this->resolver->arrange($assets[$group]) as $data) {
            $html .= $this->asset($group, $data);
        }

        return $html;
    }

    /**
     * Get the HTML link to a registered asset.
     */
    public function asset(string $group, ?array $asset = null): string
    {
        if (! isset($asset)) {
            return '';
        }

        $asset['source'] = $this->getAssetSourceUrl($asset['source']);

        $html = $this->html->{$group}($asset['source'], $asset['attributes']);

        return $html->toHtml();
    }

    /**
     * Determine if path is local.
     */
    protected function isLocalPath(string $path): bool
    {
        if (Str::startsWith($path, ['https://', 'http://', '//'])) {
            return false;
        }

        return filter_var($path, FILTER_VALIDATE_URL) === false;
    }

    /**
     * Get asset source URL.
     */
    protected function getAssetSourceUrl(string $source): string
    {
        // If the source is not a complete URL, we will go ahead and prepend
        // the asset's path to the source provided with the asset. This will
        // ensure that we attach the correct path to the asset.
        $file = $this->path.'/'.ltrim($source, '/');
        
        if (! $this->isLocalPath($file)) {
            return $file;
        }

        return $this->getAssetSourceUrlWithModifiedTime($source, $file);
    }

    /**
     * Get asset source URL with Modified time.
     */
    protected function getAssetSourceUrlWithModifiedTime(string $source, string $file): string
    {
        if ($this->isLocalPath($source) && $this->useVersioning) {
            // We can only append mtime to locally defined path since we need
            // to extract the file.

            if (! empty($modified = $this->files->lastModified($file))) {
                return "{$source}?{$modified}";
            }
        }

        return $source;
    }
}
