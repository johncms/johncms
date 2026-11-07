import { Tooltip } from 'bootstrap';

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
      // The caption is built from text nodes: the title and the source come from user content.
      titleSrc: function (item) {
        const title = document.createElement('span');
        title.textContent = item.el.attr('title') || '';
        const source = item.el.attr('data-source');
        if (source) {
          const url = new URL(source, window.location.href);
          if (['http:', 'https:'].includes(url.protocol)) {
            const link = document.createElement('a');
            link.href = url.href;
            link.target = '_blank';
            link.rel = 'noopener';
            link.className = 'image-source-link';
            link.textContent = 'Download';
            title.append(' · ', link);
          }
        }
        return title.innerHTML;
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
  document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
    Tooltip.getOrCreateInstance(el);
  });
});

$(".custom-file-input").on("change", function () {
  var fileName = $(this).val().split("\\").pop();
  $(this).siblings(".custom-file-label").addClass("selected").html(fileName);
});
