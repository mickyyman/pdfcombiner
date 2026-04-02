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

    protected ?string $emailRecipient = null;

    protected ?string $emailSubject = null;

    protected ?string $emailMessage = null;

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

    public function emailRecipient(string $recipient): static
    {
        $this->emailRecipient = $recipient;

        return $this;
    }

    public function getEmailRecipient(): string
    {
        return $this->emailRecipient ?? config('pdf-combiner.email_recipient', '');
    }

    public function emailSubject(string $subject): static
    {
        $this->emailSubject = $subject;

        return $this;
    }

    public function getEmailSubject(): string
    {
        return $this->emailSubject ?? config('pdf-combiner.email_subject', 'Merged PDF');
    }

    public function emailMessage(string $message): static
    {
        $this->emailMessage = $message;

        return $this;
    }

    public function getEmailMessage(): string
    {
        return $this->emailMessage ?? config('pdf-combiner.email_message', 'Please find the merged PDF attached.');
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
