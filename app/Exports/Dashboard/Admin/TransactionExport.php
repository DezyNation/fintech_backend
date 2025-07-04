<?php

namespace App\Exports\Dashboard\Admin;

use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TransactionExport implements FromCollection, WithStyles, WithHeadings, ShouldAutoSize
{
    protected $from;
    protected $to;
    protected $user_id;
    protected $request;

    public function __construct($request, $from, $to, $user_id)
    {
        $this->request = $request;
        $this->from = $from;
        $this->to = $to;
        $this->user_id = $user_id;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return Transaction::adminFiterByRequest($this->request)
            ->whereBetween('created_at', [$this->from ?? Carbon::today(), $this->to ?? Carbon::tomorrow()])
            ->get(['id', 'reference_id', 'service', 'credit_amount',  'debit_amount', 'gst', 'opening_balance', 'closing_balance', 'created_at', 'updated_at']);
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]]
        ];
    }

    public function headings(): array
    {
        return ["ID", "Reference ID", "Service", "Credit Amount", "Debit Amount", "GST", "Opening Balance", "Closing Balance", "Created At", "Updated At"];
    }
}
