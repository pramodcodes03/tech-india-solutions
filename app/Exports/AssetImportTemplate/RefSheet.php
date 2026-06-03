<?php

namespace App\Exports\AssetImportTemplate;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\NamedRange;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * One-column reference sheet that feeds an in-cell dropdown on the main
 * Assets sheet. Kept visible (not hidden) so users can see the full
 * available list at a glance — the data-validation formula on the main
 * sheet references this sheet's column A.
 */
class RefSheet implements FromArray, ShouldAutoSize, WithHeadings, WithStyles, WithTitle, WithEvents
{
    public function __construct(
        protected string $title,
        protected string $heading,
        protected array $values,
        protected ?string $definedName = null,
    ) {}

    public function array(): array
    {
        if (empty($this->values)) {
            return [['(none yet)']];
        }
        return array_map(fn ($v) => [$v], $this->values);
    }

    public function headings(): array
    {
        return [$this->heading];
    }

    public function title(): string
    {
        return $this->title;
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4B5563']],
        ]);

        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Lock the reference sheet so users can't accidentally edit
                // it and break the dropdowns. They're free to read it.
                // No password — protection is advisory; users can unlock
                // from Excel's Review tab if they really want to edit.
                $sheet->getProtection()->setSheet(true);

                // Register a workbook-level named range so the main Assets
                // sheet's data-validation formulas can reference it by name.
                // Named ranges survive Google Sheets' .xlsx import cleanly,
                // while raw cross-sheet references often don't.
                if ($this->definedName !== null && ! empty($this->values)) {
                    $lastRow = count($this->values) + 1; // +1 for header row
                    $spreadsheet = $sheet->getParent();
                    $spreadsheet->addNamedRange(
                        new NamedRange(
                            $this->definedName,
                            $sheet,
                            '$A$2:$A$'.$lastRow,
                        )
                    );
                }
            },
        ];
    }
}
