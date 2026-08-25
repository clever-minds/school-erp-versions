@extends('layouts.master')
@section('title')
    {{ __('dashboard') }}
@endsection
@section('content')

    <style>
        .truncateTitle {
            max-width: 100px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
    </style>

    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                <span class="page-title-icon bg-theme text-white mr-2">
                    <i class="fa fa-home"></i>
                </span> {{ __('dashboard') }}
            </h3>
        </div>

        {{-- Super Admin Dashboard --}}
        <div class="row">

            <div class="col-md-3 stretch-card grid-margin">
                <div class="card">
                    <div class="card-body custom-card-body">
                        <div class="d-flex flex-row flex-wrap">
                            <div class="ms-3">
                                {{ __('total_schools') }}
                                <p class="text-muted">
                                <h3>{{ $super_admin['total_school'] }}</h3>
                                </p>
                                <p class="mt-2 text-success font-weight-bold"> </p>
                            </div>
                            <img class="ml-auto" src="{{ url('images/total-schools.svg') }}" alt="">
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3 stretch-card grid-margin">
                <div class="card">
                    <div class="card-body custom-card-body">
                        <div class="d-flex flex-row flex-wrap">
                            <div class="ms-3">
                                {{ __('active_schools') }}
                                <p class="text-muted">
                                <h3>{{ $super_admin['active_schools'] }}</h3>
                                </p>
                                <p class="mt-2 text-success font-weight-bold"> </p>
                            </div>
                            <img class="ml-auto" src="{{ url('images/active-schools.svg') }}" alt="">
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3 stretch-card grid-margin">
                <div class="card">
                    <div class="card-body custom-card-body">
                        <div class="d-flex flex-row flex-wrap">
                            <div class="ms-3">
                                {{ __('inactive_schools') }}
                                <p class="text-muted">
                                <h3>{{ $super_admin['inactive_schools'] }}</h3>
                                </p>
                                <p class="mt-2 text-success font-weight-bold"> </p>
                            </div>
                            <img class="ml-auto" src="{{ url('images/inactive-schools.svg') }}" alt="">
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3 stretch-card grid-margin">
                <div class="card">
                    <div class="card-body custom-card-body">
                        <div class="d-flex flex-row flex-wrap">
                            <div class="ms-3">
                                {{ __('total_packages') }}
                                <p class="text-muted">
                                <h3>{{ $super_admin['total_packages'] }}</h3>
                                </p>
                                <p class="mt-2 text-success font-weight-bold"> </p>
                            </div>
                            <img class="ml-auto" src="{{ url('images/package.svg') }}" alt="">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">

            @if (($settings['wildcard_domain'] ?? 0) == 0 || ($settings['notification_settings'] ?? 0) == 0)
                <div class="col-md-12 grid-margin stretch-card">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title text-dark"><i class="fa fa-info-circle" aria-hidden="true"></i>
                                {{ __('server_configuration') }} - <span
                                    class="">{{ __('required_for_full_system_functionality') }}</span></h4>
                            <div class="list-wrapper">
                                <ul class="d-flex flex-column todo-list todo-list-custom">
                                    @foreach ($server_configuration as $key => $value)
                                        @if (($settings[$key] ?? 0) == 0)
                                            <li class="{{ $loop->index == 0 ? 'border-bottom' : '' }}">
                                                <div class="form-check">
                                                    <label class="form-check-label">
                                                        <input class="checkbox server-configuration-checkbox" id="{{ $key }}" value="1"
                                                            type="checkbox"> {{ $value['title'] }} <i class="input-helper"></i>
                                                        <a href="{{ $value['link'] }}" target="_blank" class="">
                                                            {{ __('view_setup') }}
                                                        </a>
                                                    </label>
                                                    <p class="text-muted text-wrap">{{ $value['description'] ?? '' }}</p>
                                                </div>
                                            </li>
                                        @endif
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <div class="col-md-7 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body custom-card-body">
                        <div class="row">
                            <div class="col-md-9">
                                <h4 class="card-title">
                                    {{ __('transaction') }}
                                </h4>
                            </div>
                            <div class="col-md-3">
                                {!! Form::selectRange('year', $start_year, date('Y'), date('Y'), [
                                    'class' => 'form-control form-control-sm year-filter',
                                ]) !!}
                            </div>
                        </div>

                        <div id="subscriptionTransactionChart">

                        </div>

                    </div>
                </div>
            </div>

            <div class="col-md-5 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body custom-card-body">
                        <h4 class="card-title">
                            {{ __('schools') }}
                        </h4>
                        @if ($schools->isNotEmpty())
                            <div class="v-scroll">
                                <table class="table custom-table">
                                    <thead>
                                        <th></th>
                                        <th>{{ __('school') }}</th>
                                        <th class="text-right">{{ __('admin') }}</th>
                                    </thead>
                                    <tbody>
                                        @foreach ($schools as $school)
                                            <tr>
                                                <td>
                                                    <img src="{{ $school->logo }}" onerror="onErrorImage(event)" class="me-2"
                                                        alt="image">
                                                </td>
                                                <td>{{ $school->name }}</td>
                                                <td class="text-right">{{ $school->user->full_name }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="v-scroll text-center" style="padding-top: 50%;">
                                <span>{{ __('no_school_added') }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body custom-card-body">
                        <h4 class="card-title">
                            {{ __('subscription') }} {{ __('details') }}
                        </h4>
                        @if (collect($package_graph)->filter()->isNotEmpty())
                            <div id="packageChart"> </div>
                        @else
                            <div class="text-center" style="padding-top: 40%;">
                                <span>{{ __('no_subscription_details_available') }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-md-6 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body custom-card-body">
                        <h4 class="card-title">
                            {{ __('staff') }} {{ __('details') }}
                        </h4>
                        @if ($staffs->isNotEmpty())
                            <div class="v-scroll">
                                <table class="table custom-table">
                                    @hasNotFeature('Staff Management')
                                    <thead>
                                        <th></th>
                                        <th>{{ __('name') }}</th>
                                        <th>{{ __('role') }}</th>
                                        <th class="text-right">{{ __('assign_schools') }}</th>
                                    </thead>
                                    {{-- @endHasNotFeature
                                    @hasFeature('Staff Management') --}}
                                    <tbody>
                                        @foreach ($staffs as $staff)
                                            <tr>
                                                <td>
                                                    <img src="{{ $staff->image }}" onerror="onErrorImage(event)" class="me-2"
                                                        alt="image">
                                                </td>
                                                <td>{{ $staff->full_name }}</td>
                                                <td>{{ $staff->roles->first()->name ?? '' }}</td>
                                                <td>{{ $staff->school_names }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    @endHasNotFeature
                                </table>
                            </div>
                        @else
                            <div class="v-scroll text-center" style="padding-top: 40%;">
                                <span>{{ __('no_staff_details_available') }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body custom-card-body">
                        <h4 class="card-title">
                            {{ __('addon') }}
                        </h4>
                        <div id="addonChart"> </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('script')
    <script>
        $(document).ready(function () {
            $('.server-configuration-checkbox').change(function () {
                var checkbox = $(this);
                var value = checkbox.prop('checked') ? 1 : 0;
                var name = checkbox.attr('id');

                $.ajax({
                    url: "{{ route('server-configuration.update') }}",
                    type: 'POST',
                    data: {
                        name: name,
                        value: value,
                    },
                    success: function (response) {
                        showSuccessToast(response.message);
                    },
                    error: function (xhr, status, error) {
                        console.log(xhr.responseText);
                    }
                });

            });
        });
    </script>

    <script>
        window.onload = setTimeout(() => {
            $('.year-filter').trigger('change');

            addon_graph(<?php echo json_encode($addon_graph[0]); ?>, <?php echo json_encode($addon_graph[1]); ?>);
            package_graph(<?php echo json_encode($package_graph[0]); ?>, <?php echo json_encode($package_graph[1]); ?>);
        }, 500);
    </script>
@endsection
