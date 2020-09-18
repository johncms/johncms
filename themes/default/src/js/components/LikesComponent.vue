<template>
    <div class="position-relative">
        <div class="d-flex justify-content-center position-absolute w-100 vote-preloader" v-if="loading">
            <div class="spinner-border text-secondary" role="status">
                <span class="sr-only">Loading...</span>
            </div>
        </div>
        <button class="btn btn-light btn-sm" @click="setVote(1)" :class="voted > 0 ? 'liked' : ''" :disabled="voted > 0 || !can_vote">
            <svg class="icon download-button-icon mt-n1">
                <use xlink:href="/themes/default/assets/icons/sprite.svg#like"/>
            </svg>
        </button>
        <span :class="rating_color" class="ml-2 mr-2 font-weight-bold">{{ rating > 0 ? '+' : '' }}{{ rating }}</span>
        <button class="btn btn-light btn-sm" @click="setVote(-1)" :class="voted < 0 ? 'disliked' : ''" :disabled="voted < 0 || !can_vote">
            <svg class="icon download-button-icon mr-1">
                <use xlink:href="/themes/default/assets/icons/sprite.svg#dislike"/>
            </svg>
        </button>
    </div>
</template>

<script>
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
            default: '/news/',
        }
    },
    data()
    {
        return {
            message: '',
            loading: false,
        }
    },
    computed: {
        rating_color: function () {
            let class_name = '';
            if (this.rating > 0) {
                class_name = 'text-success';
            } else if (this.rating < 0) {
                class_name = 'text-danger'
            }
            return class_name;
        }
    },
    methods: {
        setVote(type)
        {
            this.loading = true;
            axios.get(this.set_vote_url + '?action=set_vote&type_vote=' + type + '&article_id=' + this.article_id)
                    .then(response => {
                        this.rating = response.data.rating;
                        this.voted = response.data.voted;
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
