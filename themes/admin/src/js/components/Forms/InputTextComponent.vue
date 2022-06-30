<!--suppress CssUnusedSymbol -->
<template>
  <div class="form-input" :class="[disabled ? 'disabled' : '']">
    <label v-if="label" :for="elementId">{{ label }}</label>
    <input
      :class="[hasError ? 'is-invalid' : '']"
      :type="type"
      :name="name"
      :id="elementId"
      :placeholder="placeholder"
      :disabled="disabled"
      v-model="value"
      :required="required"
      :readonly="readonly"
      :enterkeyhint="enterKeyHint"
      @input="updateModelValue"
      :pattern="type === 'number' ? '[0-9]*' : null"
      :autocomplete="autocomplete !== '' ? autocomplete : null"
      class="form-control">
    <div class="invalid-feedback" v-if="hasError">{{ error }}</div>
    <div class="fz-x-small text-secondary opacity-75" v-if="help">{{ help }}</div>
  </div>
</template>

<script lang="ts">
import _ from "lodash";

export default {
  name: "InputTextComponent",
  props: {
    modelValue: String,
    label: {
      type: String,
      default: '',
    },
    placeholder: {
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
    type: {
      type: String,
      default: 'text',
    },
    enterKeyHint: {
      type: String,
      default: '',
    },
    autocomplete: {
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
    updateModelValue: _.debounce(function () {
      this.$emit('update:modelValue', this.value)
    }, 500)
  }
}
</script>
