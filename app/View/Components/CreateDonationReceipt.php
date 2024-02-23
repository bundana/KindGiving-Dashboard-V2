<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\View\Component;

class CreateDonationReceipt extends Component
{
    public $totalAmount;
    /**
     * Create a new component instance.
     */
    public function __construct(public Request $request)
    {
        $this->totalAmount = $request->totalAmount;  
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.create-donation-receipt');
    }
}
