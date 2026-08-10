<template>
  <div>
    <div class="form-group">
      <label :for="id">{{ label }}</label>
      <textarea
        :name="name"
        :id="id"
        class="form-control"
        :class="[classes, errors ? 'is-invalid' : '']"
        v-model="model_value"
      ></textarea>
      <div class="invalid-feedback d-block" v-if="errors">{{ errors }}</div>
    </div>

    <div v-for="file in attached_files" :key="file.id">
      <input type="hidden" name="attached_files[]" :value="file.id">
    </div>
  </div>
</template>
<script setup>
import { ref, onMounted } from 'vue';
import { csrfHeaders } from '../csrf';

const props = defineProps({
  label: { type: String, default: 'Message' },
  id: { type: String, default: '' },
  name: { type: String, default: '' },
  classes: { type: String, default: '' },
  value: { type: String, default: '' },
  errors: { type: String, default: '' },
  language: { type: String, default: 'en' },
  upload_url: { type: String, default: '' },
  allow_upload: { type: Boolean, default: true }
});

const model_value = ref(props.value);
const attached_files = ref([]);

onMounted(() => {
  const toolbarItems = [
    'heading', '|', 'bold', 'italic', 'link', 'fontColor',
    'bulletedList', 'numberedList', 'removeFormat', '|',
    'codeBlock'
  ];

  if (props.allow_upload) {
    toolbarItems.push('insertImage');
  }

  toolbarItems.push('blockQuote', 'insertTable', 'mediaEmbed', 'undo', 'redo');

  const config = {
    language: props.language,
    toolbar: {
      items: toolbarItems
    }
  };

  if (props.allow_upload && props.upload_url) {
    config.simpleUpload = {
      uploadUrl: props.upload_url,
      withCredentials: false,
      // The adapter sends its own XHR, so the axios defaults do not apply to it.
      headers: csrfHeaders(),
      savedCallback: (file) => {
        attached_files.value.push(file);
      },
    };
  }

  ClassicEditor
    .create(document.querySelector(`#${props.id}`), config)
    .then(editor => {
      editor.model.document.on('change:data', () => {
        model_value.value = editor.getData();
      });
      window.editor = editor;
    })
    .catch(error => {
      console.error(error);
    });
});
</script>
