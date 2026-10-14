<?php

namespace Modules\Sacco\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Modules\Sacco\Services\DataImportService;

class DataImportController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', '2fa']);
        $this->middleware(['permission:sacco.import.create']);
    }

    public function index()
    {
        $batches = DB::table('client_import_batches')
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();
        return theme_view('sacco::import.index', compact('batches'));
    }

    public function store(Request $request, DataImportService $service)
    {
        $request->validate([
            'import_type' => 'required|in:members',
            'file' => 'required|file|mimes:csv,txt,xlsx,xls',
        ]);

        // Allow long-running imports
        set_time_limit(600);
        ini_set('memory_limit', '512M');

        $file = $request->file('file');
        // Store to a permanent path so the extension is preserved
        $stored = $file->store('imports', 'local');
        $path = storage_path('app/' . $stored);
        $extension = $file->getClientOriginalExtension();

        $filename = $file->getClientOriginalName();
        $result = $service->importMembers($path, $extension, $filename);

        // Clean up stored file
        \Illuminate\Support\Facades\Storage::disk('local')->delete($stored);

        return theme_view('sacco::import.result', compact('result'));
    }

    public function unseedConfirm(string $batchId)
    {
        $batch = DB::table('client_import_batches')->where('batch_id', $batchId)->firstOrFail();
        $count = DB::table('clients')->where('import_batch_id', $batchId)->count();
        return theme_view('sacco::import.unseed_confirm', compact('batch', 'count'));
    }

    public function unseed(string $batchId, DataImportService $service)
    {
        $result = $service->unseedBatch($batchId);
        return theme_view('sacco::import.unseed_result', compact('result'));
    }

    public function downloadTemplate(string $type, string $format = 'xlsx')
    {
        $templates = [
            'individual' => [
                'filename' => 'individual_members_template',
                'headers'  => [
                    'client_type', 'first_name', 'last_name', 'gender', 'dob',
                    'id_number', 'passport_number', 'mobile', 'alternative_mobile', 'email',
                    'address', 'postal_code', 'branch_id', 'external_id', 'bmu_name',
                    'employment_status', 'employer_name', 'workplace_address', 'employer_phone', 'occupation',
                ],
                'sample' => [
                    'individual', 'Jane', 'Wanjiku', 'female', '1992-03-10',
                    '23456789', '', '254712000001', '254787000001', 'jane@example.com',
                    'P.O. Box 200-00200', '00200', '1', 'EXT002', 'Kisumu Sacco Group',
                    'employed', 'ABC Company', 'Industrial Area', '0722000001', 'Accountant',
                ],
            ],
            'organisation' => [
                'filename' => 'organisation_members_template',
                'headers'  => [
                    'client_type', 'first_name', 'mobile', 'alternative_mobile', 'email',
                    'address', 'postal_code', 'branch_id', 'external_id', 'bmu_name',
                    'registration_number', 'registration_date', 'kra_pin',
                    'nature_of_business', 'business_category', 'membership_number', 'date_of_admission',
                ],
                'sample' => [
                    'organisation', 'Wanjiku Traders Ltd', '254712000002', '254787000002', 'info@wanjiku.co.ke',
                    'P.O. Box 300-00300', '00300', '1', 'EXT003', 'Kisumu Sacco Group',
                    'CPR/2020/12345', '2020-01-15', 'A001234567B',
                    'Retail Trade', 'SME', 'MBR0001', '2020-02-01',
                ],
            ],
        ];

        if (!isset($templates[$type])) {
            abort(404);
        }

        $tpl = $templates[$type];
        $format = in_array($format, ['xlsx', 'csv']) ? $format : 'xlsx';
        $filename = $tpl['filename'] . '.' . $format;

        if ($format === 'xlsx') {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Bold header row
            $sheet->fromArray([$tpl['headers']], null, 'A1');
            $sheet->fromArray([$tpl['sample']], null, 'A2');

            $headerStyle = [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                           'startColor' => ['argb' => 'FF1F6B3A']],
            ];
            $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($tpl['headers']));
            $sheet->getStyle("A1:{$lastCol}1")->applyFromArray($headerStyle);

            // Auto-width columns
            foreach (range('A', $lastCol) as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            $writer = new Xlsx($spreadsheet);
            return response()->streamDownload(function () use ($writer) {
                $writer->save('php://output');
            }, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Cache-Control' => 'max-age=0',
            ]);
        }

        // CSV
        return response()->streamDownload(function () use ($tpl) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $tpl['headers']);
            fputcsv($handle, $tpl['sample']);
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
