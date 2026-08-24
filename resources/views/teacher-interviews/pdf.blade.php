<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Teacher Interview Performance Feedback</title>
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

        .feedback-table th,
        .feedback-table td {
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
                @if(isset($application->school) && $application->school->name)
                    {{ $application->school->name }}
                @else
                    LAURELS CLAN<br>INTERNATIONAL SCHOOL
                @endif
            </td>
            <td class="school-tagline">
                An Activity based digitalized learning system with character building.
            </td>
        </tr>
    </table>

    <div class="report-title">Teacher Interview Performance Feedback</div>
    <div class="report-subtitle">(EVALUATION REPORT)</div>

    <table class="info-table">
        <tr>
            <td width="20%">Applicant Name</td>
            <td width="30%" class="underline">{{ $application->name }}</td>
            <td width="15%">Phone</td>
            <td width="35%" class="underline">{{ $application->phone }}</td>
        </tr>
        <tr>
            <td>Email</td>
            <td class="underline">{{ $application->email }}</td>
            <td>Interviewer</td>
            <td class="underline">
                {{ $interview->interviewer ? $interview->interviewer->first_name . ' ' . $interview->interviewer->last_name : '-' }}
            </td>
        </tr>
        <tr>
            <td>Date of Visit</td>
            <td colspan="3" class="underline">{{ date('d M, Y') }}</td>
        </tr>
    </table>

    <table class="feedback-table">
        <thead>
            <tr>
                <th width="30%">Particulars</th>
                <th width="50%">Status</th>
                <th width="20%">Remarks</th>
            </tr>
        </thead>
        <tbody>
            @php
                $currentCategory = null;
            @endphp
            @foreach($feedbackQuestions as $question)
                @php $catName = $question->category ? $question->category->name : 'General'; @endphp
                @if($currentCategory !== $catName)
                    @php $currentCategory = $catName; @endphp
                    <tr>
                        <td colspan="3" class="category-header">{{ $currentCategory }}</td>
                    </tr>
                @endif

                @php
                    $currentAnswer = isset($feedbacks[$question->id]) ? $feedbacks[$question->id]->interviewer_feedback : '';
                @endphp

                <tr>
                    <td>{{ $question->feedback_question }}</td>
                    <td>
                        @if((in_array($question->type, ['Rating', 'rating']) || empty($question->type)) && $question->optionGroup)
                            @foreach($question->optionGroup->option_values as $opt)
                                @php $isSelected = ($currentAnswer == $opt['label']); @endphp
                                <span class="checkbox-item">
                                    <span
                                        class="box {{ $isSelected ? 'box-selected' : '' }}">{!! $isSelected ? '&#10004;' : '' !!}</span>
                                    {{ $opt['label'] }}
                                </span>
                            @endforeach
                        @elseif($question->type == 'Yes/No' || $question->type == 'boolean')
                            @php $isYes = ($currentAnswer == 'Yes');
                            $isNo = ($currentAnswer == 'No'); @endphp
                            <span class="checkbox-item">
                                <span class="box {{ $isYes ? 'box-selected' : '' }}">{!! $isYes ? '&#10004;' : '' !!}</span> Yes
                            </span>
                            <span class="checkbox-item">
                                <span class="box {{ $isNo ? 'box-selected' : '' }}">{!! $isNo ? '&#10004;' : '' !!}</span> No
                            </span>
                        @else
                            {{ $currentAnswer }}
                        @endif
                    </td>
                    <td></td>
                </tr>
            @endforeach
        </tbody>
    </table>

</body>

</html>