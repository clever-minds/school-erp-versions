<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class ExpenseExport implements FromCollection, WithHeadings, WithEvents
{
    protected $data;
    protected $totalDebit;
    protected $totalCredit;

    public function __construct(Collection $data, $totalDebit, $totalCredit)
    {
        $this->data = $data;
        $this->totalDebit = $totalDebit;
        $this->totalCredit = $totalCredit;
    }

    public function collection()
    {
        $rows = collect($this->data);

        // ✅ TOTAL ROW
        $rows->push([
            'no' => '',
            'title' => 'TOTAL',
            'category' => '',
            'debit' => number_format($this->totalDebit, 2),
            'credit' => number_format($this->totalCredit, 2),
            'transaction_date' => '',
        ]);

        return $rows;
    }

    public function headings(): array
    {
        return [
            'No',
            'Title',
            'Category',
            'Debit',
            'Credit',
            'Date'
        ];
    }

    // 🎨 HIGHLIGHT TOTAL ROW
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                // 🔢 Total rows count (+1 for heading)
                $totalRowNumber = $this->data->count() + 2;

                $event->sheet->getStyle("A{$totalRowNumber}:F{$totalRowNumber}")
                    ->applyFromArray([
                        'font' => [
                            'bold' => true,
                        ],
                        'fill' => [
                            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                            'startColor' => [
                                'argb' => 'FFFDE9D9', // light yellow
                            ],
                        ],
                    ]);
            },
        ];
    }
}
