<template>
  <section class="site-news-comments comments-list" :aria-busy="loading" ref="list">
    <header class="site-news-comments__heading">
      <h2>{{ __('comments') }} <span class="site-news-comments__count" v-if="messages.total > 0">{{ messages.total }}</span></h2>
      <span class="site-news-comments__loading" role="status" v-if="loading">{{ __('loading') }}</span>
    </header>
    <div v-if="error_message" class="alert alert-danger" role="alert">{{ error_message }}</div>
    <div v-if="comment_added_message" class="alert alert-success" role="status">{{ comment_added_message }}</div>
    <div v-if="messages.data && messages.data.length < 1" class="site-news-comments__empty">{{ __('empty_list') }}</div>
    <div class="site-news-comments__feed">
      <article class="site-news-comment" v-for="message in messages.data" :key="message.id" :id="'comment-' + message.id">
        <header class="site-news-comment__header">
          <div class="site-news-comment__avatar">
            <a :href="message.user.profile_url" v-if="message.user.profile_url" :aria-label="message.user.user_name">
              <img :src="message.user.avatar" alt="" width="40" height="40">
            </a>
            <img :src="message.user.avatar" alt="" width="40" height="40" v-else>
            <span class="site-news-comment__online" v-if="message.user.is_online" :title="__('online')"></span>
          </div>
          <div class="site-news-comment__identity">
            <div class="site-news-comment__byline">
              <a :href="message.user.profile_url" class="site-news-comment__name" v-if="message.user.profile_url">{{ message.user.user_name }}</a>
              <span class="site-news-comment__name" v-else>{{ message.user.user_name || __('unknown_user') }}</span>
              <span class="site-news-comment__role" v-if="message.user.rights_name" :title="message.user.rights_name">
                <svg class="site-icon site-icon--lucide" width="12" height="12" aria-hidden="true"><use :href="icon_sprite + '#check'" /></svg>
              </span>
              <span class="site-news-comment__date">{{ message.created_at }}</span>
            </div>
            <div class="site-news-comment__status" v-if="message.user.status">{{ message.user.status }}</div>
          </div>
          <a class="site-news-comment__anchor" :href="'#comment-' + message.id">#{{ message.id }}</a>
        </header>
        <div class="site-news-comment__body ck-content" v-html="message.text"></div>
        <footer class="site-news-comment__footer" v-if="message.ip || message.can_reply || message.can_quote || message.can_delete">
          <div class="site-news-comment__moderation" v-if="message.ip">
            <a :href="message.search_ip_url">{{ message.ip }}</a>
            <span v-if="message.user_agent">{{ message.user_agent }}</span>
          </div>
          <div class="site-news-comment__actions">
            <button type="button" v-if="message.can_reply" @click="reply(message)" :disabled="loading || !editorInstance">{{ __('reply') }}</button>
            <button type="button" v-if="message.can_quote" @click="quote(message)" :disabled="loading || !editorInstance">{{ __('quote') }}</button>
            <button type="button" class="site-news-comment__delete" v-if="message.can_delete" @click="delComment(message.id)" :disabled="loading">{{ __('delete') }}</button>
          </div>
        </footer>
      </article>
    </div>
    <pagination :data="messages" @pagination-change-page="getComments" class="site-news-comments__pagination"></pagination>

    <section class="site-news-compose" v-if="can_write">
      <h2 id="news-compose-title">{{ __('write_comment') }}</h2>
      <div v-if="editor_error" class="alert alert-warning" role="alert">{{ editor_error }}</div>
      <form action="" class="comment-form" @submit.prevent="sendComment" ref="form">
        <label for="comment_text" class="visually-hidden">{{ __('write_comment') }}</label>
        <textarea name="text" id="comment_text" ref="editor" required class="form-control" v-model="comment_text"></textarea>
        <div class="site-news-compose__actions">
          <button type="submit" class="btn btn-primary" :disabled="loading">
            <span class="spinner-border spinner-border-sm" aria-hidden="true" v-if="loading"></span>
            {{ __('send') }}
          </button>
        </div>
      </form>
    </section>
  </section>
</template>

<script>
import { markRaw } from 'vue';
import { csrfHeaders } from '../csrf';

export default {
  name: 'CommentsComponent',
  props: {
    article_id: Number,
    icon_sprite: String,
    can_write: { type: Boolean, default: false },
    i18n: { type: Object, required: true },
    language: { type: String, default: 'en' },
    upload_url: { type: String, default: '' },
  },
  data() {
    return {
      messages: {},
      comment_text: '',
      comment_added_message: '',
      error_message: '',
      editor_error: '',
      loading: false,
      attached_files: [],
      editorInstance: null,
    };
  },
  mounted() {
    this.getComments(1, false);
    if (!this.can_write) return;
    if (!window.ClassicEditor) {
      this.editor_error = this.__('editor_error');
      return;
    }
    window.ClassicEditor.create(this.$refs.editor, {
      simpleUpload: {
        uploadUrl: this.upload_url,
        withCredentials: false,
        headers: csrfHeaders(),
        savedCallback: file => { this.attached_files.push(file.id); },
      },
      language: this.language,
    }).then(editor => {
      this.editorInstance = markRaw(editor);
      editor.model.document.on('change:data', () => {
        this.comment_text = editor.getData();
      });
    }).catch(error => {
      this.editor_error = this.__('editor_error');
      console.error('Could not initialize the news comment editor.', error);
    });
  },
  beforeUnmount() {
    this.editorInstance?.destroy().catch(error => console.error('Could not destroy the news comment editor.', error));
  },
  updated() {
    $(this.$el).find('.image-preview').magnificPopup({
      type: 'image',
      image: {
        verticalFit: true,
        titleSrc: item => {
          const title = document.createElement('span');
          title.textContent = item.el.attr('title') || '';
          const source = item.el.attr('data-source');
          if (source) {
            const url = new URL(source, window.location.href);
            if (['http:', 'https:'].includes(url.protocol)) {
              const link = document.createElement('a');
              link.href = url.href;
              link.target = '_blank';
              link.rel = 'noopener';
              link.className = 'image-source-link';
              link.textContent = this.__('download');
              title.append(' · ', link);
            }
          }
          return title.innerHTML;
        },
      },
      zoom: {
        enabled: true,
        duration: 300,
        opener: element => element.find('img'),
      },
    });
  },
  methods: {
    getComments(page = 1, scrollToComments = true) {
      this.loading = true;
      return axios.get('/news/comments/' + this.article_id + '/?page=' + page)
        .then(response => {
          this.messages = response.data;
          this.error_message = '';
          if (scrollToComments) this.scrollTo(this.$refs.list);
        })
        .catch(error => {
          this.error_message = error.response?.data?.message || this.__('load_error');
        })
        .finally(() => { this.loading = false; });
    },
    scrollTo(element) {
      element.scrollIntoView({behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'instant' : 'smooth', block: 'start'});
    },
    reply(message) {
      const editor = this.editorInstance;
      if (!editor) return;
      this.scrollTo(this.$refs.form);
      editor.editing.view.focus();
      editor.model.change(writer => {
        writer.insertText(message.user.user_name + ', ', {}, editor.model.document.selection.getFirstPosition());
        writer.setSelection(writer.createPositionAt(editor.model.document.getRoot(), 'end'));
      });
    },
    quote(message) {
      const editor = this.editorInstance;
      if (!editor) return;
      // Build quote markup through text nodes, including the author's name.
      const text = new DOMParser().parseFromString(message.text, 'text/html').body.textContent;
      const quote = document.createElement('blockquote');
      const paragraph = document.createElement('p');
      paragraph.textContent = message.user.user_name + ', ' + message.created_at + ': ' + text;
      quote.append(paragraph);
      const viewFragment = editor.data.processor.toView(quote.outerHTML + '<p></p>');
      editor.model.insertContent(editor.data.toModel(viewFragment));
      this.scrollTo(this.$refs.form);
      editor.editing.view.focus();
    },
    sendComment() {
      if (this.loading) return;
      this.loading = true;
      this.error_message = '';
      this.comment_added_message = '';
      axios.post('/news/comments/add/' + this.article_id + '/', {
        comment: this.comment_text,
        attached_files: this.attached_files,
      }).then(response => {
        this.comment_added_message = response.data.message;
        this.comment_text = '';
        this.attached_files = [];
        this.editorInstance?.setData('');
        return this.getComments(response.data.last_page);
      }).catch(error => {
        this.error_message = error.response?.data?.message || this.__('send_error');
        this.scrollTo(this.$refs.list);
      }).finally(() => { this.loading = false; });
    },
    delComment(commentId) {
      if (this.loading || !window.confirm(this.__('confirm_delete'))) return;
      this.loading = true;
      this.error_message = '';
      axios.post('/news/comments/del/', {comment_id: commentId})
        .then(() => this.getComments(this.messages.data.length === 1 ? Math.max(1, this.messages.current_page - 1) : this.messages.current_page, false))
        .catch(error => {
          this.error_message = error.response?.data?.message || this.__('delete_error');
        })
        .finally(() => { this.loading = false; });
    },
    __(message) {
      return this.i18n[message] || '';
    },
  },
};
</script>
