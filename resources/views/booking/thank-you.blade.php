@extends('layout.app')

@section('page-styles')
    <style>
        .header {
            padding: 0px;
        }
    </style>
@endsection

@section('content')
<div class="content" style="padding-top:60px;">
    <div class="thx_content_wrapper">
        <div class="thx_block">
            <div class="container">
                <div class="thx_block_title h2_title">
                    {{ __('dictionary.MSG_MSG_THX_PAGE_DYAKUYU_ZA_BRONYUVANNYA_BILETU') }}
                </div>
                <div class="thx_block_subtitle par">
                    {{ __('dictionary.MSG_MSG_THX_PAGE_DANI_VASHOGO_BILETU') }}
                </div>
                <a href="{{ route('auth') }}" class="private_link h4_title blue_btn">
                    <span class="hidden-xs">
                        {{ __('dictionary.MSG_MSG_THX_PAGE_PEREJTI_U_PERSONALINIJ_KABINET') }}
                    </span>
                    <span class="hidden-xxl hidden-xl hidden-lg hidden-md hidden-sm col-xs-12">
                        {{ __('dictionary.MSG_MSG_THX_PAGE_PERSONALINIJ_KABINET') }}
                    </span>
                </a>
            </div>
        </div>
        <div class="txh_image">
            <img src="{{ asset('images/legacy/common/thx_img.png') }}" alt="thanks" class="fit_img">
        </div>
    </div>
</div>
@endsection

@section('page-scripts')
<script>
    $(document).ready(function () {
        // AJAX request to clear session data on page load
        $.ajax({
            type: 'post',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            url: '{{ route("booking.thank-you.clear-session") }}',
            success: function (response) {
                // Handle response
                if (response.data === 'ok') {
                    // Session data successfully cleared
                    console.log('Session data successfully cleared');
                } else {
                    // Error clearing session data
                    console.log('Error clearing session data');
                }
            },
            error: function(xhr, status, error) {
                console.log('Error clearing session data:', error);
            }
        });
    });
</script>
@endsection
