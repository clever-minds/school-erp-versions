<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>School Evaluation and Assessment Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #000;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .school-name-box {
            background-color: #a83232;
            color: #fff;
            padding: 10px;
            font-weight: bold;
            font-size: 14px;
            width: 40%;
            text-transform: uppercase;
        }
        .school-tagline {
            color: #7a9c39;
            padding-left: 15px;
            font-size: 12px;
            vertical-align: middle;
        }
        .report-title {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            text-decoration: underline;
            margin-bottom: 5px;
        }
        .report-subtitle {
            text-align: center;
            font-size: 14px;
            margin-bottom: 20px;
        }
        .info-table {
            width: 100%;
            margin-bottom: 20px;
            border-collapse: separate;
            border-spacing: 0 10px;
        }
        .info-table td {
            vertical-align: bottom;
        }
        .underline {
            border-bottom: 1px solid #000;
            display: inline-block;
            min-width: 200px;
        }
        .feedback-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .feedback-table th, .feedback-table td {
            border: 1px solid #000;
            padding: 8px;
        }
        .feedback-table th {
            background-color: #d9d9d9;
            font-weight: bold;
            text-align: center;
        }
        .category-header {
            background-color: #e6e6e6;
            font-weight: bold;
            font-size: 14px;
        }
        .checkbox-item {
            display: inline-block;
            margin-right: 15px;
        }
        .box {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid #a8a8a8;
            border-radius: 3px;
            margin-right: 5px;
            vertical-align: middle;
            text-align: center;
            line-height: 16px;
            font-size: 14px;
            font-weight: bold;
            font-family: 'DejaVu Sans', sans-serif;
        }
        .box-selected {
            background-color: #007bff;
            border-color: #007bff;
            color: #ffffff;
        }
    </style>
</head>
<body>

    <table class="header-table">
        <tr>
            <td class="school-name-box">
                @if(isset($audit->school) && $audit->school->name)
                    {{ $audit->school->name }}
                @else
                    LAURELS CLAN<br>INTERNATIONAL SCHOOL
                @endif
            </td>
            <td class="school-tagline">
                An Activity based digitalized learning system with character building.
            </td>
        </tr>
    </table>

    <div class="report-title">School Evaluation and Assessment Report</div>
    <div class="report-subtitle">({{ strtoupper($audit->audit_type ?? 'AUDIT') }})</div>

    <table class="info-table">
        <tr>
            <td width="15%">Auditor</td>
            <td width="35%" class="underline">{{ $audit->auditor ? $audit->auditor->first_name . ' ' . $audit->auditor->last_name : '-' }}</td>
            <td width="15%">Campus</td>
            <td width="35%" class="underline">{{ $audit->school ? $audit->school->name : '-' }}</td>
        </tr>
        <tr>
            <td>Date of Visit</td>
            <td class="underline">{{ date('d M, Y', strtotime($audit->audit_date)) }}</td>
            <td>Status</td>
            <td class="underline">{{ $audit->status == 1 ? 'Completed' : 'Pending' }}</td>
        </tr>
    </table>

    @if($audit->remarks)
        <div style="margin-bottom: 15px;">
            <strong>General Remarks:</strong> <br>
            {{ $audit->remarks }}
        </div>
    @endif

    <table class="feedback-table">
        <thead>
            <tr>
                <th width="40%">Particulars</th>
                <th width="40%">Status</th>
                <th width="20%">Remarks</th>
            </tr>
        </thead>
        <tbody>
            @foreach($audit->answers->groupBy('question.category.name') as $category => $categoryAnswers)
                @if($category)
                    <tr>
                        <td colspan="3" class="category-header">{{ $category }}</td>
                    </tr>
                @endif
                
                @foreach($categoryAnswers as $answer)
                    <tr>
                        <td>{{ $answer->question ? $answer->question->question : '-' }}</td>
                        <td>
                            @if($answer->question && in_array($answer->question->type, ['Yes/No', 'Conditional']))
                                @php 
                                    $conditionalOptions = $answer->question->custom_options ? array_map('trim', explode(',', $answer->question->custom_options)) : ['Excellent', 'Good', 'Average', 'Unsatisfactory'];
                                    $isYes = ($answer->answer == 'Yes' || in_array($answer->answer, $conditionalOptions)); 
                                    $isNo = ($answer->answer == 'No'); 
                                    $isNA = ($answer->answer == 'N/A'); 
                                @endphp
                                <span class="checkbox-item">
                                    <span class="box {{ $isYes ? 'box-selected' : '' }}">{!! $isYes ? '&#10004;' : '' !!}</span> Yes
                                </span>
                                <span class="checkbox-item">
                                    <span class="box {{ $isNo ? 'box-selected' : '' }}">{!! $isNo ? '&#10004;' : '' !!}</span> No
                                </span>
                                <span class="checkbox-item">
                                    <span class="box {{ $isNA ? 'box-selected' : '' }}">{!! $isNA ? '&#10004;' : '' !!}</span> N/A
                                </span>
                                @if(in_array($answer->answer, $conditionalOptions))
                                    <br><strong>Rating:</strong> {{ $answer->answer }}
                                @endif
                            @elseif($answer->question && in_array($answer->question->type, ['Rating', 'Custom']))
                                <strong>{{ $answer->answer }}</strong>
                            @else
                                {{ $answer->answer }}
                            @endif
                        </td>
                        <td>{{ $answer->remarks ?? '' }}</td>
                    </tr>
                @endforeach
            @endforeach
        </tbody>
    </table>

</body>
</html>
