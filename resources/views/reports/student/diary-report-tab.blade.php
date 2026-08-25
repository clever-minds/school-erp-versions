{{-- Student Diary Report Tab --}}
<div class="diary-report-wrapper py-2">

{{-- Header Section --}}
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 border-bottom pb-3">
        
        {{-- Summary Badges (Left) --}}
        <div class="d-flex flex-wrap align-items-center mb-3 mb-md-0 summary-badges-container">
            <span class="badge badge-light text-dark shadow-sm px-3 py-2 mr-2 mb-2">
                <i class="fa fa-file-text-o mr-1 text-secondary"></i> {{ __('total') }}: <span id="diary_total_count">0</span>
            </span>
            <span class="badge badge-soft-success shadow-sm px-3 py-2 mr-2 mb-2">
                <i class="fa fa-check-circle mr-1"></i> {{ __('positive') }}: <span id="diary_positive_count">0</span>
            </span>
            <span class="badge badge-soft-danger shadow-sm px-3 py-2 mb-2">
                <i class="fa fa-exclamation-triangle mr-1"></i> {{ __('negative') }}: <span id="diary_negative_count">0</span>
            </span>
        </div>
        
        {{-- Filters (Right) --}}
        <div class="form-inline d-flex flex-wrap align-items-center">
            {{-- Type Filter --}}
            <select class="form-control form-control-sm mr-2 mb-2 mb-sm-0" id="diary_type" style="min-width: 120px;">
                <option value="">{{ __('all_types') }}</option>
                <option value="positive">{{ __('positive') }}</option>
                <option value="negative">{{ __('negative') }}</option>
            </select>

            {{-- Month Filter --}}
            <div class="input-group input-group-sm mb-2 mb-sm-0" style="min-width: 150px;">
                <select class="form-control" id="diary_month">
                    <option value="">{{ __('all_months') }}</option>
                    @foreach($attendanceMonths as $month)
                        <option value="{{ $month->id }}" data-year="{{ $month->year }}">{{ $month->name }}</option>
                    @endforeach
                </select>
                <div class="input-group-append">
                    <span class="input-group-text bg-white"><i class="fa fa-calendar text-muted"></i></span>
                </div>
            </div>
        </div>
    </div>

    {{-- Diary Cards Container --}}
    <div id="diary_cards_container" class="diary-list">
        <!-- Dynmically loaded cards go here -->
    </div>

    {{-- Loading Indicator --}}
    <div id="diary_loading" class="text-center py-5" style="display: none;">
        <div class="spinner-border text-primary" role="status">
            <span class="sr-only">Loading...</span>
        </div>
    </div>

    {{-- Pagination Container --}}
    <div id="diary_pagination" class="d-flex justify-content-center mt-4">
        <!-- Pagination controls -->
    </div>

</div>

<script>
    window.DIARY_STUDENT_ID = '{{ $student->user_id ?? $student->id }}';
    window.DIARY_SESSION_YEAR_ID = '{{ $session_year_id }}';
    window.DIARY_FETCH_URL = '{{ route("reports.student.diary.report") }}';
    // Let's pass missing translations to JS (could be improved by global dictionary, but keeping it simple)
</script>
