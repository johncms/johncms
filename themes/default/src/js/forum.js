$('#first_post')
    .on('hide.bs.collapse', function (e) {
        togglePreview();
    })
    .on('shown.bs.collapse', function () {
        togglePreview();
    });

function togglePreview() {
    $('#first_post_block .post-preview').toggle(0);
}
