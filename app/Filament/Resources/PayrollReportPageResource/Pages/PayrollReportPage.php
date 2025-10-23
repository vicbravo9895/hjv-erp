<?php

namespace App\Filament\Resources\PayrollReportPageResource\Pages;

use App\Filament\Resources\PayrollReportPageResource;
use Filament\Resources\Pages\Page;

class PayrollReportPage extends Page
{
    protected static string $resource = PayrollReportPageResource::class;

    protected static string $view = 'filament.resources.payroll-report-page-resource.pages.payroll-report-page';
}
