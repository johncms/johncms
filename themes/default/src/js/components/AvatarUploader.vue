<template>
    <div>
        <div class="profile-photo-upload-btn cursor-pointer has-photo"
             data-bs-toggle="modal"
             data-bs-target="#uploadAvatar"
             :style="'background: url('+ imgUrl +')'">
            <div class="text-center add-photo-btn" v-if="!imgUrl">
                <div class="add-photo-icon">+</div>
                <div class="add-photo-text">Add photo</div>
            </div>
            <div class="change-photo-btn" v-if="imgUrl">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-camera">
                    <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path>
                    <circle cx="12" cy="13" r="4"></circle>
                </svg>
            </div>
        </div>
        <avatar-uploader-bs-modal
                modal-id="uploadAvatar"
                field="img"
                @crop-success="cropSuccess"
                @crop-upload-success="cropUploadSuccess"
                @crop-upload-fail="cropUploadFail"
                v-model="show"
                :width="300"
                :height="300"
                url="/upload"
                :params="params"
                :headers="headers"
                :langType="langType"
                img-format="png"
        ></avatar-uploader-bs-modal>
    </div>
</template>

<script lang="ts">
import AvatarUploaderBsModal from "./AvatarUploaderBsModal.vue";

export default {
    components: {AvatarUploaderBsModal},
    props: {
        langType: {
            type: String,
            'default': 'ru'
        },
        currentAvatar: {
            type: String,
            'default': ''
        }
    },
    data() {
        return {
            show: false,
            params: {
                token: '123456798',
                name: 'avatar'
            },
            headers: {},
            imgUrl: this.currentAvatar
        }
    },
    methods: {
        toggleShow() {
            this.show = !this.show;
        },
        /**
         * crop success
         *
         * [param] imgDataUrl
         * [param] field
         */
        cropSuccess(imgDataUrl, field) {
            this.imgUrl = imgDataUrl;
        },
        /**
         * upload success
         *
         * [param] jsonData  server api return data, already json encode
         * [param] field
         */
        cropUploadSuccess(jsonData, field) {
            console.log('-------- upload success --------');
            console.log(jsonData);
            console.log('field: ' + field);
        },
        /**
         * upload fail
         *
         * [param] status    server api return error status, like 500
         * [param] field
         */
        cropUploadFail(status, field) {
            console.log('-------- upload fail --------');
            console.log(status);
            console.log('field: ' + field);
        }
    }
}
</script>

<style scoped>

</style>
