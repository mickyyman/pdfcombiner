<?php

namespace Mickyyman\PdfCombiner\Pages;

use Filament\Pages\Page;
use Mickyyman\PdfCombiner\PdfCombinerPlugin;

class PdfCombiner extends Page
{
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-document-duplicate';

    protected string $view = 'pdf-combiner::pages.pdf-combiner';

    protected static ?string $title = 'PDF Combiner';

    protected static ?string $slug = 'pdf-combiner';

    public string $excludePhrase = '';

    public string $emailRecipient = '';

    public string $emailSubject = '';

    public string $emailMessage = '';

    public static function getNavigationGroup(): ?string
    {
        return PdfCombinerPlugin::get()->getNavigationGroup();
    }

    public static function getNavigationSort(): ?int
    {
        return PdfCombinerPlugin::get()->getNavigationSort();
    }

    public function mount(): void
    {
        $plugin = PdfCombinerPlugin::get();
        $this->excludePhrase  = $plugin->getExcludePhrase();
        $this->emailRecipient = $plugin->getEmailRecipient();
        $this->emailSubject   = $plugin->getEmailSubject();
        $this->emailMessage   = $plugin->getEmailMessage();
    }
}
