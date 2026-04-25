<?php

namespace App\Providers;

use App\Models\Post;
use Carbon\Carbon;
use Illuminate\Support\ServiceProvider;
use TomatoPHP\FilamentDocs\Facades\FilamentDocs;
use TomatoPHP\FilamentDocs\Services\Contracts\DocsVar;

class DocsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        FilamentDocs::register([
            DocsVar::make('$POST_TITLE')
                ->label('Post Title')
                ->model(Post::class)
                ->column('title'),
            DocsVar::make('$POST_TYPE')
                ->label('Post Type')
                ->model(Post::class)
                ->column('type'),
            DocsVar::make('$SELECTED_TIME')
                ->label('SELECTED TIME')
                ->value(fn() => Carbon::now()->subDays(10)->translatedFormat('D-M-Y')),
        ]);
    }
}
