<?php

namespace Modules\Core\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\Core\Services\UploadTemplateService;

class UploadTemplateController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', '2fa']);
    }

    public function download(string $key, UploadTemplateService $service)
    {
        $format = request()->query('format', 'csv');
        return $service->download($key, $format);
    }
}
