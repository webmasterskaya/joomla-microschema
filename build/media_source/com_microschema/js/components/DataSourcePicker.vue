<script setup>
import { computed, ref } from 'vue';
import { filterDataSourceFields } from '../schema-state.js';

const props = defineProps({
  catalog: { type: Object, required: true },
  labels: { type: Object, required: true },
  terminalTypes: { type: Array, default: () => [] },
});

const emit = defineEmits(['insert']);
const expanded = ref(false);
const frames = ref([]);
const sources = computed(() => (props.catalog.sources || []).reduce((filtered, source) => {
  const fields = filterDataSourceFields(source.fields, props.terminalTypes);

  if (fields.length) {
    filtered.push({ ...source, fields });
  }

  return filtered;
}, []));
const currentFrame = computed(() => frames.value[frames.value.length - 1] || null);
const currentLabel = computed(() => currentFrame.value?.label || props.labels.dynamicValues);
const currentItems = computed(() => {
  if (!currentFrame.value) {
    return sources.value.map((source) => ({
      ...source,
      object: true,
      path: [],
      source: source.name,
    }));
  }

  return (currentFrame.value.fields || []).map((field) => ({
    ...field,
    path: [...currentFrame.value.path, field.name],
    source: currentFrame.value.source,
  }));
});

const toggle = () => {
  expanded.value = !expanded.value;

  if (!expanded.value) {
    frames.value = [];
  }
};

const choose = (item) => {
  if (item.object) {
    frames.value.push({
      label: item.label,
      path: item.path,
      source: item.source,
      fields: item.fields || [],
    });
    return;
  }

  emit('insert', `{${item.source}.${item.path.join('.')}}`);
  expanded.value = false;
  frames.value = [];
};

const goBack = () => {
  frames.value.pop();
};

const goToRoot = () => {
  frames.value = [];
};

const goToFrame = (index) => {
  frames.value = frames.value.slice(0, index + 1);
};
</script>

<template>
  <div
    v-if="sources.length"
    class="microschema-data-source-picker"
  >
    <button
      type="button"
      class="btn btn-sm btn-outline-secondary"
      :aria-expanded="expanded"
      @click="toggle"
    >
      <span class="icon-code" aria-hidden="true" />
      {{ labels.insertDataSource }}
    </button>

    <div
      v-if="expanded"
      class="microschema-data-source-panel mt-2"
    >
      <div class="d-flex align-items-center gap-2 mb-2">
        <button
          v-if="frames.length"
          type="button"
          class="btn btn-sm btn-outline-secondary"
          :aria-label="labels.back"
          @click="goBack"
        >
          <span class="icon-arrow-left" aria-hidden="true" />
        </button>
        <strong class="flex-grow-1">{{ currentLabel }}</strong>
        <button
          type="button"
          class="btn-close"
          :aria-label="labels.close"
          @click="toggle"
        />
      </div>

      <nav
        v-if="frames.length"
        class="microschema-data-source-breadcrumb mb-2"
        :aria-label="labels.dynamicValues"
      >
        <button
          type="button"
          class="btn btn-link btn-sm p-0"
          @click="goToRoot"
        >{{ labels.dynamicValues }}</button>
        <button
          v-for="(frame, index) in frames"
          :key="`${frame.source}-${frame.path.join('.')}`"
          type="button"
          class="btn btn-link btn-sm p-0"
          :disabled="index === frames.length - 1"
          @click="goToFrame(index)"
        >{{ frame.label }}</button>
      </nav>

      <div class="list-group list-group-flush">
        <button
          v-for="item in currentItems"
          :key="`${item.source}-${item.path.join('.')}`"
          type="button"
          class="list-group-item list-group-item-action d-flex align-items-center gap-2"
          @click="choose(item)"
        >
          <span class="flex-grow-1">{{ item.label }}</span>
          <small
            v-if="!item.object && item.type"
            class="text-muted"
          >{{ item.type }}</small>
          <span
            v-if="item.object"
            class="icon-chevron-right"
            aria-hidden="true"
          />
        </button>
      </div>
    </div>
  </div>
</template>
