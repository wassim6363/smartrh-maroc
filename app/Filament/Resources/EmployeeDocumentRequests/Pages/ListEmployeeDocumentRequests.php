<?php

namespace App\Filament\Resources\EmployeeDocumentRequests\Pages;

use App\Filament\Resources\EmployeeDocumentRequests\EmployeeDocumentRequestResource;
use Filament\Resources\Pages\ListRecords;

class ListEmployeeDocumentRequests extends ListRecords
{
    protected static string $resource = EmployeeDocumentRequestResource::class;
}
