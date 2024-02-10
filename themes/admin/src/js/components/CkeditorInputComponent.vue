<template>
  <div>
    <div class="form-group">
      <label :for="id">{{ label }}</label>
      <textarea :name="name" :id="id" class="form-control" :class="classes + (errors ? 'is-invalid' : '')" v-model="model_value"></textarea>
      <div class="invalid-feedback d-block" v-if="errors">{{ errors }}</div>
    </div>
    <div v-for="(file, index) in attached_files" :key="index">
      <input type="hidden" :name="filesInputName" v-model="file.id">
    </div>
  </div>
</template>

<script>
/* global ClassicEditor */
export default {
  name: "CkeditorInputComponent",
  props: {
    label: {
      type: String,
      default: 'Message'
    },
    id: {
      type: String,
      default: ''
    },
    filesInputName: {
      type: String,
      default: 'attached_files[]'
    },
    name: {
      type: String,
      default: ''
    },
    classes: {
      type: String,
      default: ''
    },
    value: {
      type: String,
      default: ''
    },
    errors: {
      type: String,
      default: ''
    },
    language: {
      type: String,
      default: 'en'
    },
    upload_url: {
      type: String,
      default: ''
    },
    csrf_token: {
      type: String,
      default: ''
    },
  },
  data()
  {
    return {
      model_value: this.value,
      attached_files: [],
    }
  },
  mounted()
  {
    let config = {
      simpleUpload: {
        uploadUrl: this.upload_url,
        headers: {
          'X-CSRF-Token': this.csrf_token,
          'X-Requested-With': 'XMLHttpRequest',
        },
        withCredentials: false,
        savedCallback: (file) => {
          this.attached_files.push(file);
        },
      },
      language: this.language
    };

    ClassicEditor
      .create(document.querySelector('#' + this.id), config)
      .then(editor => {
        window.editor = editor;
      });
  }
}
</script>
