<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Fees Receipt || {{ config('app.name') }}</title>
    <style>
        @page {
            margin: 20px;
            size: A4;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            color: #333;
            line-height: 1.4;
        }
        .container {
            width: 100%;
            margin: 0 auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .header-table td {
            vertical-align: top;
        }
        .school-info {
            text-align: left;
        }
        .school-name {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
            text-transform: uppercase;
        }
        .school-address {
            font-size: 12px;
            color: #555;
        }
        .receipt-title {
            text-align: right;
        }
        .receipt-header {
            font-size: 20px;
            font-weight: bold;
            text-transform: uppercase;
            border-bottom: 2px solid #333;
            display: inline-block;
            margin-bottom: 10px;
            padding-bottom: 5px;
        }
        .info-label {
            font-weight: bold;
            color: #555;
            width: 80px;
            display: inline-block;
        }
        .student-section {
            margin-top: 15px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            padding: 10px;
            background-color: #f9f9f9;
        }
        .student-table td {
            padding: 3px 5px;
        }
        .fee-table {
            margin-top: 20px;
        }
        .fee-table th {
            background-color: #eee;
            border-top: 1px solid #333;
            border-bottom: 1px solid #333;
            padding: 8px;
            text-align: left;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 11px;
        }
        .fee-table td {
            border-bottom: 1px solid #ddd;
            padding: 8px;
            vertical-align: top;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .font-bold {
            font-weight: bold;
        }
        .total-section {
            margin-top: 10px;
        }
        .total-table td {
            padding: 5px 10px;
        }
        .grand-total {
            background-color: #333;
            color: #fff;
            padding: 8px 10px;
            font-weight: bold;
            font-size: 14px;
        }
        .small-text {
            font-size: 10px;
            color: #777;
        }
        .logo-img {
            max-height: 60px;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>

<div class="container">
    {{-- Header Section --}}
    <table class="header-table">
        <tr>
            {{-- Left: School Info --}}
            <td width="60%">
                <div class="school-info">
                   
                    @if ($school['horizontal_logo'] ?? '')
                        <img class="logo-img" src="{{ public_path('storage/') . $school['horizontal_logo'] }}" alt="Logo">
                    @else
                        <img class="logo-img" src="{{ public_path('assets/horizontal-logo2.svg') }}" alt="Logo">
                    @endif
                    <div class="school-name">{{$school['school_name'] ?? 'School Name'}}</div>
                    <div class="school-address">{!! nl2br(e($school['school_address'] ?? '')) !!}</div>
                </div>
            </td>
            
            {{-- Right: Receipt Info --}}
            <td width="40%" class="receipt-title">
                <div class="receipt-header">Fee Receipt</div>
                <table style="width: 100%; text-align: right;">
                    <tr>
                        <td class="text-right"><span class="info-label">Receipt No:</span></td>
                        <td class="text-right font-bold">{{$feesPaid->id ?? '-'}}</td>
                    </tr>
                    <tr>
                        <td class="text-right"><span class="info-label">Date:</span></td>
                        <td class="text-right">{{ date($school['date_format'] ?? 'd M Y') }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- Student Details Section --}}
    <div class="student-section">
        <table class="student-table">
            <tr>
                <td width="15%"><span class="info-label">Student:</span></td>
                <td width="45%" class="font-bold" style="font-size: 13px;">{{$student->user->full_name}}</td>
                <td width="15%"><span class="info-label">Class:</span></td>
                <td width="25%">{{$student->class_section->full_name ?? ''}}</td>
            </tr>
            <tr>
                <td><span class="info-label">Admission:</span></td>
                <td>{{ $student->admission_no ?? '-' }}</td>
                <td><span class="info-label">Session:</span></td>
                <td>{{ $feesPaid->fees->session_year->name ?? '-' }}</td>
            </tr>
        </table>
    </div>

    {{-- Fee Details Table --}}
    <table class="fee-table">
        <thead>
            <tr>
                <th width="5%" class="text-center">Sr.</th>
                <th width="50%">Fee Description</th>
                <th width="25%">Payment Info</th>
                <th width="20%" class="text-right">Amount</th>
            </tr>
        </thead>
        <tbody>
            @php
                $no = 1;
                $total_fees = 0;
                $total_due_charges = 0;
            @endphp

            @php
                // Pre-process Compulsory Fees Types
                $compulsoryFeesType = $feesPaid->fees->compulsory_fees->pluck('fees_type_name')->join(', ');
            @endphp

            {{-- Compulsory Fees --}}
            @if(isset($feesPaid->compulsory_fee) && $feesPaid->compulsory_fee->isNotEmpty())
                @foreach ($feesPaid->compulsory_fee as $fee)
                    
                    @php
                        $payment_type = $fee->type;
                        $description = "";
                        if ($payment_type == "Full Payment" && $fee->fees_paid) {
                            $description = "Full Payment - " . $fee->fees_paid->fees->name ?? 'Fees';
                        } elseif ($payment_type == "Installment Payment") {
                            $description = $fee->installment_fee->name ?? "Installment";
                        }
                    @endphp

                    <tr>
                        <td class="text-center">{{ $no++ }}</td>
                        <td>
                            <span class="font-bold">{{ $description }}</span>
                            @if(isset($compulsoryFeesType) && !empty($compulsoryFeesType))
                                <div class="small-text" style="margin-top: 2px;">Includes: {{ $compulsoryFeesType }}</div>
                            @endif
                        </td>
                        <td>
                            <div class="small-text">Mode: {{ $fee->mode }} {{ $fee->cheque_no ? '#' . $fee->cheque_no : '' }}</div>
                            <div class="small-text">Date: {{ $fee->date }}</div>
                        </td>
                        <td class="text-right">
                            {{ number_format($fee->amount, 2) }} {{ $school['currency_symbol'] ?? '' }}
                            @if($fee->due_charges > 0)
                                <div class="small-text text-danger" style="margin-top:2px;">
                                    + {{ number_format($fee->due_charges, 2) }} (Due Chg)
                                </div>
                            @endif
                        </td>
                    </tr>

                    @php
                        $total_fees += $fee->amount;
                        $total_due_charges += $fee->due_charges ?? 0;
                    @endphp
                @endforeach
            @endif

            {{-- Optional Fees --}}
            @if(isset($feesPaid->optional_fee) && $feesPaid->optional_fee->isNotEmpty())
                @foreach ($feesPaid->optional_fee as $optFee)
                    <tr>
                        <td class="text-center">{{ $no++ }}</td>
                        <td>
                            <span class="font-bold">{{ $optFee->fees_class_type->fees_type_name }}</span> 
                            <span class="small-text">(Optional)</span>
                        </td>
                        <td>
                            <div class="small-text">Mode: {{ $optFee->mode }} {{ $optFee->cheque_no ? '#' . $optFee->cheque_no : '' }}</div>
                            <div class="small-text">Date: {{ $optFee->date }}</div>
                        </td>
                        <td class="text-right">
                            {{ number_format($optFee->amount, 2) }} {{ $school['currency_symbol'] ?? '' }}
                        </td>
                    </tr>
                    @php $total_fees += $optFee->amount; @endphp
                @endforeach
            @endif
        </tbody>
    </table>

    {{-- Total Section --}}
    <div class="total-section">
        <table class="total-table" style="width: 40%; margin-left: auto;">
            <tr>
                <td class="text-right"><strong>Subtotal:</strong></td>
                <td class="text-right">
                   {{ number_format($total_fees, 2) }} {{ $school['currency_symbol'] ?? '' }}
                </td>
            </tr>
            @if($total_due_charges > 0)
            <tr>
                <td class="text-right text-danger"><strong>Due Charges:</strong></td>
                <td class="text-right text-danger">
                   + {{ number_format($total_due_charges, 2) }} {{ $school['currency_symbol'] ?? '' }}
                </td>
            </tr>
            @endif
            <tr>
                <td class="text-right" width="50%"></td>
                <td width="50%" style="padding: 0;">
                    <div class="grand-total text-right">
                        <span>Total Paid:</span>
                        <span style="font-size: 16px; margin-left: 10px;">
                            {{ number_format($total_fees + $total_due_charges, 2) }} {{ $school['currency_symbol'] ?? '' }}
                        </span>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    {{-- Footer --}}
    {{-- <div style="margin-top: 50px; text-align: center; border-top: 1px dashed #ccc; padding-top: 10px;">
        <span class="small-text">This is a computer-generated receipt and does not require a signature.</span>
    </div> --}}
</div>

</body>
</html>
