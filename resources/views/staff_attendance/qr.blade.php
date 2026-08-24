@extends('layouts.master')

@section('title')
    {{ __('QR Attendance Generator') }}
@endsection

@section('content')
    <div class="content-wrapper text-center">
        <div class="page-header justify-content-center">
            <h3 class="page-title">
                {{ __('Staff Attendance QR Code') }}
            </h3>
        </div>
        <div class="row justify-content-center">
            <div class="col-md-6 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title mb-4">{{ __('Scan to mark your attendance') }}</h4>
                        
                        <div id="qrcode" class="d-flex justify-content-center mb-4" style="min-height: 256px;"></div>
                        
                        <p class="text-muted">{{ __('This QR code refreshes automatically every 50 seconds.') }}</p>
                        <p class="text-danger font-weight-bold">
                            {{ __('Next refresh in') }} <span id="countdown">50</span> {{ __('seconds') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script>
        let countdown = 50;
        let qrCodeObj = null;

        function generateQR() {
            $.ajax({
                url: "{{ route('staff-attendance.qr-generate') }}",
                type: 'POST',
                data: {
                    _token: "{{ csrf_token() }}"
                },
                success: function(response) {
                    if (response.error === false) {
                        $('#qrcode').empty(); // Clear previous QR
                        qrCodeObj = new QRCode(document.getElementById("qrcode"), {
                            text: response.token,
                            width: 256,
                            height: 256,
                            colorDark : "#000000",
                            colorLight : "#ffffff",
                            correctLevel : QRCode.CorrectLevel.H
                        });
                        countdown = 50; // Reset countdown
                        $('#countdown').text(countdown);
                    }
                },
                error: function() {
                    console.error("Failed to generate QR token.");
                }
            });
        }

        $(document).ready(function() {
            // Initial generation
            generateQR();

            // Refresh every 50 seconds
            setInterval(function() {
                generateQR();
            }, 50000);

            // Countdown timer
            setInterval(function() {
                if (countdown > 0) {
                    countdown--;
                    $('#countdown').text(countdown);
                }
            }, 1000);
        });
    </script>
@endsection
