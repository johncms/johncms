<template>
  <div class="position-relative">
    <div class="profile-photo-upload-btn cursor-pointer has-photo"
         data-bs-toggle="modal"
         data-bs-target="#uploadAvatar"
         :class="{'border-0': imgUrl}"
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
    <form :action="deleteUrl" method="post" v-if="imgUrl">
      <input type="hidden" name="csrf_token" :value="token">
      <button type="submit" class="btn btn-link delete-photo-btn text-decoration-none cursor-pointer" v-if="deleteUrl">&times;</button>
    </form>
    <avatar-uploader-bs-modal
      modal-id="uploadAvatar"
      field="avatar"
      @crop-upload-success="cropSuccess"
      v-model="show"
      :width="300"
      :height="300"
      :url="uploadUrl"
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
    },
    uploadUrl: {
      type: String,
      'default': ''
    },
    deleteUrl: {
      type: String,
      'default': ''
    },
    token: {
      type: String,
      'default': ''
    }
  },
  data() {
    return {
      show: false,
      params: {
        csrf_token: this.token
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
    cropSuccess(imgDataUrl: any) {
      this.imgUrl = imgDataUrl;
    },
  }
}
</script>
