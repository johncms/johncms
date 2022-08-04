<template>
  <div class="modal fade" tabindex="-1" aria-labelledby="exampleModalLabel" ref="modalBase">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="exampleModalLabel">{{ title }}</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <slot></slot>
        </div>
        <div class="modal-footer">
          <button
            type="button"
            class="btn"
            :class="['btn-' + cancelButtonColor]"
            data-bs-dismiss="modal"
          >{{ cancelButtonText }}
          </button>
          <button
            type="button"
            class="btn"
            @click="confirm"
            :class="['btn-' + confirmButtonColor]"
            :disabled="loading"
          >{{ confirmButtonText }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import {defineComponent} from 'vue';
import {Modal} from 'bootstrap'

export default defineComponent({
  name: "ConfirmModal",
  props: {
    title: {
      type: String,
      default: 'Confirmation',
    },
    cancelButtonText: {
      type: String,
      default: 'Cancel',
    },
    confirmButtonText: {
      type: String,
      default: 'Confirm',
    },
    cancelButtonColor: {
      type: String,
      default: 'secondary',
    },
    confirmButtonColor: {
      type: String,
      default: 'primary',
    },
    large: {
      type: Boolean,
      default: false,
    }
  },
  emits: ["onModalClose", "onConfirm"],
  data() {
    return {
      modal: null,
      loading: false,
    };
  },
  methods: {
    openModal() {
      this.modal = new Modal(this.$refs.modalBase, {
        keyboard: false
      });
      this.modal.show();

      this.$refs.modalBase.addEventListener('hidden.bs.modal', () => {
        this.$emit('onModalClose', true);
      });
    },
    closeModal() {
      this.modal.hide();
    },
    confirm(element: PointerEvent) {
      this.$emit('onConfirm', element);
    },
    setLoading(loading: boolean) {
      this.loading = loading;
    }
  }
});
</script>
