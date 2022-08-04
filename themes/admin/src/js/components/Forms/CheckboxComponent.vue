<template>
  <div class="form-check">
    <input
      :id="elementId"
      :name="name"
      :disabled="disabled"
      :required="required"
      :readonly="readonly"
      @change="updateModelValue"
      v-model="value"
      :class="[hasError ? 'is-invalid' : '']"
      class="form-check-input cursor-pointer"
      type="checkbox"
    >
    <label class="form-check-label cursor-pointer" :for="elementId" v-text="label"></label>
    <span class="invalid-feedback mt-0" v-if="hasError">{{ error }}</span>
    <div class="small text-secondary opacity-75" v-if="help">{{ help }}</div>
  </div>
</template>

<script lang="ts">
export default {
  name: "CheckboxComponent",
  props: {
    modelValue: [String, Boolean],
    label: {
      type: String,
      default: '',
    },
    name: {
      type: String,
      default: '',
    },
    id: {
      type: String,
      default: '',
    },
    error: {
      type: [String, Number],
      default: null,
    },
    help: {
      type: [String, Number],
      default: null,
    },
    disabled: {
      type: Boolean,
      default: false,
    },
    readonly: {
      type: Boolean,
      default: false,
    },
    required: {
      type: Boolean,
      default: false,
    },
  },
  emits: ['update:modelValue'],
  data() {
    return {
      value: this.modelValue,
      elementId: this.id ? this.id : this.name
    }
  },
  computed: {
    hasError() {
      return !!this.error;
    },
  },
  methods: {
    updateModelValue() {
      this.$emit('update:modelValue', this.value);
    }
  }
}
</script>
