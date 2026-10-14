<?php

namespace Modules\Report\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Nwidart\Modules\Facades\Module;

class ReportController extends Controller
{
    /**
     * ReportController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(['permission:reports.index'])->only(['index','show']);

    }

    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index()
    {
        $data = [];
        $modules = Module::collections();
        foreach ($modules as $module) {
            if (file_exists(Module::getPath($module) . '/' . $module . '/Reports/ReportsLinks.php')) {
                $class = "\\Modules\\" . $module . "\\Reports\\ReportsLinks";
                $class = new $class($data);
                $links = $this->normalizeReportLinks($class->get_links());
                if (!empty($links)) {
                    $data[] = $links;
                }
            }
        }
        return theme_view('report::index', compact('data'));
    }

    /**
     * Normalize module report links to a flat url => label map.
     *
     * @param array $links
     * @return array
     */
    protected function normalizeReportLinks(array $links): array
    {
        if (isset($links['links']) && is_array($links['links'])) {
            $flat = [];
            foreach ($links['links'] as $link) {
                if (!is_array($link) || empty($link['url']) || empty($link['name'])) {
                    continue;
                }
                if (!empty($link['permission']) && !auth()->user()->can($link['permission'])) {
                    continue;
                }
                $flat[$link['url']] = $link['name'];
            }

            return $flat;
        }

        return array_filter($links, function ($value, $key) {
            return is_string($key) && (is_string($value) || is_numeric($value));
        }, ARRAY_FILTER_USE_BOTH);
    }


}
