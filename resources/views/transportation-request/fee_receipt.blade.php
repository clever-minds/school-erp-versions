<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transportation Fee Receipt - {{ $user->full_name }}</title>
    <!-- Use standard Bootstrap 4 (commonly used in these systems) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/4.6.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            background-color: #f0f2f5;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            padding-top: 50px;
            padding-bottom: 50px;
        }

        .receipt-preview-header {
            max-width: 850px;
            margin: 0 auto 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .receipt-preview-header h4 {
            color: #4b5563;
            font-weight: 700;
            margin: 0;
        }

        .receipt-card {
            max-width: 850px;
            margin: 0 auto;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            border: none;
        }

        /* --- Print Specific Styling --- */
        @media print {
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                color-adjust: exact !important;
            }
            body {
                background-color: #fff !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            .no-print {
                display: none !important;
            }
            .container-fluid {
                padding: 0 !important;
                width: 100% !important;
            }
            .receipt-card {
                box-shadow: none !important;
                border: 1px solid #e5e7eb !important;
                max-width: 100% !important;
                width: 100% !important;
                border-radius: 0 !important;
                margin: 0 !important;
            }
            @page {
                margin: 0;
                size: auto;
            }
        }

        /* --- Header --- */
        .receipt-header {
            background-color: #111827;
            color: #fff;
            padding: 35px 40px;
        }

        .school-brand {
            display: flex;
            align-items: center;
        }

        .school-logo-container {
            width: 60px;
            height: 60px;
            background: #fff;
            border-radius: 12px;
            padding: 8px;
            margin-right: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .school-logo-container img {
            max-height: 100%;
            object-fit: contain;
        }

        .school-name {
            font-size: 22px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
        }

        .school-tagline {
            font-size: 12px;
            color: #9ca3af;
            font-style: italic;
        }

        .receipt-summary {
            text-align: right;
        }

        .payment-status {
            display: inline-block;
            background: rgba(16, 185, 129, 0.15);
            color: #10b981;
            padding: 6px 16px;
            border-radius: 50px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 15px;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .receipt-no-box {
            color: #fff;
        }

        .receipt-no-box .label {
            display: block;
            font-size: 11px;
            color: #9ca3af;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 4px;
        }

        .receipt-no-box .value {
            font-size: 28px;
            font-weight: 800;
        }

        /* --- Details Grid --- */
        .details-grid {
            padding: 40px;
            border-bottom: 1px solid #f3f4f6;
        }

        .detail-block {
            margin-bottom: 25px;
        }

        .detail-block .block-title {
            font-size: 12px;
            font-weight: 700;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 20px;
            padding-bottom: 8px;
            border-bottom: 1px solid #f3f4f6;
        }

        .info-item {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }

        .info-icon {
            width: 38px;
            height: 38px;
            background: #f3f4f6;
            color: #6b7280;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-size: 14px;
        }

        .info-content .label {
            display: block;
            font-size: 11px;
            color: #6b7280;
            margin-bottom: 2px;
        }

        .info-content .value {
            display: block;
            font-size: 15px;
            font-weight: 700;
            color: #1f2937;
        }

        .payment-mode-pill {
            background: #f0fdfa;
            color: #0d9488;
            border: 1px solid #ccfbf1;
            padding: 4px 12px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 700;
        }

        .payment-mode-pill i {
            font-size: 10px;
            margin-right: 6px;
            vertical-align: middle;
        }

        /* --- Table Styling --- */
        .items-section {
            padding: 0 40px 40px;
        }

        .items-container {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            overflow: hidden;
        }

        .custom-table {
            width: 100%;
            margin-bottom: 0;
        }

        .custom-table th {
            background: #f9fafb;
            border-top: none;
            border-bottom: 1px solid #e5e7eb;
            padding: 15px 25px;
            font-size: 11px;
            font-weight: 700;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .custom-table td {
            padding: 25px;
            vertical-align: middle;
            border-top: none;
        }

        .fee-description {
            display: flex;
            align-items: center;
        }

        .fee-icon {
            width: 24px;
            height: 24px;
            background: #d1fae5;
            color: #059669;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            margin-right: 12px;
        }

        .fee-text {
            font-size: 16px;
            font-weight: 700;
            color: #111827;
        }

        .pickup-location {
            font-size: 13px;
            color: #6b7280;
            margin-top: 8px;
            display: flex;
            align-items: center;
        }

        .pickup-location i {
            margin-right: 6px;
            font-size: 11px;
        }

        .duration-badge {
            font-size: 14px;
            color: #4b5563;
            font-weight: 600;
        }

        .amount-text {
            font-size: 20px;
            font-weight: 800;
            color: #111827;
            text-align: right;
        }

        /* --- Footer --- */
        .receipt-footer {
            padding: 30px 40px;
            background: #fafafa;
            border-top: 1px solid #f3f4f6;
            text-align: center;
        }

        .footer-note {
            font-size: 12px;
            color: #9ca3af;
            margin-bottom: 5px;
        }

        .copyright {
            font-size: 11px;
            color: #9ca3af;
        }

        /* Button Styling */
        .btn-print {
            background-color: {{ $systemSettings['theme_color'] ?? '#6366f1' }};
            color: #fff;
            border: none;
            padding: 10px 24px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.2s;
            box-shadow: 0 4px 6px -1px rgba(99, 102, 241, 0.2);
        }

        .btn-print:hover {
            background-color: {{ $systemSettings['theme_color'] ?? '#6366f1' }};
            color: #fff;
            transform: translateY(-1px);
            box-shadow: 0 10px 15px -3px rgba(99, 102, 241, 0.3);
        }

        .btn-print i {
            margin-right: 8px;
        }

    </style>
</head>
<body>

    <div class="container-fluid">
        <!-- Receipt Preview Top Bar -->
        {{-- In URL gettin API then i want to show that if API is comming from there then hide preview top bar --}}
        @if (request()->getRequestUri() != '/api/transport/receipt')
            <div class="receipt-preview-header no-print">
                <h4>Receipt Preview</h4>
                <button onclick="window.print()" class="btn btn-print btn-theme">
                    <i class="fas fa-print"></i> Print Receipt
                </button>
            </div>
        @endif
        

        <div class="receipt-card">
            <!-- Header -->
            <div class="receipt-header">
                <div class="row align-items-center">
                    <div class="col-md-7">
                        <div class="school-brand">
                            <div class="school-logo-container">
                                @if ($school['vertical_logo'] ?? '')
                                    <img src="{{ $school['vertical_logo'] }}" alt="School Logo">
                                @else
                                    <img src="{{ asset('assets/favicon.svg') }}" alt="Default Logo">
                                @endif
                            </div>
                            <div class="brand-text">
                                <div class="school-name">{{ $school['school_name'] ?? 'School Name' }}</div>
                                <div class="school-tagline">{{ $school['school_address'] ?? 'School Address' }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="receipt-summary">
                            <div class="payment-status">Payment Successful</div>
                            <div class="receipt-no-box">
                                <span class="label">Receipt No.</span>
                                <span class="value">#{{ str_pad($TransportationPayment->id, 4, '0', STR_PAD_LEFT) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recipient & Transaction Info -->
            <div class="details-grid">
                <div class="row">
                    <!-- Recipient Info -->
                    <div class="col-md-6">
                        <div class="detail-block">
                            <div class="block-title">Recipient Details</div>
                            
                            <div class="info-item">
                                <div class="info-icon"><i class="fas fa-user"></i></div>
                                <div class="info-content">
                                    <span class="label">Name</span>
                                    <span class="value">{{ $user->full_name }}</span>
                                </div>
                            </div>

                            <div class="info-item">
                                <div class="info-icon"><i class="fas fa-envelope"></i></div>
                                <div class="info-content">
                                    <span class="label">Email / Admission No.</span>
                                    <span class="value">{{ $user->student->admission_no ?? $user->email }}</span>
                                </div>
                            </div>

                            @if($user->student)
                            <div class="info-item">
                                <div class="info-icon"><i class="fas fa-graduation-cap"></i></div>
                                <div class="info-content">
                                    <span class="label">Class & Section</span>
                                    <span class="value">{{ $user->student->class_section->full_name ?? ($user->student->class_section->class->name . ' - ' . ($user->student->class_section->section->name ?? '')) }}</span>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>

                    <!-- Transaction Info -->
                    <div class="col-md-6">
                        <div class="detail-block">
                            <div class="block-title">Transaction Info</div>

                            <div class="info-item">
                                <div class="info-icon"><i class="fas fa-calendar-alt"></i></div>
                                <div class="info-content">
                                    <span class="label">Paid Date</span>
                                    <span class="value">{{ $TransportationPayment->paid_at }}</span>
                                </div>
                            </div>

                            <div class="info-item">
                                <div class="info-icon"><i class="fas fa-wallet"></i></div>
                                <div class="info-content">
                                    <span class="label">Payment Mode</span>
                                    <div class="mt-1">
                                        <span class="payment-mode-pill">
                                            <i class="fas fa-circle"></i> {{ strtoupper($TransportationPayment->paymentTransaction->payment_gateway ?? 'N/A') }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            @if($TransportationPayment->paymentTransaction->payment_gateway === 'cheque')
                            <div class="info-item">
                                <div class="info-icon"><i class="fas fa-file-invoice-dollar"></i></div>
                                <div class="info-content">
                                    <span class="label">Cheque Number</span>
                                    <span class="value">{{ $TransportationPayment->paymentTransaction->order_id }}</span>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Items Table -->
            <div class="items-section">
                <div class="items-container">
                    <table class="table custom-table">
                        <thead>
                            <tr>
                                <th width="50%">Fee Description</th>
                                <th width="20%">Duration</th>
                                <th width="30%" class="text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <div class="fee-description">
                                        <div class="fee-icon"><i class="fas fa-check"></i></div>
                                        <div class="fee-text">Transportation Fee</div>
                                    </div>
                                    <div class="pickup-location">
                                        <i class="fas fa-map-marker-alt"></i>
                                        {{ $TransportationPayment->pickupPoint->name ?? 'N/A' }}
                                    </div>
                                </td>
                                <td>
                                    <div class="duration-badge">{{ $TransportationPayment->transportationFee->duration ?? 'N/A' }} Days</div>
                                </td>
                                <td>
                                    <div class="amount-text">
                                        {{ $schoolSettings['currency_symbol'] ?? '₹' }} {{ number_format($TransportationPayment->amount, 2) }}
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Footer -->
            <div class="receipt-footer">
                <div class="footer-note">This is a computer-generated document. No signature required.</div>
                <div class="copyright">&copy; {{ date('Y') }} {{ $school['school_name'] ?? 'School' }}. All rights reserved.</div>
            </div>
        </div>
    </div>

    <!-- Print immediately if needed or just let the user click the button -->
    <script>
        // Optional: Auto-open print dialog on load
        // window.onload = function() { window.print(); }
    </script>
</body>
</html>