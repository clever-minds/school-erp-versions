<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Certificate</title>
    <link rel="stylesheet" href="{{ asset('/assets/css/certificate.css') }}" />
    <style>
        body {
            margin: 0;
            padding: 0;
        }
        .certificate {
            width: 100%;
        }
        .sheet {
            width: {{ $layout['width'] }};
            height: {{ $layout['height'] }};
            page-break-after: always;
            position: relative;
            overflow: hidden;
        }

        .sheet:last-child {
            page-break-after: auto;
        }
        .template {
            width: {{ $layout['width'] }};
            height: {{ $layout['height'] }};
            position: relative;
            background-color: #fff;
        }
        .frame-border {
            width: 100%;
            height: 100%;
            border: 1px solid black;
            box-sizing: border-box;
        }
        .background-image {
            width: 100%;
            height: 100%;
            position: absolute;
            top: 0;
            left: 0;
            z-index: 1;
        }
        .pdf-element {
            position: absolute;
            z-index: 2;
            word-wrap: break-word;
        }
        .pdf-element img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        @if ($certificateTemplate->user_image_shape == 'Round')
            .user_image {
                border-radius: 50%;
                object-fit: cover !important;
            }
        @else
            .user_image {
                border-radius: 6%;
                object-fit: cover !important;
            }
        @endif
    </style>
</head>

<body>
    <div class="certificate">
        @foreach ($assignments as $assignment)
        @php
            $user = $assignment->user;
            $template = $assignment->certificate_template;
        @endphp
        <div class="sheet">
            <div class="template">

                {{-- Background image --}}
                @if ($template->background_image)
                    <img src="{{ $template->background_image }}" class="background-image" alt="">
                @else
                    <div class="frame-border"></div>
                @endif
                
                {{-- Elements --}}
                @if(!empty($assignment->elements) && count($assignment->elements) > 0)
                    @foreach($assignment->elements as $element)
                        @php
                            $styleString = '';
                            if(isset($element['style'])) {
                                foreach($element['style'] as $key => $val) {
                                    $kebabKey = strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $key));
                                    
                                    // Append px to numeric values meant for sizes
                                    if(in_array($key, ['fontSize', 'width', 'height']) && is_numeric($val)) {
                                        $val = $val . 'px';
                                    }
                                    
                                    // Validate positioning
                                    if(in_array($key, ['top', 'left']) && is_numeric($val)) {
                                        $val = $val . 'px';
                                    }

                                    $styleString .= "{$kebabKey}: {$val}; ";
                                }
                            }
                        @endphp
                        
                        <div class="pdf-element" style="{!! $styleString !!}">
                            @if($element['type'] == 'text')
                                {!! $element['content'] !!}
                            @elseif($element['type'] == 'image')
                                @php
                                    $src = '';
                                    if($element['value'] == '{horizontal_logo}') $src = $settings['horizontal_logo'] ?? '';
                                    elseif($element['value'] == '{vertical_logo}' || $element['value'] == '{school_logo}') $src = $settings['vertical_logo'] ?? '';
                                    elseif($element['value'] == '{signature}') $src = $settings['signature'] ?? '';
                                    elseif($element['value'] == '{user_image}') $src = $assignment->image ?? '';
                                    else $src = $element['content'] ?? ''; // fallback for custom images
                                @endphp
                                @if($src)
                                    <img src="{{ $src }}" alt="" class="{{ $element['value'] == '{user_image}' ? 'user_image' : '' }}">
                                @endif
                            @endif
                        </div>
                    @endforeach
                @endif

            </div>
        </div>
        @endforeach
    </div>
</body>
</html>
