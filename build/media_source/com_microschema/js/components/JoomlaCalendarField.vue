<script setup>
import {
  computed,
  nextTick,
  onMounted,
  ref,
  watch,
} from 'vue';
import {
  formatCalendarValue,
  isDataSourcePlaceholder,
  parseCalendarValue,
} from '../schema-state.js';

const props = defineProps({
  calendar: { type: Object, required: true },
  clearLabel: { type: String, default: '' },
  id: { type: String, required: true },
  modelValue: { default: '' },
  required: { type: Boolean, default: false },
  typeDefinition: { type: Object, required: true },
});

const emit = defineEmits(['update:modelValue']);
const root = ref(null);
const showTime = computed(() => props.typeDefinition.input === 'calendar-datetime');
const dynamicValue = computed(() => isDataSourcePlaceholder(props.modelValue));
const displayValue = computed(() => formatCalendarValue(props.modelValue, props.typeDefinition));
const dateFormat = computed(() => (
  showTime.value ? props.calendar.dateTimeFormat : props.calendar.dateFormat
));

const updateValue = (event) => {
  const value = event.target.dataset.altValue || event.target.value;
  emit('update:modelValue', parseCalendarValue(value, props.typeDefinition));
};

const initializeCalendar = () => {
  nextTick(() => {
    if (root.value && window.JoomlaCalendar) {
      window.JoomlaCalendar.init(root.value);
    }
  });
};

onMounted(initializeCalendar);
watch(dynamicValue, (dynamic) => {
  if (!dynamic) {
    initializeCalendar();
  }
});
</script>

<template>
  <div
    v-if="dynamicValue"
    class="input-group"
  >
    <input
      :id="id"
      type="text"
      class="form-control"
      :value="modelValue"
      :required="required"
      readonly
    >
    <button
      type="button"
      class="btn btn-outline-secondary"
      :aria-label="clearLabel"
      :title="clearLabel"
      @click="$emit('update:modelValue', '')"
    >
      <span class="icon-times" aria-hidden="true" />
    </button>
  </div>
  <div
    v-else
    ref="root"
    class="field-calendar"
  >
    <div class="input-group">
      <input
        :id="id"
        type="text"
        class="form-control"
        :value="displayValue"
        :data-alt-value="displayValue"
        :required="required"
        autocomplete="off"
        @blur="updateValue"
        @change="updateValue"
      >
      <button
        :id="`${id}_btn`"
        type="button"
        class="btn btn-primary"
        :title="calendar.openLabel"
        :data-inputfield="id"
        :data-button="`${id}_btn`"
        :data-date-format="dateFormat"
        :data-firstday="calendar.firstDay"
        :data-weekend="calendar.weekend.join(',')"
        :data-today-btn="calendar.todayButton"
        :data-week-numbers="calendar.weekNumbers"
        :data-show-time="showTime ? 1 : 0"
        :data-show-others="calendar.fillTable"
        :data-time24="calendar.timeFormat"
        :data-only-months-nav="calendar.singleHeader"
        :data-date-type="calendar.calendarType"
      >
        <span class="icon-calendar" aria-hidden="true" />
        <span class="visually-hidden">{{ calendar.openLabel }}</span>
      </button>
    </div>
  </div>
</template>
