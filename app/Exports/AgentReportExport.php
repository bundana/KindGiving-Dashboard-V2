<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class AgentReportExport implements FromCollection, WithHeadings
{
    protected $agentReports;

    public function __construct($agentReports)
    {
        $this->agentReports = $agentReports;
    }

    public function collection()
    {
        return collect($this->agentReports);
    }

    public function headings(): array
    {
        return [
            'Agent Name',
            'Total Donations',
            'Commission',
        ];
    }
}
