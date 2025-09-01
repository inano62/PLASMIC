<?php

namespace App\Application\Service\Excel;

use Illuminate\Http\UploadedFile;

interface ExcelUserService
{
    public function readForImport(string $content): ?array;
}
