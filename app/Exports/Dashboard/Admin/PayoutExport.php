<?php

namespace App\Exports\Dashboard\Admin;

use App\Models\Payout;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PayoutExport implements FromCollection, WithStyles, WithHeadings, ShouldAutoSize
{

    protected $from;
    protected $to;
    protected $user_id;

    public function __construct($from, $to, $user_id)
    {
        $this->from = $from;
        $this->to = $to;
        $this->user_id = $user_id;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return Payout::where('user_id', $this->user_id)
            ->whereBetween('created_at', [$this->from ?? Carbon::today(), $this->to ?? Carbon::tomorrow()])
            ->get(['id', 'account_number', 'ifsc_code', 'beneficiary_name', 'reference_id', 'status', 'amount', 'created_at', 'updated_at']);
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]]
        ];
    }

    public function headings(): array
    {
        return ["ID", "Account Number", "IFSC", "Beneficiary Name", "Reference ID", "Status", "Amount", "Created At", "Updated At"];
    }
}
