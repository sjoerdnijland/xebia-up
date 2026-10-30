<?php

namespace App\Twig;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * versioned('/css/app.css') → '/css/app.css?v=1730282400'
 *
 * Appends the file's modification time so browsers fetch a fresh copy
 * after every deploy instead of serving a stale cached stylesheet/script.
 */
class AssetVersionExtension extends AbstractExtension
{
    /** @var array<string,string> */
    private array $cache = [];

    public function __construct(
        #[Autowire('%kernel.project_dir%/public')]
        private readonly string $publicDir,
    ) {
    }

    public function getFunctions(): array
    {
        return [new TwigFunction('versioned', $this->versioned(...))];
    }

    public function versioned(string $path): string
    {
        if (isset($this->cache[$path])) {
            return $this->cache[$path];
        }
        $file = $this->publicDir . '/' . ltrim($path, '/');
        $mtime = is_file($file) ? filemtime($file) : false;

        return $this->cache[$path] = $mtime ? $path . '?v=' . $mtime : $path;
    }
}
