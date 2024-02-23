<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\View\Component;

class PayDonationReceipts extends Component
{
    /**
     * Create a new component instance.
     */
    public $campaign, $request;
    public function __construct(Request $request)
    {
        $this->campaign = $request->campaign;
        $this->request = $request;
    } 

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.pay-donation-receipts');
    }
}
