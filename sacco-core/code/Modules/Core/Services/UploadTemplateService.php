<?php

namespace Modules\Core\Services;

use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class DynamicArrayExport implements FromArray, WithHeadings
{
    protected $headings;
    protected $sample;

    public function __construct(array $headings, array $sample = [])
    {
        $this->headings = $headings;
        $this->sample = $sample;
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function array(): array
    {
        return $this->sample ? [$this->sample] : [];
    }
}

class UploadTemplateService
{
    public function get(string $key): ?array
    {
        return config("core.upload_templates.{$key}");
    }

    public function hasDownload(string $key): bool
    {
        $template = $this->get($key);

        return !empty($template['headers']);
    }

    public function download(string $key, string $format = 'csv')
    {
        $template = $this->get($key);
        if (!$template || empty($template['headers'])) {
            abort(404);
        }

        if ($format === 'xlsx') {
            $filename = !empty($template['filename']) ? str_replace('.csv', '.xlsx', $template['filename']) : str_replace('.', '_', $key) . '.xlsx';
            return Excel::download(
                new DynamicArrayExport($template['headers'], $template['sample'] ?? []),
                $filename
            );
        }

        $filename = $template['filename'] ?? str_replace('.', '_', $key) . '.csv';

        return response()->streamDownload(function () use ($template) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $template['headers']);
            if (!empty($template['sample'])) {
                fputcsv($handle, $template['sample']);
            }
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
