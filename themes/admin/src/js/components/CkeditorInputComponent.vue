<template>
    <div>
        <div class="form-group">
            <label :for="id">{{ label }}</label>
            <textarea :name="name" :id="id" class="form-control" :class="classes + (errors ? 'is-invalid' : '')" v-model="model_value"></textarea>
            <div class="invalid-feedback d-block" v-if="errors">{{ errors }}</div>
        </div>
        <div v-for="file in attached_files">
            <input type="hidden" name="attached_files[]" v-model="file.id">
        </div>
    </div>
</template>

<script>
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
        const self = this;
        let config = {
            simpleUpload: {
                uploadUrl: this.upload_url,
                withCredentials: false,
                savedCallback: function (file) {
                    self.attached_files.push(file);
                },
            },
            language: this.language
        };

        ClassicEditor
                .create(document.querySelector('#' + this.id), config)
                .then(editor => {
                    window.editor = editor;
                })
                .catch(error => {
                    console.error(error);
                });
    }
}
</script>
