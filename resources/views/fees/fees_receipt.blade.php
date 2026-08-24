<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    * {
        font-family: DejaVu Sans, sans-serif;
        font-size: 10px;
    }

    body {
        margin: 0;
        padding: 0;
    }

    @page {
        margin: 15px;
    }

    .page-break {
        page-break-after: always;
    }

    .page-break:last-child {
        page-break-after: auto;
    }

    .text-center { text-align: center; }
    .text-left { text-align: left; }
    .text-right { text-align: right; }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    th, td {
        padding: 4px;
        border: 1px solid #000;
    }

    .no-border td {
        border: none;
    }

    .header-line {
        border-bottom: 2px solid #000;
        margin-top: 5px;
        margin-bottom: 10px;
    }

</style>
<title>Fee Receipt</title>
</head>

<body>

@php
    $compulsoryFeesType = $feesPaid->fees->compulsory_fees->pluck('fees_type_name');
    $compulsoryFeesType = implode(" , ", $compulsoryFeesType->toArray());
@endphp

@if(isset($feesPaid->compulsory_fee) && $feesPaid->compulsory_fee->isNotEmpty())    
@foreach ($feesPaid->compulsory_fee as $compulsoryFee)

<div class="page-break">

    <!-- Header -->
    <div class="text-center">
        @php
            $logoPath = public_path('storage/') . ($school['horizontal_logo'] ?? '');
            $logoBase64 = '';
            if (!empty($school['horizontal_logo']) && file_exists($logoPath) && is_file($logoPath)) {
                $logoData = base64_encode(file_get_contents($logoPath));
                $logoBase64 = 'data:image/' . pathinfo($logoPath, PATHINFO_EXTENSION) . ';base64,' . $logoData;
            }
        @endphp
        @if ($logoBase64)
            <img src="{{ $logoBase64 }}" style="height:40px; max-width:100%; margin-bottom:5px;">
        @endif

        <div style="font-size:10px; margin-bottom:5px;">
            {{ $school['school_address'] ?? '' }}
        </div>

        <div class="header-line" style="margin-top:2px; margin-bottom:5px;"></div>
        <h4 style="margin:0; margin-bottom:10px;">FEE RECEIPT</h4>
    </div>

    <!-- Student & Receipt Details -->
    <table class="no-border" style="margin-top:15px;">
        <tr>
            <td width="50%" style="vertical-align:top; text-align:left; padding:0;">
                <strong>Student Details</strong><br><br>
                <strong>Name:</strong> {{ $student->user->full_name }}<br>
                <strong>Class:</strong> {{ $student->class_section->full_name ?? '' }}<br>
                <strong>GR No:</strong> {{ $student->user->email }}<br>
            </td>

            <td width="50%" style="vertical-align:top; text-align:left; padding:0;">
                <strong>Receipt Details</strong><br><br>
                <strong>Receipt No:</strong> C-{{ $compulsoryFee->id ?? '' }}<br>
                <strong>Date:</strong> {{ date('d-m-Y', strtotime($compulsoryFee->date)) }}
            </td>
        </tr>
    </table>

    <!-- Fees Table -->
    <table style="margin-top:20px;">
        <thead>
            <tr>
                <th>Fee Type</th>
                <th width="20%">Amount</th>
                <th width="25%">Remark</th>
            </tr>
        </thead>
        <tbody>

            @if($compulsoryFee->type == "Full Payment")

                <tr>
                    <td class="text-left">
                        <strong>{{ $compulsoryFee->type }}</strong><br>
                        <small>( {{ $compulsoryFeesType }} )</small><br><br>

                        <strong>Mode:</strong> 
                        {{ !empty($compulsoryFee->bank_name) ? 'Bank' : $compulsoryFee->mode }}<br>

                        @if($compulsoryFee->mode === "Online")
                            <strong>Bank:</strong> {{ $compulsoryFee->bank_name ?? '-' }}<br>
                            <strong>Txn ID:</strong> {{ $compulsoryFee->transaction_id ?? '-' }}<br>
                        @endif

                        @if($compulsoryFee->mode === "Cheque")
                            <strong>Cheque No:</strong> {{ $compulsoryFee->cheque_no ?? '-' }}<br>
                        @endif
                    </td>

                    <td class="text-right">
                        {{ number_format($compulsoryFee->amount,2) }} {{ $school['currency_symbol'] ?? '' }}
                    </td>

                    <td class="text-left">
                        {{ $compulsoryFee->remark }} {{$feesPaid->fees->name}}
                    </td>
                </tr>

                @if ($compulsoryFee->due_charges > 0)
                <tr>
                    <td class="text-left">Due Charges</td>
                    <td class="text-right">
                        {{ number_format($compulsoryFee->due_charges,2) }} {{ $school['currency_symbol'] ?? '' }}
                    </td>
                    <td></td>
                </tr>
                @endif

            @elseif($compulsoryFee->type == "Installment Payment")

                <tr>
                    <td class="text-left">
                        <strong>{{ $compulsoryFee->installment_fee->name }}</strong><br>
                        <small>Includes: {{ $compulsoryFeesType }}</small><br>
                        <strong>Mode:</strong> {{ $compulsoryFee->mode }}
                    </td>

                    <td class="text-right">
                        {{ number_format($compulsoryFee->amount + $compulsoryFee->due_charges,2) }}
                        {{ $school['currency_symbol'] ?? '' }}
                    </td>

                    <td></td>
                </tr>

            @endif

            <!-- Total -->
            <tr>
                <td class="text-left"><strong>Total Amount</strong></td>
                <td class="text-right">
                    <strong>
                        {{ number_format($compulsoryFee->amount + ($compulsoryFee->due_charges ?? 0),2) }}
                        {{ $school['currency_symbol'] ?? '' }}
                    </strong>
                </td>
                <td></td>
            </tr>

        </tbody>
    </table>

    <!-- Digital Note Section -->
    <div style="margin-top:20px; text-align:center; font-size:10px; color:#555;">
        <em>This is a computer-generated document. No signature is required.</em>
    </div>

</div>

@endforeach
@endif

</body>
</html>
