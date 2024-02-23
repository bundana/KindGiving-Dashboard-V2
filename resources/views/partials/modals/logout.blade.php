    <div class="modal fade" tabindex="-1" id="user-logout-confirmed">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <a href="#" class="close" data-bs-dismiss="modal"><em class="icon ni ni-cross"></em></a>
                <div class="modal-body modal-body-lg text-center">
                    <div class="nk-modal">
                        <em class="nk-modal-icon icon icon-circle icon-circle-xxl ni ni-cross  bg-danger"></em>
                        <h4 class="nk-modal-title">Do you Want to Logout ?</h4>
                        <div class="nk-modal-text">
                            <div class="caption-text">By logging out, you will be signed out of the application. " +
                                "Make sure to save any unsaved changes before proceeding.</div>
                        </div>
                        <div class="nk-modal-action">
                            <form action="{{ route('logout') }}" method="post">
                                @csrf
                                <button type="submit" class="btn btn-lg btn-mw btn-primary"
                                    data-bs-dismiss="modal">Logout!</button>
                            </form>
                        </div>
                    </div>
                </div><!-- .modal-body -->
            </div>
        </div>
    </div>
