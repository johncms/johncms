<template>
  <div>
    <div
      class="form-group"
      :class="[hasError ? 'is-invalid' : '']"
    >
      <label v-if="label" :for="id" v-text="label"></label>
      <select
        class="form-control"
        @change="change"
        :multiple="multiple"
        :name="name"
        :id="id"
        v-model="value"
        :disabled="disabledInput">
        <option v-if="defaultNothing" value="">{{ defaultNothingText ?? $t('form.select.nothingText') }}</option>
        <option v-for="(option, index) in options" :value="option.id" v-text="getName(option)" :key="index"></option>
      </select>
    </div>
    <span class="invalid-feedback mt-n3" v-if="hasError" v-text="error"></span>
  </div>
</template>

<script lang="ts">
export default {
  name: "SelectComponent",
  props: {
    modelValue: String,
    options: {
      type: [Array, Object]
    },
    name: String,
    label: {
      type: [String, Number],
      default: null,
    },
    id: String,
    error: {
      type: [String, Number],
      default: null,
    },
    defaultNothing: {
      type: Boolean,
      default: true,
    },
    defaultNothingText: {
      type: String,
      default: null,
    },
    disabledInput: {
      type: Boolean,
      default: false,
    },
    multiple: {
      type: Boolean,
      default: false,
    },
    displayName: {
      type: String,
      default: 'name',
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
    getName(option: string) {
      return option[this.displayName];
    },
    change() {
      this.$emit('update:modelValue', this.value);
    }
  }
}
</script>
