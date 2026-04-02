<?php

namespace Mickyyman\PdfCombiner;

use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class PdfCombinerServiceProvider extends PackageServiceProvider
{
    public static string $name = 'pdf-combiner';

    public static string $viewNamespace = 'pdf-combiner';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(static::$name)
            ->hasConfigFile()
            ->hasViews(static::$viewNamespace);
    }

    public function packageBooted(): void
    {
        // Register JS/CSS assets — loaded on every Filament panel page via @filamentScripts.
        // pdf.worker.min.js is NOT registered here; it must be a publicly accessible file
        // (its URL is passed to pdf.js via window.pdfCombiner_workerSrc).
        FilamentAsset::register(
            assets: [
                Js::make('pdf-lib', __DIR__ . '/../resources/dist/pdf-lib.min.js'),
                Js::make('pdfjs', __DIR__ . '/../resources/dist/pdf.min.js'),
                Js::make('pdf-combiner-scripts', __DIR__ . '/../resources/dist/pdf-combiner.js'),
                Css::make('bootstrap-icons', __DIR__ . '/../resources/dist/bootstrap-icons.css'),
            ],
            packageName: 'mickyyman/pdf-combiner',
        );

        // Publish the Web Worker file and icon fonts to the public directory.
        // Users must run: php artisan vendor:publish --tag=pdf-combiner-assets
        $this->publishes([
            __DIR__ . '/../resources/dist/pdf.worker.min.js'    => public_path('vendor/mickyyman/pdf-combiner/pdf.worker.min.js'),
            __DIR__ . '/../resources/dist/bootstrap-icons.css'  => public_path('vendor/mickyyman/pdf-combiner/bootstrap-icons.css'),
            __DIR__ . '/../resources/dist/fonts'                => public_path('vendor/mickyyman/pdf-combiner/fonts'),
        ], 'pdf-combiner-assets');
    }
}
