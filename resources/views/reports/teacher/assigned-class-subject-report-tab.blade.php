{{-- ========================================= --}}
{{-- CLASS TEACHER OF (Modern Card Layout) --}}
{{-- ========================================= --}}
<div class="card shadow-sm border-0 tvr-card mb-4">
    <div class="card-body p-4">
        <h6 class="text-uppercase text-muted font-weight-bold mb-3" style="letter-spacing: 1px;">{{ __('class_teacher_of') }}</h6>

        @if($teacher->staff && $teacher->staff->class_teacher->count())
            <div class="row">
                @foreach ($teacher->staff->class_teacher as $ct)
                    @php $cs = $ct->class_section; @endphp

                    <div class="col-md-2 mb-3">
                        <div class="border rounded p-3 bg-white" style="border-color: #e2e8f0 !important; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
                            <h4 class="font-weight-bold mb-2 text-theme">
                                {{ $cs->class->name ?? '' }} - {{ $cs->section->name ?? '' }} 
                                @if ($cs->class->shift) <small class="text-muted">({{ $cs->class->shift->name }})</small> @endif
                            </h4>
                            <div class="text-muted" style="font-size: 13px;">
                                @if ($cs->medium && $cs->medium->name)
                                    <div class="badge badge-outline-info">{{ $cs->medium->name ?? '-' }}</div>
                                @endif
                                @if ($cs->class->stream && $cs->class->stream->name)
                                    <div class="badge badge-outline-info">{{ $cs->class->stream->name ?? '-' }}</div>
                                @endif
                            </div>
                        </div>
                    </div>

                @endforeach
            </div>
        @else
            <div class="tvr-no-data py-3">
                <i class="fa fa-info-circle mb-2" style="font-size: 24px; color: #cbd5e1;"></i>
                <p class="mb-0">{{ __('not_assigned_as_class_teacher') }}</p>
            </div>
        @endif
    </div>
</div>

{{-- ========================================= --}}
{{-- PREPARE UNIQUE CLASS SECTIONS --}}
{{-- ========================================= --}}
@php
    $uniqueClasses = $teacher->staff
        ? $teacher->staff->subjects->groupBy('class_section_id')
        : collect();
@endphp

{{-- ========================================= --}}
{{-- TEACHING OVERVIEW TABLE (Modern) --}}
{{-- ========================================= --}}
<div class="card shadow-sm border-0 tvr-card">
    <div class="card-body p-4">
        <h5 class="tvr-card-title mb-4">{{ __('teaching_overview') }}</h5>

        @if($uniqueClasses->count())
            <div class="table-responsive">
                <table class="table mb-0 tvr-table">
                    <thead>
                        <tr>
                            <th>{{ __('class') }}</th>
                            <th>{{ __('section') }}</th>
                            <th>{{ __('medium') }}</th>
                            <th>{{ __('stream') }}</th>
                            <th>{{ __('shift') }}</th>
                            <th>{{ __('subjects') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($uniqueClasses as $items)
                            @php $cs = $items->first()->class_section; @endphp

                            <tr>
                                <td class="font-weight-bold">{{ $cs->class->name ?? '' }}</td>
                                <td class="font-weight-bold">{{ $cs->section->name ?? '' }}</td>
                                <td>{{ $cs->medium->name ?? '-' }}</td>
                                <td>{{ $cs->class->stream->name ?? '-' }}</td>
                                <td>{{ $cs->class->shift->name ?? '-' }}</td>

                                <td>
                                    <div class="d-flex flex-wrap gap-2">
                                    @foreach ($items as $sub)
                                        <span class="badge" style="background-color: #f3f0ff; color: var(--theme-color); font-weight: 600; padding: 6px 10px; border-radius: 6px; margin: 2px;">
                                            {{ $sub->subject->name_with_type }}
                                        </span>
                                    @endforeach
                                    </div>
                                </td>
                            </tr>

                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="tvr-no-data py-4">
                <i class="fa fa-book mb-2" style="font-size: 32px; color: #e2e8f0;"></i>
                <p class="mb-0">{{ __('no_teaching_assignments_found') }}</p>
            </div>
        @endif
    </div>
</div>