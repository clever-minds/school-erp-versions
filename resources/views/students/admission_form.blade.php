<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Student Admission Form</title>

    <style>
        html {
            margin: 0px;
        }
        .text-left {
            text-align: left;
            padding-left: 5px;
        }
        .text-right {
            text-align: right;
        }
        .full-table-width
        {
            width: 100%;
        }
        .school-info th, .school-info td 
        {
            padding: 2px 5px;
        }
        .header {
            margin-top: 1rem;
        }
        .school-name {
            font-size: 24px;
        }
        table {
            border-collapse: collapse;
            border: none;
            font-size: 14px;
            z-index: 1;
        }
        .section-heading {
            margin-top: 0.5rem;
            letter-spacing: 1px;
            font-weight: 700;
            background-color: #E1E1E1;
            padding: 5px 0px;
        }
        .text-center {
            text-align: center;
        }
        .main-body {
            margin: 0rem 2rem;
        }
        .student-section {
            
        }
        .section {
            margin-top: 1rem;
        }
        
        .label {
            width: 4.5rem;
        }
        .section span {
            display: inline-block;
            width: auto;
        }
        .section .col-line {
            display: inline-block;
            
            
        }
        .line {
            border-bottom: 1px solid black;
            width: auto;
        }
        .box {
            border: 1px solid gray;
            padding: 0.8rem 0.7rem;
        }
        table
        {
            margin-bottom: 0.5rem;
        }
        .text-small {
            font-size: 12px;
        }
        .photo {
            width: 8rem;
            height: 10rem;
            border: 1px solid gray;
        }
        .note {
            padding: 5px;
            vertical-align: top;
            text-align: left;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <table class="full-table-width school-info">
                <tr>
                    <th class="text-left school-name">{{ $schoolSettings['school_name'] ?? 'eSchool' }}</th>
                    <th class="text-right" rowspan="3">
                        @if ($settings['horizontal_logo'] ?? '')
                            <img height="50" src="{{ public_path('storage/') . $settings['horizontal_logo'] }}" alt="">
                        @else
                            <img height="50" src="{{ public_path('assets/horizontal-logo2.svg') }}" alt="">
                        @endif
                    </th>
                </tr>
                <tr>
                    <td class="text-left">{{ $schoolSettings['school_address'] ?? '' }}</td>
                </tr>

                <tr>
                    <td class="text-left">{{ $schoolSettings['school_email'] ?? '' }} | {{ $schoolSettings['school_phone'] ?? '' }}</td>
                </tr>
            </table>
        </div>
        <div class="section-heading text-center">
            STUDENT REGISTRATION FORM
        </div>
        <div class="main-body">
            <table class="full-table-width">
                <tr>
                    <td class="note">
                        Before filing student registration form kindly make sure:
                        <ul class="text-small">
                            <li>
                                PLEASE FILL UP THE FORM IN CAPITAL LETTERS.
                            </li>
                            <li>
                                * FIELDS ARE MANDATORY.
                            </li>
                        </ul>
                    </td>

                    <td class="photo text-center">
                        Student photo
                    </td>
                    <td class="photo text-center">
                        Guardian photo
                    </td>
                </tr>
                
            </table>
            

            <div class="student-detail section-heading text-center">
                STUDENT DETAILS
            </div>

            <div class="student-section section">
                <table class="full-table-width">
                    <tr>
                        <th class="text-left label">
                            First Name
                        </th>
                        <td class="line"> </td>

                        <th class="text-right label">
                            Last Name 
                        </th>
                        <td class="line"> </td>
                    </tr>
                </table>
                <table class="full-table-width">
                    <tr>
                        <th class="text-left label">
                            DOB
                        </th>
                        <th class="box"></th>
                        <th class="box"></th>
                        <th class="box"></th>
                        <th class="box"></th>
                        <th class="box"></th>
                        <th class="box"></th>
                        <th class="box"></th>
                        <th class="box"></th>
                        <th class="text-right label">
                            Gender
                        </th>
                        <td>
                            <th class="box"></th>
                            <th class="text-left">Male</th>
                            <th class="box"></th>
                            <th class="text-left">Female</th>
                        </td>
                    </tr>
                    <tr>
                        <td></td>

                        <th class="text-small">D</th>
                        <th class="text-small">D</th>
                        <th class="text-small">M</th>
                        <th class="text-small">M</th>
                        <th class="text-small">Y</th>
                        <th class="text-small">Y</th>
                        <th class="text-small">Y</th>
                        <th class="text-small">Y</th>

                        <th colspan="5"></th>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</body>
</html>