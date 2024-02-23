<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\View\Component;

class AllCampaignReceipts extends Component
{
    /**
     * Create a new component instance.
     */
    public $campaign_id;
    public function __construct(public Request $request)
    {
        $this->request = $request;
        $this->campaign_id = $request->id;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.all-campaign-receipts');
    }
}
