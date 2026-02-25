<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class PemangkuMatrixExport implements FromView, ShouldAutoSize
{
    private array $data;
    public function __construct(array $data){ $this->data = $data; }

    public function view(): View
    {
        // gunakan blade yang sama strukturnya dengan PDF
        return view('exports.matrix_pemangku_excel', $this->data);
    }
}
