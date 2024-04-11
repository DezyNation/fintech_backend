<?php

namespace App\Exports\Dashboard\Admin;

use App\Models\Fund;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class FundRequestExport implements FromCollection, WithStyles, WithHeadings, ShouldAutoSize
{
    protected $from;
    protected $to;
    protected $request;

    public function __construct($from, $to, $request)
    {
        $this->from = $from;
        $this->to = $to;
        $this->request = $request;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return Fund::adminFilterExport($this->request)
            ->join('users as reviewer', 'reviewer.id', '=', 'funds.updated_by')
            ->join('users', 'users.id', '=', 'funds.uuser_id')
            ->whereBetween('created_at', [$this->from ?? Carbon::today(), $this->to ?? Carbon::tomorrow()])
            ->select('fund_requsts.id', 'fund_requsts.transaction_id', 'users.name', 'reviewer.name', 'fund_requsts.status', 'fund_requsts.bank', 'fund_requsts.amount', 'fund_requsts.transaction_date', 'fund_requsts.user_remarks', 'fund_requsts.admin_remarks', 'fund_requsts.created_at', 'fund_requsts.updated_at')
            ->get();
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]]
        ];
    }

    public function headings(): array
    {
        return ["ID", "Transaction ID", "User Name", "Reviewer", "Status", "Bank", "Amount", "Transaction Date", "User Remarks", "Admin Remarks", "Created At", "Upadated At"];
    }
}
