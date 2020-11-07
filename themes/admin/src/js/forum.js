$('#first_post')
        .on('hide.bs.collapse', function (e) {
            togglePreview();
        })
        .on('shown.bs.collapse', function () {
            togglePreview();
        });

function togglePreview()
{
    $('#first_post_block .post-preview').toggle(0);
}

$(function () {
    $('.image-gallery').each(function () {
        $(this).magnificPopup({
            delegate: '.gallery-item',
            type: 'image',
            tLoading: 'Loading image #%curr%...',
            mainClass: 'mfp-img-mobile',
            gallery: {
                enabled: true,
                navigateByImgClick: true,
                preload: [0, 1]
            },
            image: {
                tError: '<a href="%url%">The image #%curr%</a> could not be loaded.',
                titleSrc: function (item) {
                    return item.el.attr('title') + ' &middot; <a class="image-source-link" href="' + item.el.attr('data-source') + '" target="_blank">Download</a>';
                }
            },
            zoom: {
                enabled: true,
                duration: 300,
                opener: function (element) {
                    return element.find('img');
                }
            },
        });
    });
    $('.image-preview').magnificPopup({
        type: 'image',
        image: {
            verticalFit: true,
            titleSrc: function (item) {
                return item.el.attr('title') + ' &middot; <a class="image-source-link" href="' + item.el.attr('data-source') + '" target="_blank">Download</a>';
            }
        },
        zoom: {
            enabled: true,
            duration: 300,
            opener: function (element) {
                return element.find('img');
            }
        }
    });
    $('[data-toggle="tooltip"]').tooltip();
});

$(".custom-file-input").on("change", function () {
    var fileName = $(this).val().split("\\").pop();
    $(this).siblings(".custom-file-label").addClass("selected").html(fileName);
});
