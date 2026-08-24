<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-size: 1rem;
    font-family: "ubuntu-regular", sans-serif;
    -webkit-font-smoothing: antialiased;
}
/* CARD */
.card-body {
    width: 360px;
    height: 566px;
    position: relative;
    background-image: url('{{ $settings["background_image"] 
        ? public_path("storage/".$settings["background_image"]) 
        : public_path("id_card_bg.jpg") }}');
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
}
/* LOGO */
.logo-wrapper {
    position: absolute;
    left: 50%;
    transform: translateX(-50%);
}

.logo-box {
    width: auto;
    height: 75px;
    display: flex;
    justify-content: center;
    align-items: center;
    border-radius: 6px;
    overflow: hidden;
    padding-top: 18%;


}

.logo-box img {
    width: auto;
    height: 70px;
    object-fit: contain;
}

/* PHOTO */
.photo-frame {
    position: absolute;
    top: 130px;
    left: 50%;
    transform: translateX(-50%);
    width: 170px;
    height: 170px;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* STUDENT IMAGE (ROUND / SQUARE CONTROL) */
.student-profile {
    width: 170px;
    height: 170px;
    object-fit: cover;
    border: 4px solid {{ $settings['secondary_color'] ?? '#f4a000' }};   
    border-radius:
        @if (isset($settings['profile_image_style']) && $settings['profile_image_style'] == 'squre')
            2px
        @else
            50%
        @endif
    ;
}

/* DETAILS */
.details {
    position: absolute;
    top: 330px;
    left: 30px;
    right: 30px;
    font-size: 13px;
    line-height: 18px;
    font-weight: bold;
}

.details span {
    font-weight: normal;
}

/* FOOTER */
.footer {
    position: absolute;
    bottom: 10px;
    left: 0;
    right: 0;
    text-align: center;
    font-size: 13px;
    line-height: 14px;
}
</style>
</head>

<body>

@foreach ($students as $student)
<div class="card-body">
    <!-- LOGO -->
    <div class="logo-wrapper">
        <div class="logo-box">
            @if (isset($student->student->campus) && $student->student->campus == 'JIAM')
                <div style="text-align: center; color: navy;">
                    <h1 style="margin: 0; line-height: 1; font-size: 32px;">JIAM</h1>
                    <h4 style="margin: 0; line-height: 1.2; margin-top: 4px;">English Medium School</h4>
                </div>
            @elseif (!empty($settings['horizontal_logo']))
                <img src="{{ public_path('storage/'.$settings['horizontal_logo']) }}">
            @else
                <img src="{{ public_path('assets/horizontal-logo2.svg') }}">
            @endif
        </div>
    </div>

    <!-- PHOTO -->
    <div class="photo-frame">
        @if ($student->getRawOriginal('image'))
            <img class="student-profile"
                 src="{{ public_path('storage/'.$student->getRawOriginal('image')) }}">
        @else
            <img class="student-profile"
                 src="{{ public_path('assets/dummy_logo.jpg') }}">
        @endif
    </div>

    <!-- DETAILS -->
    <div class="details">
        <table width="100%">
            <tr>
                <td width="50">Name :</td>
                <td><span>{{ $student->full_name }}</span></td>
            </tr>
            <tr>
                <td>Class :</td>
                <td><span>{{ $student->student->class_section->full_name }}</span></td>
            </tr>
            <tr>
                <td>DOB :</td>
                <td><span>{{ $student->dob }}</span></td>
            </tr>
            <tr>
                <td>Mobile :</td>
                <td><span>{{ $student->mobile }}</span></td>
            </tr>
            <tr>
                <td valign="top">Address :</td>
                <td><span>{!! nl2br(e($student->current_address)) !!}</span></td>
            </tr>
        </table>
    </div>

    <!-- FOOTER -->
    <div class="footer" @if(isset($student->student->campus) && $student->student->campus == 'JIAM') style="bottom: 35px;" @endif>
        @php
            $schoolCode = \App\Models\School::find($student->school_id)->code ?? '';
        @endphp

        @if (isset($student->student->campus) && $student->student->campus == 'JIAM')
            Saudagar park behind sunpharma lab tandalja<br>
            Contact: 8140027986, 6355162422
        @else
            @php
                $words = explode(' ', $settings['school_address'] ?? '');
                $chunks = array_chunk($words, 5);
            @endphp

            @foreach($chunks as $chunk)
                {{ implode(' ', $chunk) }}<br>
            @endforeach

            @if ($schoolCode == 'GKGO202558')
                Contact: 8140027986, 6355162422
            @elseif ($schoolCode == 'LTA20251')
                Contact: 9737371796, 9328301833
            @elseif ($schoolCode == 'GKGO202511')
                Contact: 9173282648, 7069410150
            @else
                Contact:  {{ $settings['school_phone'] }}
            @endif
        @endif
    </div>

</div>
@endforeach

</body>
</html>
