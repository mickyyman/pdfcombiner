<?php

namespace Mickyyman\PdfCombiner;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Mickyyman\PdfCombiner\Pages\PdfCombiner;

class PdfCombinerPlugin implements Plugin
{
    protected ?string $excludePhrase = null;

    protected ?string $navigationGroup = null;

    protected ?int $navigationSort = null;

    // ------------------------------------------------------------------ //
    //  Factory
    // ------------------------------------------------------------------ //

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }

    // ------------------------------------------------------------------ //
    //  Fluent configuration
    // ------------------------------------------------------------------ //

    /**
     * Override the phrase used to auto-filter pages.
     * Defaults to the value in config/pdf-combiner.php.
     */
    public function excludePhrase(string $phrase): static
    {
        $this->excludePhrase = $phrase;

        return $this;
    }

    public function getExcludePhrase(): string
    {
        return $this->excludePhrase ?? config('pdf-combiner.exclude_phrase', '');
    }

    public function navigationGroup(?string $group): static
    {
        $this->navigationGroup = $group;

        return $this;
    }

    public function getNavigationGroup(): ?string
    {
        return $this->navigationGroup;
    }

    public function navigationSort(?int $sort): static
    {
        $this->navigationSort = $sort;

        return $this;
    }

    public function getNavigationSort(): ?int
    {
        return $this->navigationSort;
    }

    // ------------------------------------------------------------------ //
    //  Plugin contract
    // ------------------------------------------------------------------ //

    public function getId(): string
    {
        return 'pdf-combiner';
    }

    public function register(Panel $panel): void
    {
        $panel->pages([
            PdfCombiner::class,
        ]);
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
