<template>
    <div class="mt-4">
        <h3 class="font-weight-bold">{{__('comments')}} <span class="text-success" v-if="messages.total > 0">{{messages.total}}</span></h3>
        <div v-if="messages.data && messages.data.length < 1" class="alert alert-info">{{__('empty_list')}}</div>
        <div class="new_post-item" v-for="message in messages.data">
            <div class="new_post-header d-flex justify-content-between">
                <div class="post-user">
                    <a :href="message.user.profile_url" v-if="message.user.profile_url">
                        <div class="avatar">
                            <img :src="message.user.avatar" class="img-fluid" alt=".">
                        </div>
                    </a>
                    <span class="user-status shadow" :class="message.user.is_online ? 'online' : 'offline'"></span>
                    <div v-if="message.user.rights_name"
                         class="post-of-user"
                         data-toggle="tooltip"
                         data-placement="top"
                         data-html="true"
                         :title="message.user.rights_name">
                        <svg class="icon-post">
                            <use xlink:href="/themes/default/assets/icons/sprite.svg?#check"/>
                        </svg>
                    </div>
                </div>
                <div class="flex-grow-1 post-user d-flex flex-wrap overflow-hidden d-flex align-items-center">
                    <div class="w-100">
                        <a :href="message.user.profile_url" v-if="message.user.profile_url"><span class="user-name d-inline mr-2">{{message.user.user_name}}</span></a>
                        <div class="user-name d-inline mr-2" v-if="!message.user.profile_url">{{message.user.user_name}}</div>
                        <span class="post-meta d-inline mr-2"
                              data-toggle="tooltip"
                              data-placement="top"
                              title="Link to post">
                            {{ message.created_at }}
                        </span>
                    </div>
                    <div v-if="message.user.status" class="overflow-hidden text-nowrap text-dark-brown overflow-ellipsis small">
                        <span class="font-weight-bold">{{message.user.status}}</span>
                    </div>
                </div>
            </div>
            <div class="post-body pt-2 pb-2" v-html="message.text"></div>
            <div class="post-footer d-flex justify-content-between">
                <div class="overflow-hidden">
                    <div class="post-meta d-flex" v-if="message.ip">
                        <div class="user-ip mr-2">
                            <a :href="message.search_ip_url">{{ message.ip }}</a>
                        </div>
                        <div class="useragent">
                            <span>{{ message.user_agent }}</span>
                        </div>
                    </div>
                </div>
                <div class="d-flex">
                    <div class="ml-3" v-if="message.can_reply">
                        <a href="#" @click.prevent="reply(message)">{{__('reply')}}</a>
                    </div>
                    <div class="ml-3" v-if="message.can_quote">
                        <a href="#" @click.prevent="quote(message)">{{__('quote')}}</a>
                    </div>
                    <div class="dropdown ml-3" v-if="message.can_delete">
                        <div class="cursor-pointer" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <svg class="icon text-primary">
                                <use xlink:href="/themes/default/assets/icons/sprite.svg?#more_horizontal"/>
                            </svg>
                        </div>
                        <div class="dropdown-menu dropdown-menu-right">
                            <a class="dropdown-item" href="" @click.prevent="delComment(message.id)">{{__('delete')}}</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <pagination :data="messages" @pagination-change-page="getComments" class="mt-3"></pagination>

        <div class="mt-4" v-if="can_write">
            <h3 class="font-weight-bold">{{__('write_comment')}}</h3>
            <form action="" @submit.prevent="sendComment">
                <div class="d-flex" v-if="error_message">
                    <div class="alert alert-danger d-inline">{{error_message}}</div>
                </div>
                <div class="d-flex" v-if="comment_added_message">
                    <div class="alert alert-success d-inline">{{comment_added_message}}</div>
                </div>
                <div style="max-width: 800px;">
                    <textarea name="text" id="comment_text" rows="6" required class="form-control" v-model="comment_text"></textarea>
                </div>
                <div class="mt-2">
                    <button type="submit" name="submit" value="1" class="btn btn-primary" :disabled="loading">
                        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true" v-if="loading"></span>
                        {{__('send')}}
                    </button>
                    <div></div>
                </div>
            </form>
        </div>
    </div>
</template>

<script>
    export default {
        name: "CommentsComponent",
        props: {
            article_id: Number,
            can_write: {
                type: Boolean,
                default: false,
            },
            i18n: {
                type: Object,
                default: function () {
                    return {
                        write_comment: 'Write a comment',
                        send: 'Send',
                        delete: 'Delete',
                        quote: 'Quote',
                        reply: 'Reply',
                        comments: 'Comments',
                        empty_list: 'The list is empty',
                    }
                }
            }
        },
        data()
        {
            return {
                messages: {},
                comment_text: '',
                comment_added_message: '',
                error_message: '',
                loading: false,
            }
        },
        mounted()
        {
            this.getComments();
        },
        computed: {},
        methods: {
            getComments(page = 1)
            {
                this.loading = true;
                axios.get('/news/?action=comments&article_id=' + this.article_id + '&page=' + page)
                        .then(response => {
                            this.messages = response.data;
                            this.loading = false;
                        })
                        .catch(error => {
                            alert(error);
                            this.loading = false;
                        });
            },
            reply(message)
            {
                this.comment_text = '[b]' + message.user.user_name + '[/b], ';
                $('#comment_text').focus();
            },
            quote(message)
            {
                let text = message.text.replace(/(<([^>]+)>)/ig, "");
                this.comment_text = '[quote][b]' + message.user.user_name + "[/b] " + message.created_at + "\n" + text + "[/quote]";
                $('#comment_text').focus();
            },
            sendComment()
            {
                this.loading = true;
                axios.post('/news/?action=add_comment&article_id=' + this.article_id, {
                    comment: this.comment_text
                })
                        .then(response => {
                            this.comment_added_message = response.data.message;
                            this.loading = false;
                            this.comment_text = '';
                            this.error_message = '';
                            this.getComments(response.data.last_page);
                        })
                        .catch(error => {
                            this.error_message = error.response.data.message;
                            this.loading = false;
                        });
            },
            delComment(comment_id)
            {
                this.loading = true;
                axios.post('/news/?action=del_comment', {
                    comment_id: comment_id
                })
                        .then(response => {
                            this.getComments(this.messages.current_page);
                        })
                        .catch(error => {
                            alert(error.response.data.message);
                            this.loading = false;
                        });
            },
            __(message)
            {
                return _.get(this.i18n, message, '');
            }
        }
    }
</script>
