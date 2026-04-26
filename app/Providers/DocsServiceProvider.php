<?php

namespace App\Providers;

use App\Models\CALL\Client;
use App\Models\CALL\Company;
use App\Models\CALL\Employee;
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
            DocsVar::make('$CLIENT_NAME')
                ->label('Client Name')
                ->model(Client::class)
                ->column('name'),
            DocsVar::make('$CLIENT_COMPANY_NAME')
                ->label('Company Name')
                ->model(Client::class)
                ->column('company.name'),
            DocsVar::make('$CLIENT_ADDRESS')
                ->label('Client Address')
                ->model(Client::class)
                ->column('address.name'),
            DocsVar::make('$CLIENT_VAT_NUMBER')
                ->label('Client VAT Number')
                ->model(Client::class)
                ->column('vat_number'),
            DocsVar::make('$CLIENT_COMPANY_NAME')
                ->label('Client Company Name')
                ->model(Client::class)
                ->column('company.name'),
            DocsVar::make('$CLIENT_COMPANY_VAT_NUMBER')
                ->label('Client Company VAT Number')
                ->model(Client::class)
                ->column('company.vat_number'),
            DocsVar::make('$CLIENT_COMPANY_ADDRESS')
                ->label('Company Address')
                ->model(Client::class)
                ->column('company.address.name'),
            DocsVar::make('$EMPLOYEE_NAME')
                ->label('Employee Name')
                ->model(Employee::class)
                ->column('name'),
            DocsVar::make('$EMPLOYEE_COMPANY_TITOLARE')
                ->label('Employee Company Titolare')
                ->model(Employee::class)
                ->column('company.titolare'),
            DocsVar::make('$SELECTED_TIME')
                ->label('SELECTED TIME')
                ->value(fn() => Carbon::now()->subDays(10)->translatedFormat('D-M-Y')),
        ]);
    }
}
