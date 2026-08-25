@extends('layouts.master')

@section('title')
    {{__('payment_configuration')}}
@endsection


@section('content')

    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{__('payment_configuration')}}
            </h3>
        </div>
        <div class="row grid-margin">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title mb-5">
                            {{__('payment_configuration')}}
                        </h4>
                        <form id="formdata" class="edit-form" action="{{route('system-settings.payment.update')}}" method="POST" novalidate="novalidate">
                            @csrf
                            <div class="row">

                                <div class="form-group col-sm-12 col-md-6">
                                    <label for="stripe_publishable_key">{{ __('stripe_publishable_key') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="stripe_publishable_key" id="stripe_publishable_key" class="form-control" placeholder="{{ __('stripe_publishable_key') }}" required value="{{$settings['stripe_publishable_key'] ?? ''}}">
                                </div>

                                <div class="form-group col-sm-12 col-md-6">
                                    <label for="stripe_secret_key">{{ __('stripe_secret_key') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="stripe_secret_key" id="stripe_secret_key" class="form-control" placeholder="{{ __('stripe_secret_key') }}" required value="{{$settings['stripe_secret_key'] ??''}}">
                                </div>

                                <div class="form-group col-sm-12 col-md-6">
                                    <label for="stripe_webhook_secret">{{ __('stripe_webhook_secret') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="stripe_webhook_secret" id="stripe_webhook_secret" class="form-control" placeholder="{{ __('stripe_webhook_secret') }}" required value="{{$settings['stripe_webhook_secret'] ?? ''}}">
                                </div>

                                <div class="form-group col-sm-12 col-md-6">
                                    <label for="stripe_webhook_url">{{ __('stripe_webhook_url') }}</label>
                                    <input type="text" name="stripe_webhook_url" id="stripe_webhook_url" class="form-control" placeholder="{{ __('stripe_webhook_url') }}" disabled value="{{ url('subscription/webhook/stripe') }}">
                                </div>

                                <div class="form-group col-md-3 col-sm-12">
                                    <label for="currency_code">{{__('currency_code')}} <span class="text-danger">*</span></label>
                                    <input name="currency_code" id="currency_code" value="{{ $settings['currency_code'] ?? ''}}" type="text" placeholder="{{__('currency_code')}}" class="form-control" required/>
                                </div>
                                <div class="form-group col-md-3 col-sm-12">
                                    <label for="currency_symbol">{{__('currency_symbol')}} <span class="text-danger">*</span></label>
                                    <input name="currency_symbol" id="currency_symbol" value="{{$settings['currency_symbol'] ??  ''}}" type="text" placeholder="{{__('currency_symbol')}}" class="form-control" required/>
                                </div>
                            </div>
                            <input class="btn btn-theme" type="submit" value="Submit">
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
