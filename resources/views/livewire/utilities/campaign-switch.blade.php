<div wire:ignore>
    <form wire:submit.prevent="switchCampaign">
        <div class="form-group">
            <label class="form-label">Select campaign</label>
            <div wire:loading>
                Loading...
            </div>
            <div class="form-control-wrap">
                <select class="form-select js-select2" data-search="on" wire:model.live="campaign_id" wire:change="switchCampaign">
                    @foreach ($campaigns as $campaign)
                        <option value="{{ $campaign->campaign_id }}">{{ $campaign->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </form>
</div>

@if (session()->has('message'))
    <div class="alert alert-success">{{ session('message') }}</div>
@endif
