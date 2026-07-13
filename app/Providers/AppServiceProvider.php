<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Generate all URLs (routes, assets, form actions) from APP_URL so the
        // "/public" segment never leaks into links, regardless of how the
        // request was rewritten into the public/ folder.
        if ($root = config('app.url')) {
            URL::forceRootUrl($root);

            // Also derive the scheme from APP_URL. On the production server the
            // site is served over HTTPS, so asset()/url() must emit https links
            // — otherwise browsers block the CSS/JS/images as mixed content and
            // the page renders completely unstyled. Local dev stays on http.
            if (str_starts_with($root, 'https://')) {
                URL::forceScheme('https');
            }
        }
    }
}
