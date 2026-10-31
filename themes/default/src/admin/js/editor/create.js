import {
  ClassicEditor, Essentials, Paragraph, Heading, Bold, Italic, Underline, Strikethrough,
  List, Link, CodeBlock, BlockQuote, ImageBlock, ImageInline, ImageInsert, ImageUpload,
  ImageResize, ImageStyle, ImageToolbar, ImageCaption, ImageTextAlternative,
  Table, TableToolbar, TableProperties, TableCellProperties,
  FontColor, FontBackgroundColor, FontFamily, FontSize, RemoveFormat, MediaEmbed,
  SimpleUploadAdapter, PendingActions,
} from 'ckeditor5';
import 'ckeditor5/ckeditor5.css';
import '../../scss/editor.scss';
import { csrfHeaders } from '../csrf';

const translations = import.meta.glob([
  '/node_modules/ckeditor5/dist/translations/*.js',
  '!/node_modules/ckeditor5/dist/translations/*.umd.js',
]);

/** Keep the panel's features and configuration separate from the public editor. */
export async function createEditor(textarea, { language, uploadUrl }) {
  const locale = language.replace('_', '-').toLowerCase();
  const translation = translations[`/node_modules/ckeditor5/dist/translations/${locale}.js`]
    ?? translations[`/node_modules/ckeditor5/dist/translations/${locale.split('-')[0]}.js`];
  const dictionary = translation ? (await translation()).default : null;
  const uiLanguage = dictionary ? Object.keys(dictionary)[0] : 'en';

  const plugins = [
    Essentials, PendingActions, Paragraph, Heading, Bold, Italic, Underline, Strikethrough,
    List, Link, CodeBlock, BlockQuote, ImageBlock, ImageInline, ImageResize, ImageStyle,
    ImageToolbar, ImageCaption, ImageTextAlternative, Table, TableToolbar, TableProperties,
    TableCellProperties, FontColor, FontBackgroundColor, FontFamily, FontSize, RemoveFormat, MediaEmbed,
  ];
  if (uploadUrl) {
    plugins.push(ImageInsert, ImageUpload, SimpleUploadAdapter);
  }

  return ClassicEditor.create({
    attachTo: textarea,
    licenseKey: 'GPL',
    plugins,
    language: { ui: uiLanguage, content: language },
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
      resizeOptions: [
        { name: 'resizeImage:original', value: null, icon: 'original' },
        { name: 'resizeImage:50', value: '50', icon: 'medium' },
        { name: 'resizeImage:75', value: '75', icon: 'large' },
      ],
      toolbar: [
        'imageStyle:alignLeft', 'imageStyle:alignCenter', 'imageStyle:alignRight', '|',
        'resizeImage', '|', 'imageTextAlternative',
      ],
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
    table: {
      contentToolbar: ['tableColumn', 'tableRow', 'mergeTableCells', 'tableProperties', 'tableCellProperties'],
    },
    heading: {
      options: [
        { model: 'paragraph', title: 'Paragraph', class: 'ck-heading_paragraph' },
        { model: 'heading2', view: 'h2', title: 'Heading 2', class: 'ck-heading_heading2' },
        { model: 'heading3', view: 'h3', title: 'Heading 3', class: 'ck-heading_heading3' },
        { model: 'heading4', view: 'h4', title: 'Heading 4', class: 'ck-heading_heading4' },
        ...['success', 'info', 'danger', 'warning'].map(type => ({
          model: `alert${type[0].toUpperCase()}${type.slice(1)}`,
          view: { name: 'div', classes: ['alert', `alert-${type}`] },
          title: `alert-${type}`,
          class: `alert alert-${type}`,
        })),
      ],
    },
    ...(uploadUrl ? { simpleUpload: { uploadUrl, headers: csrfHeaders() } } : {}),
  });
}
