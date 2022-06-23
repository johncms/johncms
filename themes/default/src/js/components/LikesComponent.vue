<template>
  <div class="position-relative">
    <div class="d-flex justify-content-center position-absolute w-100 vote-preloader" v-if="loading">
      <div class="spinner-border text-secondary" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
    </div>
    <button class="btn btn-light btn-sm" @click="setVote(1)" :class="isVoted > 0 ? 'liked' : ''" :disabled="isVoted > 0 || !can_vote">
      <svg class="icon download-button-icon mt-n1">
        <use xlink:href="/themes/default/assets/icons/sprite.svg#like"/>
      </svg>
    </button>
    <span :class="rating_color" class="ms-2 me-2 fw-bold">{{ currentRating > 0 ? '+' : '' }}{{ currentRating }}</span>
    <button class="btn btn-light btn-sm" @click="setVote(0)" :class="isVoted < 0 ? 'disliked' : ''" :disabled="isVoted < 0 || !can_vote">
      <svg class="icon download-button-icon me-1">
        <use xlink:href="/themes/default/assets/icons/sprite.svg#dislike"/>
      </svg>
    </button>
  </div>
</template>

<script lang="ts">
import axios from "axios";

export default {
  name: "LikesComponent",
  props: {
    article_id: Number,
    rating: {
      type: Number,
      default: 0
    },
    can_vote: {
      type: Boolean,
      default: false,
    },
    voted: {
      type: Number,
      default: 0,
    },
    set_vote_url: {
      type: String,
      default: '/news/add_vote/',
    }
  },
  data() {
    return {
      message: '',
      loading: false,
      isVoted: this.voted,
      currentRating: this.rating,
    }
  },
  computed: {
    rating_color: function () {
      let class_name = '';
      if (this.currentRating > 0) {
        class_name = 'text-success';
      } else if (this.currentRating < 0) {
        class_name = 'text-danger'
      }
      return class_name;
    }
  },
  methods: {
    setVote(type: number) {
      this.loading = true;
      axios.post(this.set_vote_url + this.article_id + '/' + type + '/')
        .then(response => {
          this.currentRating = response.data.rating;
          this.isVoted = response.data.voted;
          this.message = response.data.message;
          this.loading = false;
        })
        .catch(error => {
          alert(error);
          this.loading = false;
        });
    }
  }
}
</script>
