<?php

namespace App\Exports;

use App\Models\Campaigns\Donations;
use App\Models\CampaignsDonations;
use Maatwebsite\Excel\Concerns\FromCollection;

class CampaignDonors implements FromCollection
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return Donations::all();
    }
}
