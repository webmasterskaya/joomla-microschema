<script setup>
import {
  computed,
  onBeforeUnmount,
  onMounted,
  reactive,
  ref,
  toRaw,
  watchEffect,
} from 'vue';
import SchemaField from './SchemaField.vue';
import SchemaHiddenInputs from './SchemaHiddenInputs.vue';
import {
  createPropertyValue,
  ensureRequiredProperties,
  hasProperty,
  normalizeObjectValue,
  orderActiveProperties,
} from '../schema-state.js';

const props = defineProps({
  config: { type: Object, required: true },
});

const schemaType = ref(props.config.schemaType || '');
const values = reactive({});
const frames = ref([]);
const propertyToAdd = ref('');
const schemaTypeDescription = ref('');
const schemaTypeLabel = ref('');
const schemaTypeOptions = ref([]);
const transitionDirection = ref('forward');
const addedProperties = new WeakMap();
let frameScrollPositions = new WeakMap();
let nativeSchemaTypeElements = [];
let pendingScrollPosition = null;
let schemaTypeField = null;

if (schemaType.value) {
  values[schemaType.value] = normalizeObjectValue(props.config.value);
}

const currentRoot = computed(() => {
  if (!schemaType.value) {
    return {};
  }

  if (!values[schemaType.value]) {
    values[schemaType.value] = {};
  }

  return values[schemaType.value];
});

const rootFrame = computed(() => ({
  data: currentRoot.value,
  label: schemaType.value,
  type: schemaType.value,
}));

const currentFrame = computed(() => frames.value[frames.value.length - 1] || rootFrame.value);
const currentDataSourceCatalog = computed(() => (
  currentFrame.value.dataSourceCatalog || props.config.dataSourceCatalog
));
const currentSchema = computed(() => props.config.schemas[currentFrame.value.type] || null);
const breadcrumbs = computed(() => [rootFrame.value, ...frames.value]);
const frameKey = computed(() => [
  schemaType.value,
  ...frames.value.map((frame) => `${frame.type}:${frame.label}`),
].join('/'));
const transitionName = computed(() => `microschema-frame-${transitionDirection.value}`);

const getAddedProperties = (value) => {
  const target = toRaw(value);

  if (!addedProperties.has(target)) {
    addedProperties.set(target, reactive([]));
  }

  return addedProperties.get(target);
};

const activeProperties = computed(() => {
  if (!currentSchema.value) {
    return [];
  }

  return orderActiveProperties(
    currentSchema.value.properties,
    currentFrame.value.data,
    getAddedProperties(currentFrame.value.data),
  );
});

const availableProperties = computed(() => {
  if (!currentSchema.value) {
    return [];
  }

  return currentSchema.value.properties.filter((property) => (
    !hasProperty(currentFrame.value.data, property.name)
  ));
});

watchEffect(() => {
  ensureRequiredProperties(currentFrame.value.data, currentSchema.value);
});

const setProperty = (name, value) => {
  currentFrame.value.data[name] = value;
};

const addProperty = (propertyName) => {
  const property = currentSchema.value?.properties.find((item) => item.name === propertyName);

  if (!property) {
    propertyToAdd.value = '';
    return;
  }

  currentFrame.value.data[property.name] = createPropertyValue(property);
  getAddedProperties(currentFrame.value.data).push(property.name);
  propertyToAdd.value = '';
};

const removeProperty = (property) => {
  if (!property.required) {
    delete currentFrame.value.data[property.name];

    const added = getAddedProperties(currentFrame.value.data);
    const index = added.indexOf(property.name);

    if (index !== -1) {
      added.splice(index, 1);
    }
  }
};

const currentScrollPosition = () => ({
  left: window.scrollX,
  top: window.scrollY,
});

const rememberCurrentScrollPosition = () => {
  frameScrollPositions.set(toRaw(currentFrame.value.data), currentScrollPosition());
};

const prepareFrameTransition = (targetFrame, fallbackPosition = null) => {
  const savedPosition = frameScrollPositions.get(toRaw(targetFrame.data));
  pendingScrollPosition = savedPosition || fallbackPosition;
};

const editObject = (frame) => {
  const scrollPosition = currentScrollPosition();

  rememberCurrentScrollPosition();
  prepareFrameTransition(frame, scrollPosition);
  transitionDirection.value = 'forward';
  frames.value.push(frame);
  propertyToAdd.value = '';
};

const goToFrame = (index) => {
  const targetFrame = breadcrumbs.value[index];

  rememberCurrentScrollPosition();
  prepareFrameTransition(targetFrame);
  transitionDirection.value = 'back';
  frames.value = index === 0 ? [] : frames.value.slice(0, index);
  propertyToAdd.value = '';
};

const goBack = () => {
  const targetFrame = frames.value.length > 1
    ? frames.value[frames.value.length - 2]
    : rootFrame.value;

  rememberCurrentScrollPosition();
  prepareFrameTransition(targetFrame);
  transitionDirection.value = 'back';
  frames.value.pop();
  propertyToAdd.value = '';
};

const onSchemaTypeChange = (event) => {
  frameScrollPositions = new WeakMap();
  pendingScrollPosition = null;
  transitionDirection.value = 'forward';
  schemaType.value = event.target.value;
  frames.value = [];
  propertyToAdd.value = '';
};

const changeSchemaType = (event) => {
  if (!schemaTypeField) {
    return;
  }

  schemaTypeField.value = event.target.value;
  schemaTypeField.dispatchEvent(new Event('change', { bubbles: true }));
};

const configureSchemaTypeSelector = () => {
  if (!schemaTypeField) {
    return;
  }

  const nativeControl = schemaTypeField.closest('.control-group');
  const nativeLabel = document.querySelector(`label[for="${CSS.escape(schemaTypeField.id)}"]`);
  const describedElements = (schemaTypeField.getAttribute('aria-describedby') || '')
    .split(/\s+/)
    .filter(Boolean)
    .map((id) => document.getElementById(id))
    .filter(Boolean);

  schemaTypeOptions.value = Array.from(schemaTypeField.options).map((option) => ({
    disabled: option.disabled,
    hidden: option.hidden,
    label: option.textContent.trim(),
    value: option.value,
  }));
  schemaTypeLabel.value = nativeLabel?.textContent.trim() || props.config.labels.type;
  schemaTypeDescription.value = describedElements
    .map((element) => element.textContent.trim())
    .filter(Boolean)
    .join(' ');

  const elementsToHide = nativeControl
    ? [nativeControl]
    : [nativeLabel, schemaTypeField, ...describedElements].filter(Boolean);

  nativeSchemaTypeElements = elementsToHide.map((element) => ({
    element,
    hidden: element.hidden,
  }));
  nativeSchemaTypeElements.forEach(({ element }) => {
    element.hidden = true;
  });
};

const restoreNativeSchemaTypeSelector = () => {
  nativeSchemaTypeElements.forEach(({ element, hidden }) => {
    element.hidden = hidden;
  });
  nativeSchemaTypeElements = [];
};

const focusCurrentHeading = () => {
  document.querySelector(`#${CSS.escape(props.config.instanceId)} h3`)?.focus({ preventScroll: true });

  if (!pendingScrollPosition) {
    return;
  }

  const scrollPosition = pendingScrollPosition;
  pendingScrollPosition = null;

  requestAnimationFrame(() => {
    window.scrollTo({
      behavior: 'auto',
      left: scrollPosition.left,
      top: scrollPosition.top,
    });
  });
};

onMounted(() => {
  schemaTypeField = document.getElementById(props.config.schemaTypeField);
  schemaTypeField?.addEventListener('change', onSchemaTypeChange);
  configureSchemaTypeSelector();
});

onBeforeUnmount(() => {
  schemaTypeField?.removeEventListener('change', onSchemaTypeChange);
  restoreNativeSchemaTypeSelector();
});
</script>

<template>
  <div class="microschema-editor-shell">
    <div
      v-if="schemaTypeOptions.length && !frames.length"
      class="microschema-schema-type mb-4"
    >
      <label
        class="form-label fw-semibold"
        :for="`${config.instanceId}-schema-type`"
      >{{ schemaTypeLabel }}</label>
      <select
        :id="`${config.instanceId}-schema-type`"
        class="form-select"
        :value="schemaType"
        @change="changeSchemaType"
      >
        <option
          v-for="option in schemaTypeOptions"
          :key="option.value"
          :disabled="option.disabled"
          :hidden="option.hidden"
          :value="option.value"
        >{{ option.label }}</option>
      </select>
      <div
        v-if="schemaTypeDescription"
        class="form-text"
      >{{ schemaTypeDescription }}</div>
    </div>

    <SchemaHiddenInputs
      v-if="schemaType && config.schemas[schemaType]"
      :data="currentRoot"
      :prefix="config.fieldName"
      :schema-type="schemaType"
      :schemas="config.schemas"
    />

    <div
      v-if="!schemaType || !currentSchema"
      class="alert alert-info mb-0"
    >
      {{ config.labels.empty }}
    </div>

    <template v-else>
      <nav
        v-if="breadcrumbs.length > 1"
        class="microschema-breadcrumb"
        aria-label="Breadcrumb"
      >
        <button
          v-for="(frame, index) in breadcrumbs"
          :key="`${frame.type}-${index}`"
          type="button"
          class="btn btn-link btn-sm p-0"
          :disabled="index === breadcrumbs.length - 1"
          @click="goToFrame(index)"
        >{{ frame.label }}</button>
      </nav>

      <Transition
        :name="transitionName"
        mode="out-in"
        @after-enter="focusCurrentHeading"
      >
        <div
          :key="frameKey"
          class="microschema-frame"
        >
          <div class="d-flex align-items-center gap-3 mb-4">
            <button
              v-if="frames.length"
              type="button"
              class="btn btn-sm btn-outline-secondary"
              @click="goBack"
            >
              <span class="icon-arrow-left" aria-hidden="true" />
              {{ config.labels.back }}
            </button>
            <h3
              class="h5 mb-0"
              tabindex="-1"
            >{{ currentFrame.label }} <span class="text-muted fw-normal">{{ currentFrame.type }}</span></h3>
          </div>

          <div class="microschema-fields">
            <div
              v-for="property in activeProperties"
              :key="property.name"
              class="microschema-property-row"
            >
              <SchemaField
                :property="property"
                :model-value="currentFrame.data[property.name]"
                :labels="config.labels"
                :calendar="config.calendar"
                :data-source-catalog="currentDataSourceCatalog"
                :removable="!property.required"
                @update:model-value="setProperty(property.name, $event)"
                @edit-object="editObject"
                @remove-property="removeProperty(property)"
              />
            </div>
          </div>

          <div
            v-if="availableProperties.length"
            class="microschema-add-property mt-4"
          >
            <label
              class="form-label fw-semibold"
              :for="`${config.instanceId}-add-property`"
            >{{ config.labels.addProperty }}</label>
            <select
              :id="`${config.instanceId}-add-property`"
              class="form-select"
              :value="propertyToAdd"
              @change="addProperty($event.target.value)"
            >
              <option value="">{{ config.labels.selectProperty }}</option>
              <option
                v-for="property in availableProperties"
                :key="property.name"
                :value="property.name"
              >{{ property.label }}</option>
            </select>
          </div>
        </div>
      </Transition>
    </template>
  </div>
</template>
