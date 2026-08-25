/**
 * photo_file.js
 *
 * Gallery page JS — handles:
 *  1. Smooth image-load reveal (fade-in once each image is loaded)
 *  2. Lightbox open on gallery card click (delegated, works on dynamically added items too)
 *
 * Layout is handled entirely by CSS column-count (photo_file.css).
 * No waterfall/masonry JS plugin is required.
 */

$(document).ready(function () {

    // -------------------------------------------------------
    // 1. Fade-in each gallery card once its image has loaded
    // -------------------------------------------------------
    var $items = $('#waterfall li');

    $items.each(function () {
        var $li = $(this);
        var $img = $li.find('img.thumbnail');

        $li.css({ opacity: 0, transition: 'opacity 0.4s ease' });

        if ($img.length) {
            if ($img[0].complete && $img[0].naturalWidth > 0) {
                // Image already loaded from cache
                $li.css('opacity', 1);
            } else {
                $img.on('load', function () {
                    $li.css('opacity', 1);
                }).on('error', function () {
                    // Still show card even if image fails to load
                    $li.css('opacity', 1);
                });
            }
        } else {
            $li.css('opacity', 1);
        }
    });

    // -------------------------------------------------------
    // 2. Lightbox — delegated click on the gallery card
    //    (footer_js.blade.php handles the actual open/close;
    //     this ensures clicks on the card itself trigger the
    //     detailArr onclick set by footer_js.blade.php)
    // -------------------------------------------------------
    $(document).on('click', '#waterfall li > div.gallery-card', function () {
        var $detailArr = $(this).find('.detailArr');
        if ($detailArr.length) {
            $detailArr.trigger('click');
        }
    });

    // Prevent double-triggering when clicking directly on .detailArr
    $(document).on('click', '#waterfall .detailArr', function (e) {
        e.stopPropagation();
    });

});
