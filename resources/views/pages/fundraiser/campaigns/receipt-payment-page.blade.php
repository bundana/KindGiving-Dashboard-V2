@extends('partials.menus.base')
@section('content')
    <!-- content @s
        -->

    <div class="nk-content ">
        <div class="container-fluid">
            <div class="nk-content-inner">
                <div class="nk-content-body">

                    <div>
                        <div class="nk-block-head nk-block-head-sm">
                            <div class="nk-block-between">
                                <div class="nk-block-head-content">
                                    <h3 class="nk-block-title page-title">{{ count($donations) }} Unpaid Receipts</h3>
                                </div><!-- .nk-block-head-content -->
                                <div class="nk-block-head-content">
                                    <ul class="nk-block-tools g-4">
                                        <li style="margin: 2px" class="nk-block-tools-opt">
                                            <a  href="{{ route('manager.create-donation-receipt') }}"
                                               class="btn btn-icon btn-secondary d-md-none"><em
                                                    class="icon ni ni-download-cloud"></em></a>
                                            <a  href="{{ route('manager.create-donation-receipt') }}"
                                               class="btn btn-secondary d-none d-md-inline-flex"><em
                                                    class="icon ni ni-download-cloud"></em><span>Export</span></a>
                                        </li>
                                        <li style="margin: 2px" class="nk-block-tools-opt">
                                            <a  href="{{ route('manager.create-donation-receipt') }}"
                                               class="btn btn-icon btn-primary d-md-none"><em
                                                    class="icon ni ni-plus"></em></a>
                                            <a  href="{{ route('manager.create-donation-receipt') }}"
                                               class="btn btn-primary d-none d-md-inline-flex"><em
                                                    class="icon ni ni-plus"></em><span>Add Receipt</span></a>
                                        </li>
                                    </ul>

                                </div><!-- .nk-block-head-content -->
                            </div><!-- .nk-block-between -->
                        </div><!-- .nk-block-head -->
                        <div class="nk-block">
                            <div class="card card-stredtch" wire:ignore>
                                <div class="card-inner-group">
                                    <div class="nk-tb-list is-separate is-medium mb-3">


                                    </div>
                                </div>
                                <form action="{{route('manager.remove-donation-receipt-payment') }}" method="post">
                                    @csrf
                                    <div class="nk-tb-list is-separate is-medium mb-3">
                                        <div class="nk-tb-item nk-tb-head">
                                            <div class="nk-tb-col"><span>Ref</span></div>
                                            <div class="nk-tb-col "><span>Name</span></div>
                                            <div class="nk-tb-col "><span>Amount GH₵ </span></div>

                                        </div><!-- .nk-tb-item -->
                                        @foreach ($donations as $donation)
                                            <div class="nk-tb-item">
                                                <div class="nk-tb-col">
                                                <span class="tb-lead"><a
                                                        href="#">#{{ $donation->donation_ref }}</a></span>
                                                </div>
                                                <div class="nk-tb-col">
                                                    <span class="tb-sub">{{ $donation->donor_name }}</span>
                                                </div>
                                                <div class="nk-tb-col ">
                                                    <span class="tb-lead">{{ $donation->amount }}</span>
                                                </div>

                                                <div class="nk-tb-col nk-tb-col-tools">
                                                    <ul class="nk-tb-actions gx-1">
                                                        <li class="nk-tb-action-hiddden">
                                                            <button type="submit"
                                                                    class="btn btn-icon btn-trigger btn-tooltip"
                                                                    name="remove_donation_ref"
                                                                    value="{{ $donation->donation_ref }}">
                                                                <em class="icon ni ni-trash"></em>
                                                            </button>
                                                        </li>

                                                    </ul>
                                                </div>
                                            </div><!-- .nk-tb-item -->
                                        @endforeach
                                    </div><!-- .nk-tb-list -->
                                </form>
                                <form action="{{route('manager.pay-donation-receipt-post') }}" method="post"> @csrf
                                    <table style="margin: 10px; font-size: 20px; font-weight: bold">
                                        <tfoot>
                                        <tr>
                                            <td colspan="2"></td>
                                            <td colspan="2">Total receipts</td>
                                            <td>{{count($donations)}}</td>
                                        </tr>
                                        <tr>
                                            <td colspan="2"></td>
                                            <td colspan="2">Grand Total</td>
                                            <td>GH₵{{$totalAmount}}</td>
                                        </tr>
                                        <tr>
                                            <td colspan="2"></td>
                                            <td colspan="2"></td>
                                            <td>


                                            </td>
                                        </tr>
                                        </tfoot>
                                    </table>
                                    <div class="col-md-7">
                                        <button type="submit" class="btn btn-secondary" style="margin: 15px">Pay Now
                                        </button>
                                    </div>
                                </form>
                            </div><!-- .nk-block -->


                        </div>
                        @push('js')
                            <script>
                                document.addEventListener("DOMContentLoaded", function () {
                                    const checkAll = document.getElementById('checkAll');
                                    const donationCheckboxes = document.querySelectorAll('.donation-checkbox');

                                    checkAll.addEventListener('click', function () {
                                        donationCheckboxes.forEach(function (checkbox) {
                                            checkbox.checked = checkAll.checked;
                                        });
                                    });

                                    donationCheckboxes.forEach(function (checkbox) {
                                        checkbox.addEventListener('change', function () {
                                            checkAll.checked = [...donationCheckboxes].every(function (cb) {
                                                return cb.checked;
                                            });
                                        });
                                    });
                                });


                                document.addEventListener('DOMContentLoaded', function () {
                                    let selectedDonations = [];

                                    console.log(selectedDonations);

                                    function submitForm() {
                                        if (selectedDonations.length === 0) {
                                            toastr.clear();
                                            NioApp.Toast('<h5>Error</h5><p>Please select at least one receipt.</p>', 'error', {
                                                position: 'top-right'
                                            });
                                            return;
                                        }

                                        // Store selected donation references in Laravel session
                                        fetch('{{ route('manager.store-unpaid-donation-receipt') }}', {
                                            method: 'POST',
                                            headers: {
                                                'Content-Type': 'application/json',
                                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                            },
                                            body: JSON.stringify({
                                                donations: selectedDonations
                                            })
                                        })
                                            .then(response => {
                                                if (!response.ok) {
                                                    toastr.clear();
                                                    NioApp.Toast('<h5>Error</h5><p>Error processing request</p>', 'error', {
                                                        position: 'top-right'
                                                    });
                                                }
                                                return response.json();
                                            })
                                            .then(data => {
                                                if (data.success) {
                                                    // Redirect to the payment page
                                                    window.location.href = '{{ route('manager.donation-receipt-payment') }}';
                                                } else {
                                                    NioApp.Toast('<h5>Error</h5><p>Error storing selected donations.</p>', 'error', {
                                                        position: 'top-right'
                                                    });
                                                }
                                            })
                                            .catch(error => {
                                                toastr.clear();
                                                NioApp.Toast('<h5>Error</h5><p>Error processing request</p>', 'error', {
                                                    position: 'top-right'
                                                });
                                                console.error('Fetch error:', error);

                                            });
                                    }

                                    // Attach event listener to checkboxes
                                    const checkboxes = document.querySelectorAll('.checkbox');
                                    checkboxes.forEach(checkbox => {
                                        checkbox.addEventListener('change', function () {
                                            const donationRef = this.id;
                                            if (this.checked) {
                                                selectedDonations.push(donationRef);
                                            } else {
                                                selectedDonations = selectedDonations.filter(ref => ref !== donationRef);
                                            }
                                        });
                                    });

                                    // Attach event listener to the "Mark as Paid" button
                                    const markAsPaidBtn = document.getElementById('markAsPaidBtn');
                                    markAsPaidBtn.addEventListener('click', submitForm);
                                });


                            </script>
                        @endpush


                    </div>
                </div>
            </div>
        </div>
@endsection
