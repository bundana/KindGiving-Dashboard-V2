<?php

namespace App\Http\Middleware;

use App\Models\Campaigns\SelectedCampaign;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CampaignMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        $user_id = auth()->user()->user_id;
        $user = User::where('user_id', $user_id)->first();
        if (!$user) {
            Auth::logout();
        }
        // Get the currently authenticated user
        // $user = Auth::user();

        // Fetch the selected campaign for the user
        $selectedCampaign = SelectedCampaign::where('user_id', $user->user_id)->first();
        if (!$selectedCampaign) {

        } else {
            redirect('profile');
        }
        // Add the selected campaign to the request for further use
        $request->attributes->add(['selectedCampaign' => $selectedCampaign]);
        $selectedCampaign = $request->attributes->get('selectedCampaign');

        // dd($selectedCampaign);
        return $next($request);
    }
}
