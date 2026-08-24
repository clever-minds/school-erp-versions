<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        body {
    margin: 0;
    padding: 0;
}

        * {
            font-family: DejaVu Sans, sans-serif;
        }
      .page-break {
            page-break-after: always;
        }

    .page-break:last-child {
        page-break-after: auto;
    }

    </style>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Fees Receipt</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/4.6.0/css/bootstrap.min.css"/>
</head>
<body>
    @php
        $compulsoryFeesType = $feesPaid->fees->compulsory_fees->pluck('fees_type_name');
        $compulsoryFeesType = implode(" , ", $compulsoryFeesType->toArray());
    @endphp

    {{-- Generate Receipt for Each Compulsory Fee --}}
    @if(isset($feesPaid->compulsory_fee) && $feesPaid->compulsory_fee->isNotEmpty())    
        @foreach ($feesPaid->compulsory_fee as $index => $compulsoryFee)
            
            <div class="page-break">
                <div class="container">
                    <div class="row mt-4">
                        <div class="col">
                            {{-- Header Section --}}
                            <div class="row">
                                <div class="col">
                                    <div class="text-center">
                                        <div>
                                            @if ($school['horizontal_logo'] ?? '')
                                                <img style="height: 4rem;width: auto;" src="{{ public_path('storage/') . $school['horizontal_logo'] }}" alt="">                    
                                            @else
                                                <img style="height: 4rem;width: auto;" src="{{ public_path('assets/horizontal-logo2.svg') }}" alt="">
                                            @endif
                                        </div>
                                        {{--<span class="text-default-d3 ml-4" style="font-size:1.5rem"><strong>{{$school['school_name'] ?? ''}}</strong></span><br>--}}
                                        <span class="text-default-d3 ml-4" style="font-size:1rem">{{$school['school_address'] ?? ''}}</span>
                                        <hr style="border: 1px solid">
                                        <h4>Fee Receipt</h4>
                                    </div>
                                </div>
                            </div>

                            {{-- Invoice Details --}}
                            <table style="width:100%; border-collapse:collapse; text-align:center;">

                                <tr>
                                    <!-- LEFT : STUDENT -->
                                    <td style="width:50%; vertical-align:top; text-align:left;">
                                        <strong><u>Student Details</u></strong><br>
                                        <strong>Name</strong> :- {{ $student->user->full_name }}<br>
                                        <strong>Class</strong> :- {{ $student->class_section->full_name ?? '' }}<br>
                                        <strong>GR No.</strong> :- {{ $student->user->email }}<br>
                                    </td>

                                    <!-- RIGHT : RECEIPT -->
                                    <td style="width:50%; vertical-align:top; text-align:left;">
                                        <strong><u>Receipt Details</u></strong><br>
                                        <strong>Receipt No.</strong> :- C-{{ $compulsoryFee->id ?? '' }}
                                    </td>
                                </tr>
                            </table>

                            <hr style="border: 1px solid">

                           

                            {{-- Fees Table --}}
                            <div class="">
                                <table class="table" style="text-align: center">
                                    <thead>
                                        <tr>
                                            {{--<th scope="col">Sr</th>--}}
                                            <th scope="col" colspan="">Fee Type</th>
                                            <th scope="col">Amount</th>
                                            <th scope="col" colspan="">Remark</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if($compulsoryFee->type == "Full Payment")
                                            <tr>
                                               {{--  <td scope="row" class="text-left">1</td> --}}
                                                <td colspan="" class="text-left">
                                                    {{$compulsoryFee->type}}<br>
                                                    <small class="font-weight-bold">( {{$compulsoryFeesType}} )</small><br>

                                                    <small>Mode : <span class="font-weight-bold">{{ !empty($compulsoryFee->bank_name) ? 'Bank' : $compulsoryFee->mode }}</span></small><br>
                                                    @if($compulsoryFee->mode === "Online")
                                                        <small>Bank Name : <span class="font-weight-bold">{{ $compulsoryFee->bank_name ?? '-' }}</span></small><br>
                                                        <small>Transaction ID : <span class="font-weight-bold">{{ $compulsoryFee->transaction_id ?? '-' }}</span></small><br>
                                                    @endif
                                                    @if($compulsoryFee->mode === "chaque")
                                                        <small>Chaque No : <span class="font-weight-bold">{{ $compulsoryFee->chaque_no ?? '-' }}</span></small><br>
                                                    @endif
                                                     @if($compulsoryFee->mode === "cash")
                                                         <span class="font-weight-bold">{{ $compulsoryFee->mode ?? '-' }}</span></small><br>
                                                    @endif
                                                    <small>Date : <span class="font-weight-bold">{{date('d-m-Y',strtotime($compulsoryFee->date))}}</span></small><br>
                                                </td>
                                                <td class="text-right">
                                                    {{$compulsoryFee->amount}} {{$school['currency_symbol'] ?? ''}}
                                                </td>
                                                <td>{{$compulsoryFee->remark}}</td>
                                            </tr>
                                            @if ($compulsoryFee->due_charges)
                                                <tr>
                                                    <th scope="row" class="text-left">2</th>
                                                    <td colspan="" class="text-left">Due Charges</td>
                                                    <td class="text-right">{{$compulsoryFee->due_charges ?? 0}} {{$school['currency_symbol'] ?? ''}}</td>
                                                </tr>
                                            @endif
                                        @elseif($compulsoryFee->type == "Installment Payment")
                                            <tr>
                                                <th scope="row" class="text-left">1</th>
                                                <td colspan="" class="text-left">
                                                    {{$compulsoryFee->installment_fee->name}}<br>
                                                    <small>Mode : <span class="font-weight-bold">({{ $compulsoryFee->mode}})</span></small><br>
                                                    <small>Date : <span class="font-weight-bold">{{date('d-m-Y',strtotime($compulsoryFee->date))}}</span></small><br>
                                                    <small>Includes : <span class="font-weight-bold">{{$compulsoryFeesType}}</span></small>
                                                    @if ((float)$compulsoryFee->due_charges > 0)
                                                        <br><small>Due Charges: <b>{{ $compulsoryFee->due_charges }}</b></small>
                                                    @endif
                                                </td>
                                                <td class="text-right">{{$compulsoryFee->amount + $compulsoryFee->due_charges}} {{$school['currency_symbol'] ?? ''}}</td>
                                            </tr>
                                        @endif

                                        <tr>
                                            <th scope="row"></th>
                                            <td colspan="" class="text-left"><strong>Total Amount</strong></td>
                                            <td class="text-right"><strong>{{$compulsoryFee->amount + ($compulsoryFee->due_charges ?? 0)}} {{$school['currency_symbol'] ?? ''}}</strong></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            {{-- Footer/Signature Section --}}
                            <div class="mt-5 ml-4">
                                <table width="100%" style="width:100%; margin-top:20px;">
                                    <tr>
                                        <!-- LEFT -->
                                        <td style="width:50%; text-align:left; vertical-align:top;">
                                            <strong>Authorized By:</strong><br><br>
                                            _____________________
                                        </td>

                                        <!-- RIGHT -->
                                        <td style="width:50%; text-align:right; vertical-align:top;">
                                            <strong>Date:</strong> {{ date('d-m-Y') }}<br><br>
                                            _____________________
                                        </td>
                                    </tr>
                                </table>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    @endif
 
</body>
</html>