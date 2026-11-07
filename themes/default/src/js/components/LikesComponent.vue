<template>
  <div class="site-news-vote" role="group" :aria-label="i18n.rating" :aria-busy="loading">
    <button type="button" @click="setVote(1)" :class="{'site-news-vote__active': current_voted > 0}"
            :disabled="loading || current_voted > 0 || !can_vote" :aria-pressed="current_voted > 0" :aria-label="i18n.like" :title="i18n.like">
      <svg class="site-icon site-icon--lucide" width="14" height="14" aria-hidden="true"><use :href="icon_sprite + '#thumbs-up'" /></svg>
    </button>
    <span :class="rating_color" class="site-news-vote__rating" role="status">{{ current_rating > 0 ? '+' : '' }}{{ current_rating }}</span>
    <button type="button" @click="setVote(0)" :class="{'site-news-vote__active': current_voted < 0}"
            :disabled="loading || current_voted < 0 || !can_vote" :aria-pressed="current_voted < 0" :aria-label="i18n.dislike" :title="i18n.dislike">
      <svg class="site-icon site-icon--lucide" width="14" height="14" aria-hidden="true"><use :href="icon_sprite + '#thumbs-down'" /></svg>
    </button>
    <span class="visually-hidden" v-if="loading">{{ i18n.loading }}</span>
  </div>
</template>

<script>
export default {
  name: "LikesComponent",
  props: {
    article_id: Number,
    icon_sprite: String,
    i18n: {
      type: Object,
      required: true,
    },
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
  data()
  {
    return {
      message: '',
      loading: false,
      // Props are read-only, the vote result is kept in local state.
      current_rating: this.rating,
      current_voted: this.voted,
    }
  },
  computed: {
    rating_color: function () {
      let class_name = '';
      if (this.current_rating > 0) {
        class_name = 'text-success';
      } else if (this.current_rating < 0) {
        class_name = 'text-danger'
      }
      return class_name;
    }
  },
  methods: {
    setVote(type)
    {
      this.loading = true;
      axios.post(this.set_vote_url + this.article_id + '/' + type + '/')
        .then(response => {
          this.current_rating = response.data.rating;
          this.current_voted = response.data.voted;
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
