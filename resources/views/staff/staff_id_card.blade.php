<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">

<style>
*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

body{
    font-family:'DejaVu Sans',sans-serif;
}

/* CARD */
.card-body{
    width:360px;
    height:566px;
    position:relative;
    background-image:url('{{ $settings["staff_background_image"] 
        ? public_path("storage/".$settings["staff_background_image"]) 
        : public_path("assets/id_card_bg.jpg") }}');
    background-size:cover;
    background-position:center;
}

/* LOGO */
.logo-wrapper{
    position:absolute;
    left:50%;
    transform:translateX(-50%);
}

.logo-box{
    width:auto;
    height:70px;
    display:flex;
    justify-content:center;
    align-items:center;
    padding-top:18%;
}

.logo-box img{
    width:auto;
    height:50px;
    object-fit:contain;
}

/* PHOTO */
.photo-frame{
    position:absolute;
    top:130px;
    left:50%;
    transform:translateX(-50%);
    width:170px;
    height:170px;
    display:flex;
    align-items:center;
    justify-content:center;
}

/* PROFILE IMAGE */
.staff-profile{
    width:170px;
    height:170px;
    object-fit:cover;
    border:4px solid {{ $settings['secondary_color'] ?? '#f4a000' }};
    border-radius:
        @if(isset($settings['staff_profile_image_style']) && $settings['staff_profile_image_style']=='squre')
        5px
        @else
        50%
        @endif
    ;
}

/* DETAILS */
.details{
    position:absolute;
    top:330px;
    left:30px;
    right:30px;
    font-size:13px;
    line-height:18px;
}

.details span{
    font-weight:normal;
}

/* FOOTER */
.footer{
    position:absolute;
    bottom:15px;
    left:0;
    right:0;
    text-align:center;
    font-size:12px;
}
</style>
</head>

<body>

@foreach ($users as $user)

<div class="card-body">

    <!-- LOGO -->
    <div class="logo-wrapper">
        <div class="logo-box">
            @if(!empty($settings['horizontal_logo']))
                <img src="{{ public_path('storage/'.$settings['horizontal_logo']) }}">
            @else
                <img src="{{ public_path('assets/horizontal-logo2.svg') }}">
            @endif
        </div>
    </div>

    <!-- PHOTO -->
    <div class="photo-frame">
        @if ($user->getRawOriginal('image'))
            <img class="staff-profile"
                 src="{{ public_path('storage/'.$user->getRawOriginal('image')) }}">
        @else
            <img class="staff-profile"
                 src="{{ public_path('assets/dummy_logo.jpg') }}">
        @endif
    </div>

    <!-- DETAILS -->
    <div class="details">
        <table width="100%">
            
            @if (in_array('name',$settings['staff_id_card_fields']))
            <tr>
                <td width="80">Name :</td>
                <td><span>{{ $user->full_name }}</span></td>
            </tr>
            @endif

            @if (in_array('role',$settings['staff_id_card_fields']))
            <tr>
                <td>Role :</td>
                <td><span>{{ implode(',',$user->roles->pluck('name')->toArray()) }}</span></td>
            </tr>
            @endif

            @if (in_array('contact',$settings['staff_id_card_fields']))
            <tr>
                <td>Mobile :</td>
                <td><span>{{ $user->mobile }}</span></td>
            </tr>
            @endif

            @if (in_array('email',$settings['staff_id_card_fields']))
            <tr>
                <td>Email :</td>
                <td><span>{{ $user->email }}</span></td>
            </tr>
            @endif

            @if (in_array('dob',$settings['staff_id_card_fields']))
            <tr>
                <td>DOB :</td>
                <td><span>{{ date($settings['date_format'],strtotime($user->dob)) }}</span></td>
            </tr>
            @endif

            @if (in_array('gender',$settings['staff_id_card_fields']))
            <tr>
                <td>Gender :</td>
                <td><span>{{ ucfirst($user->gender) }}</span></td>
            </tr>
            @endif

        </table>
    </div>

    <!-- FOOTER -->
    <div class="footer">
        Valid Until : {{ $valid_until }}
    </div>

</div>

@endforeach

</body>
</html>