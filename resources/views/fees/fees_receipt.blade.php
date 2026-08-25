<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        * {
            font-family: DejaVu Sans, sans-serif;
        }
    </style>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Fees Receipt || {{ config('app.name') }}</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/4.6.0/css/bootstrap.min.css" integrity="sha512-P5MgMn1jBN01asBgU0z60Qk4QxiXo86+wlFahKrsQf37c9cro517WzVSPPV1tDKzhku2iJ2FVgL67wG03SGnNA==" crossorigin="anonymous" referrerpolicy="no-referrer"/>
</head>
<body>
<div class="container ">
    <div class="row mt-4">
        <div class="col">
            <div class="row">
                <div class="col">
                    <div class="text-center">
                        {{--  <i><img style="height: 5rem;width: 5rem;" src="{{$logo}}" alt="logo"></i>  --}}
                        <br>
                        <span class="text-default-d3 ml-4" style="font-size:1.5rem"><strong>{{$schoolName}}</strong></span>
                        <br>
                        <span class="text-default-d3 ml-4" style="font-size:1rem">{{$schoolAddress}}</span>
                        <hr width="auto" style="border: 1px solid">
                        <h4>
                            Fee Receipt
                        </h4>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-sm-6">
                </div>

                <div class="col-sm-6 align-self-start d-sm-flex justify-content-end">
                    <div class="text-grey-m2 mt-2 ml-3">
                        <p><strong><u>Invoice</u></strong><br>
                            <strong>Fee Receipt</strong> :- {{isset($feesPaid) ? $feesPaid->id : '-'}}<br>
                        </p>
                    </div>
                </div>
            </div>
            <hr style="border: 1px solid">
            <div class="row ml-3">
                <div class="col-sm-6 align-self-start">
                    <div class="row text-black">
                        <p><strong><u>Student Details :- </u></strong><br>
                            <strong>Name</strong> :- {{isset($feesPaid) ? $feesPaid->student->full_name : '-'}} <br>
                            <strong>Session</strong> :- {{isset($feesPaid) ? $feesPaid->session_year->name : '-'}} <br>
                            <strong>Class</strong> :- {{isset($feesPaid) ? $feesPaid->class->full_name : '-'}}<br>
                    </div>
                </div>
            </div>
            <div class="mt-4 ml-4">
                <table class="table" style="text-align: center">
                    <thead>
                    <tr>
                        <th scope="col">Sr no.</th>
                        <th scope="col" colspan="2">Fee Type</th>
                        <th scope="col">Amount</th>
                    </tr>
                    </thead>
                    @php
                        $no = 1;
                    @endphp
                    <tbody>
                        @if(isset($feesPaid->compulsory_fee) && $feesPaid->compulsory_fee->isNotEmpty())
                            @foreach ($feesPaid->compulsory_fee as $compulsoryFee)
                                @if($compulsoryFee->type == 1)
                                    @foreach ($feesPaid->compulsory_data as $data)
                                        <tr>
                                            <th scope="row" class="text-left">{{$no++}}</th>
                                            <td colspan="2" class="text-left">{{$data->fees_type->name}} <small class="font-weight-bold">({{ $compulsoryFee->mode_name}})</small>
                                            <td class="text-right">{{$data->amount}} {{$currencySymbol}}</td>
                                        </tr>
                                    @endforeach
                                    <tr>
                                        <th scope="row" class="text-left">{{$no++}}</th>
                                        <td colspan="2" class="text-left">Due Charges></td>
                                        <td class="text-right">{{$compulsoryFee->due_charges}} {{$currencySymbol}}</td>
                                    </tr>
                                @elseif($compulsoryFee->type == 2)
                                    <tr>
                                        <th scope="row" class="text-left">{{$no++}}</th>
                                        <td colspan="2" class="text-left">{{$compulsoryFee->installment_fee->name}} <small class="font-weight-bold">({{ $compulsoryFee->mode_name}})</small>
                                            <br><small>(PAID ON :- {{date('d-m-Y',strtotime($compulsoryFee->date))}}) </small></td>
                                        <td class="text-right">{{$compulsoryFee->amount}} {{$currencySymbol}}</td>
                                    </tr>
                                @endif
                            @endforeach
                        @endif

                        @if(isset($feesPaid->optional_fee) && $feesPaid->optional_fee->isNotEmpty())
                            @foreach ($feesPaid->optional_fee as $optionalFee)
                                <tr>
                                    <th scope="row" class="text-left">{{$no++}}</th>
                                    <td colspan="2" class="text-left">{{ $optionalFee->fees_class->fees_type_name}} <small class="font-weight-bold">({{ $optionalFee->mode_name }})</small>
                                        <br><small>(PAID ON :- {{date('d-m-Y',strtotime($optionalFee->date))}}) </small></td>
                                    <td class="text-right">{{$optionalFee->amount}} {{$currencySymbol}}</td>
                                </tr>
                            @endforeach
                        @endif
                        <tr>
                            <th scope="row"></th>
                            <td colspan="2" class="text-left"><strong>Total Amount</strong></td>
                            <td class="text-right">{{$feesPaid->amount}} {{$currencySymbol}}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</div>

</body>

</html>
