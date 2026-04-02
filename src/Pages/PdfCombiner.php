<?php

namespace Mickyyman\PdfCombiner\Pages;

use Filament\Pages\Page;
use Mickyyman\PdfCombiner\PdfCombinerPlugin;

class PdfCombiner extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-duplicate';

    protected static string $view = 'pdf-combiner::pages.pdf-combiner';

    protected static ?string $title = 'PDF Combiner';

    protected static ?string $slug = 'pdf-combiner';

    public string $excludePhrase = '';

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
        $this->excludePhrase = PdfCombinerPlugin::get()->getExcludePhrase();
    }
}
