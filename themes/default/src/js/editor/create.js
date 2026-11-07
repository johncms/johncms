import {
  ClassicEditor, Essentials, Paragraph, Heading, Bold, Italic, List, Link, CodeBlock, BlockQuote,
  ImageBlock, ImageInline, ImageInsertViaUrl, ImageUpload, ImageResize, ImageStyle, ImageToolbar,
  ImageCaption, ImageTextAlternative, Table, TableToolbar, FontColor, RemoveFormat, MediaEmbed,
  SimpleUploadAdapter, PendingActions,
} from 'ckeditor5';
import 'ckeditor5/ckeditor5.css';
import '../../scss/editor.scss';
import { csrfHeaders } from '../csrf';

const translations = import.meta.glob([
  '/node_modules/ckeditor5/dist/translations/*.js',
  '!/node_modules/ckeditor5/dist/translations/*.umd.js',
]);

/** The public editor: what visitors write with in posts, comments and articles. */
export async function createEditor(textarea, { language, uploadUrl }) {
  const locale = language.replaceAll('_', '-').toLowerCase();
  const translation = translations[`/node_modules/ckeditor5/dist/translations/${locale}.js`]
    ?? translations[`/node_modules/ckeditor5/dist/translations/${locale.split('-')[0]}.js`];
  const dictionary = translation ? (await translation()).default : null;
  const uiLanguage = dictionary ? Object.keys(dictionary)[0] : 'en';

  const plugins = [
    Essentials, PendingActions, Paragraph, Heading, Bold, Italic, List, Link, CodeBlock, BlockQuote,
    ImageBlock, ImageInline, ImageInsertViaUrl, ImageResize, ImageStyle, ImageToolbar, ImageCaption,
    ImageTextAlternative, Table, TableToolbar, FontColor, RemoveFormat, MediaEmbed,
  ];
  if (uploadUrl) {
    plugins.push(ImageUpload, SimpleUploadAdapter);
  }

  return ClassicEditor.create({
    attachTo: textarea,
    licenseKey: 'GPL',
    plugins,
    language: { ui: uiLanguage, content: locale },
    ui: { viewportOffset: { top: document.querySelector('.site-topbar')?.getBoundingClientRect().height ?? 0 } },
    translations: dictionary ? [dictionary] : [],
    toolbar: {
      items: [
        'heading', '|', 'bold', 'italic', 'link', 'fontColor', 'bulletedList', 'numberedList',
        'removeFormat', '|', 'codeBlock', ...(uploadUrl ? ['insertImage'] : []),
        'blockQuote', 'insertTable', 'mediaEmbed', 'undo', 'redo',
      ],
    },
    image: {
      styles: { options: ['alignLeft', 'alignCenter', 'alignRight'] },
      toolbar: ['imageStyle:alignLeft', 'imageStyle:alignCenter', 'imageStyle:alignRight', '|', 'imageTextAlternative'],
    },
    codeBlock: {
      languages: [
        { language: 'php', label: 'PHP', class: 'language-php line-numbers' },
        { language: 'css', label: 'CSS', class: 'language-css line-numbers' },
        { language: 'javascript', label: 'JavaScript', class: 'language-javascript line-numbers' },
        { language: 'html', label: 'HTML', class: 'language-html line-numbers' },
        { language: 'sql', label: 'SQL', class: 'language-sql line-numbers' },
        { language: 'xml', label: 'XML', class: 'language-xml line-numbers' },
      ],
    },
    table: { contentToolbar: ['tableColumn', 'tableRow', 'mergeTableCells'] },
    heading: {
      options: [
        { model: 'paragraph', title: 'Paragraph', class: 'ck-heading_paragraph' },
        { model: 'heading2', view: 'h2', title: 'Heading 2', class: 'ck-heading_heading2' },
        { model: 'heading3', view: 'h3', title: 'Heading 3', class: 'ck-heading_heading3' },
        { model: 'heading4', view: 'h4', title: 'Heading 4', class: 'ck-heading_heading4' },
      ],
    },
    ...(uploadUrl ? { simpleUpload: { uploadUrl, headers: csrfHeaders() } } : {}),
  });
}
