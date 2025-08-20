<template>
  <div>
    <div v-for="item in items" :key="item.value" class="form-check">
      <input
        class="form-check-input"
        type="radio"
        :id="createRadioId(item.value)"
        :value="item.value"
        v-model="selectedValue"
        @change="onValueChange"
      />
      <label class="form-check-label" :for="createRadioId(item.value)">
        {{ item.label }}
      </label>
    </div>
  </div>
</template>

<script lang="ts">
export type RadioItem = {
    label: string;
    value: string | number;
}
</script>

<script setup lang="ts">
import { ref, watch } from 'vue';

const props = defineProps<{
  items: RadioItem[];
  modelValue: string | number;
}>();

const idPrefix = 'radio-';

const createRadioId = (id: string | number): string => {
  return idPrefix + id;
}

const emit = defineEmits<{
  (e: 'update:modelValue', value: string | number): void;
}>();

const selectedValue = ref(props.modelValue);

watch(() => props.modelValue, (newValue) => {
  selectedValue.value = newValue;
});

const onValueChange = () => {
  emit('update:modelValue', selectedValue.value);
};
</script>