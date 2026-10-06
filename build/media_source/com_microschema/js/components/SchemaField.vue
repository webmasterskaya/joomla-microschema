<script setup>
import { computed, toRaw } from 'vue';
import DataSourcePicker from './DataSourcePicker.vue';
import JoomlaCalendarField from './JoomlaCalendarField.vue';
import {
  collectionDefinition,
  createCollectionValue,
  createTypedValue,
  insertPlaceholder,
  isPlainProperty,
  isRecord,
  objectSummary,
  supportsDataSourceTemplate,
  typeDefinition,
  unknownPlaceholders,
} from '../schema-state.js';

const props = defineProps({
  property: { type: Object, required: true },
  modelValue: { default: undefined },
  labels: { type: Object, required: true },
  calendar: { type: Object, required: true },
  dataSourceCatalog: { type: Object, required: true },
  removable: { type: Boolean, default: false },
});

const emit = defineEmits(['edit-object', 'remove-property', 'update:modelValue']);
const plain = computed(() => isPlainProperty(props.property));
const inputId = `microschema-${props.property.name}-${Math.random().toString(36).slice(2, 9)}`;
const itemKeys = new WeakMap();
const selections = new Map();
let itemKeySequence = 0;

const scalarInputType = (type, value = '') => {
  if (type?.input === 'number' && String(value ?? '').includes('{')) {
    return 'text';
  }

  return type?.input === 'boolean' ? 'boolean' : (type?.input || 'text');
};
const isCalendarType = (type) => type?.input?.startsWith('calendar-');
const dataSourceTerminalTypes = (type) => (
  isCalendarType(type)
    ? props.property.types.filter(isCalendarType).map((item) => item.name)
    : (type?.input === 'number'
      ? props.property.types.filter((item) => item.input === 'number').map((item) => item.name)
      : [])
);
const itemKey = (item) => {
  const target = toRaw(item);

  if (!itemKeys.has(target)) {
    itemKeySequence += 1;
    itemKeys.set(target, itemKeySequence);
  }

  return itemKeys.get(target);
};

const updatePlain = (event) => {
  emit('update:modelValue', event.target.value);
};

const rememberSelection = (key, event) => {
  selections.set(key, {
    start: event.target.selectionStart,
    end: event.target.selectionEnd,
  });
};

const insertPlainPlaceholder = (token) => {
  const selection = selections.get('plain') || {};
  const current = props.modelValue === null || props.modelValue === undefined
    ? ''
    : String(props.modelValue);
  const start = Number.isInteger(selection.start) ? selection.start : current.length;

  emit('update:modelValue', insertPlaceholder(
    props.modelValue,
    token,
    selection.start,
    selection.end,
  ));
  selections.set('plain', { start: start + token.length, end: start + token.length });
};

const insertPlainDataSource = (token) => {
  if (isCalendarType(props.property.types[0])) {
    emit('update:modelValue', token);
    return;
  }

  insertPlainPlaceholder(token);
};

const singleItem = () => {
  if (isRecord(props.modelValue) && Object.prototype.hasOwnProperty.call(props.modelValue, 'type')) {
    return props.modelValue;
  }

  return createTypedValue(props.property);
};

const updateItemType = (item, typeName, index = null) => {
  const type = typeDefinition(props.property, typeName);
  const next = {
    type: type.name,
    data: type.kind === 'object' ? {} : '',
  };

  if (item.collection) {
    next.collection = item.collection;
  }

  if (isRecord(item)) {
    itemKeys.set(next, itemKey(item));
  }

  if (index === null) {
    emit('update:modelValue', next);
    return;
  }

  const items = Array.isArray(props.modelValue) ? [...props.modelValue] : [];
  items[index] = next;
  emit('update:modelValue', items);
};

const updateItemData = (item, value, index = null) => {
  const next = { ...item, data: value };
  itemKeys.set(next, itemKey(item));

  if (index === null) {
    emit('update:modelValue', next);
    return;
  }

  const items = Array.isArray(props.modelValue) ? [...props.modelValue] : [];
  items[index] = next;
  emit('update:modelValue', items);
};

const insertItemPlaceholder = (item, token, index = null) => {
  const key = index === null ? 'single' : itemKey(item);
  const selection = selections.get(key) || {};
  const current = item.data === null || item.data === undefined ? '' : String(item.data);
  const start = Number.isInteger(selection.start) ? selection.start : current.length;

  updateItemData(
    item,
    insertPlaceholder(item.data, token, selection.start, selection.end),
    index,
  );
  selections.set(key, { start: start + token.length, end: start + token.length });
};

const insertItemDataSource = (item, token, index = null) => {
  if (isCalendarType(typeDefinition(props.property, item.type))) {
    updateItemData(item, token, index);
    return;
  }

  insertItemPlaceholder(item, token, index);
};

const unresolved = (value) => unknownPlaceholders(value, props.dataSourceCatalog);

const addItem = () => {
  const items = Array.isArray(props.modelValue) ? [...props.modelValue] : [];
  items.push(createTypedValue(props.property));
  emit('update:modelValue', items);
};

const removeItem = (index) => {
  const items = Array.isArray(props.modelValue) ? [...props.modelValue] : [];
  items.splice(index, 1);
  emit('update:modelValue', items);
};

const openObject = (item, index = null) => {
  let current = item;

  if (!isRecord(current.data)) {
    current = { ...current, data: {} };
    updateItemData(item, current.data, index);
  }

  emit('edit-object', {
    data: current.data,
    dataSourceCatalog: catalogForItem(item),
    label: props.property.label,
    type: current.type,
  });
};

const items = computed(() => (Array.isArray(props.modelValue) ? props.modelValue : []));
const collections = computed(() => props.dataSourceCatalog?.collections || []);

const catalogForItem = (item) => (
  collectionDefinition(props.dataSourceCatalog, item.collection)?.dataSourceCatalog
  || props.dataSourceCatalog
);

const addCollection = () => {
  if (!collections.value.length) {
    return;
  }

  const next = Array.isArray(props.modelValue) ? [...props.modelValue] : [];
  next.push(createCollectionValue(props.property, collections.value[0].name));
  emit('update:modelValue', next);
};

const updateItemCollection = (item, collectionName, index) => {
  const next = Array.isArray(props.modelValue) ? [...props.modelValue] : [];
  const updated = { ...item, collection: collectionName };
  itemKeys.set(updated, itemKey(item));
  next[index] = updated;
  emit('update:modelValue', next);
};
</script>

<template>
  <div class="microschema-field">
    <div class="microschema-field-header mb-2">
      <div class="microschema-field-heading">
        <label
          class="form-label fw-semibold mb-0"
          :for="inputId"
        >
          {{ property.label }}
          <span
            v-if="property.required"
            class="text-danger"
            aria-hidden="true"
          >*</span>
        </label>
        <span
          v-if="property.description"
          class="microschema-field-type text-muted"
        >{{ property.description }}</span>
      </div>

      <div
        v-if="removable || (plain && !property.options?.length && supportsDataSourceTemplate(property.types[0])) || (!plain && !property.multiple && supportsDataSourceTemplate(typeDefinition(property, singleItem().type)))"
        class="microschema-field-actions"
      >
        <DataSourcePicker
          v-if="plain && !property.options?.length && supportsDataSourceTemplate(property.types[0])"
          :catalog="dataSourceCatalog"
          :labels="labels"
          :terminal-types="dataSourceTerminalTypes(property.types[0])"
          @insert="insertPlainDataSource"
        />
        <DataSourcePicker
          v-else-if="!property.multiple && supportsDataSourceTemplate(typeDefinition(property, singleItem().type))"
          :catalog="dataSourceCatalog"
          :labels="labels"
          :terminal-types="dataSourceTerminalTypes(typeDefinition(property, singleItem().type))"
          @insert="insertItemDataSource(singleItem(), $event)"
        />
        <button
          v-if="removable"
          type="button"
          class="btn btn-sm btn-outline-danger microschema-remove-property"
          @click="$emit('remove-property')"
        >
          <span class="icon-trash" aria-hidden="true" />
          {{ labels.removeProperty }}
        </button>
      </div>
    </div>

    <template v-if="plain">
      <select
        v-if="property.options?.length"
        :id="inputId"
        class="form-select"
        :value="modelValue ?? ''"
        :required="property.required"
        @change="updatePlain"
      >
        <option value="" />
        <option
          v-for="option in property.options"
          :key="option.value"
          :value="option.value"
        >{{ option.label }}</option>
      </select>
      <JoomlaCalendarField
        v-else-if="isCalendarType(property.types[0])"
        :id="inputId"
        :key="property.types[0].input"
        :calendar="calendar"
        :clear-label="labels.removeItem"
        :model-value="modelValue"
        :required="property.required"
        :type-definition="property.types[0]"
        @update:model-value="$emit('update:modelValue', $event)"
      />
      <input
        v-else-if="property.types[0].input !== 'boolean'"
        :id="inputId"
        class="form-control"
        :type="scalarInputType(property.types[0], modelValue)"
        :step="property.types[0].name === 'Number' ? 'any' : undefined"
        :value="modelValue ?? ''"
        :required="property.required"
        @input="updatePlain"
        @click="rememberSelection('plain', $event)"
        @keyup="rememberSelection('plain', $event)"
        @select="rememberSelection('plain', $event)"
      >
      <select
        v-else
        :id="inputId"
        class="form-select"
        :value="modelValue ?? ''"
        :required="property.required"
        @change="updatePlain"
      >
        <option value="" />
        <option value="1">{{ labels.yes }}</option>
        <option value="0">{{ labels.no }}</option>
      </select>
      <div
        v-if="unresolved(modelValue).length"
        class="form-text text-warning"
      >{{ labels.unknownDataSource }}: {{ unresolved(modelValue).join(', ') }}</div>
    </template>

    <template v-else-if="!property.multiple">
      <div class="microschema-value-card">
        <div class="input-group microschema-typed-value">
          <select
            v-if="property.types.length > 1"
            :id="`${inputId}-type`"
            class="form-select microschema-type-select"
            :aria-label="labels.type"
            :value="singleItem().type"
            @change="updateItemType(singleItem(), $event.target.value)"
          >
            <option
              v-for="type in property.types"
              :key="type.name"
              :value="type.name"
            >{{ type.name }}</option>
          </select>

          <button
            v-if="typeDefinition(property, singleItem().type)?.kind === 'object'"
            type="button"
          class="btn btn-outline-primary microschema-value-control d-flex justify-content-between align-items-center"
          @click="openObject(singleItem())"
        >
            <span class="d-flex align-items-center gap-2">
              <span class="icon-edit" aria-hidden="true" />
              <span>{{ labels.edit }}</span>
            </span>
            <span
              v-if="objectSummary(singleItem().data)"
              class="badge bg-secondary"
            >{{ objectSummary(singleItem().data) }}</span>
          </button>
          <JoomlaCalendarField
            v-else-if="isCalendarType(typeDefinition(property, singleItem().type))"
            :id="inputId"
            :key="singleItem().type"
            :calendar="calendar"
            :clear-label="labels.removeItem"
            :model-value="singleItem().data"
            :required="property.required"
            :type-definition="typeDefinition(property, singleItem().type)"
            @update:model-value="updateItemData(singleItem(), $event)"
          />
          <select
            v-else-if="typeDefinition(property, singleItem().type)?.input === 'boolean'"
            class="form-select microschema-value-control"
            :value="singleItem().data ?? ''"
            @change="updateItemData(singleItem(), $event.target.value)"
          >
            <option value="" />
            <option value="1">{{ labels.yes }}</option>
            <option value="0">{{ labels.no }}</option>
          </select>
          <input
            v-else
            :id="inputId"
            class="form-control microschema-value-control"
            :type="scalarInputType(typeDefinition(property, singleItem().type), singleItem().data)"
            :step="singleItem().type === 'Number' ? 'any' : undefined"
            :value="singleItem().data ?? ''"
            :required="property.required"
            @input="updateItemData(singleItem(), $event.target.value)"
            @click="rememberSelection('single', $event)"
            @keyup="rememberSelection('single', $event)"
            @select="rememberSelection('single', $event)"
          >
        </div>
        <div
          v-if="unresolved(singleItem().data).length"
          class="form-text text-warning"
        >{{ labels.unknownDataSource }}: {{ unresolved(singleItem().data).join(', ') }}</div>
      </div>
    </template>

    <template v-else>
      <div
        v-for="(item, index) in items"
        :key="itemKey(item)"
        class="microschema-value-card mb-2"
      >
        <div
          v-if="item.collection"
          class="mb-2"
        >
          <label
            class="form-label small fw-semibold"
            :for="`${inputId}-${itemKey(item)}-collection`"
          >{{ labels.collection }}</label>
          <select
            :id="`${inputId}-${itemKey(item)}-collection`"
            class="form-select form-select-sm"
            :value="item.collection"
            @change="updateItemCollection(item, $event.target.value, index)"
          >
            <option
              v-if="!collectionDefinition(dataSourceCatalog, item.collection)"
              disabled
              :value="item.collection"
            >{{ item.collection }}</option>
            <option
              v-for="collection in collections"
              :key="collection.name"
              :value="collection.name"
            >{{ collection.label }}</option>
          </select>
          <div class="form-text">{{ labels.collectionItem }}</div>
        </div>
        <div class="input-group microschema-typed-value">
          <select
            v-if="property.types.length > 1"
            class="form-select microschema-type-select"
            :aria-label="labels.type"
            :value="item.type"
            @change="updateItemType(item, $event.target.value, index)"
          >
            <option
              v-for="type in property.types"
              :key="type.name"
              :value="type.name"
            >{{ type.name }}</option>
          </select>

          <button
            v-if="typeDefinition(property, item.type)?.kind === 'object'"
            type="button"
            class="btn btn-outline-primary microschema-value-control d-flex justify-content-between align-items-center"
            @click="openObject(item, index)"
          >
            <span class="d-flex align-items-center gap-2">
              <span class="icon-edit" aria-hidden="true" />
              <span>{{ labels.edit }}</span>
            </span>
            <span
              v-if="objectSummary(item.data)"
              class="badge bg-secondary"
            >{{ objectSummary(item.data) }}</span>
          </button>
          <JoomlaCalendarField
            v-else-if="isCalendarType(typeDefinition(property, item.type))"
            :id="`${inputId}-${itemKey(item)}`"
            :key="`${itemKey(item)}-${item.type}`"
            :calendar="calendar"
            :clear-label="labels.removeItem"
            :model-value="item.data"
            :type-definition="typeDefinition(property, item.type)"
            @update:model-value="updateItemData(item, $event, index)"
          />
          <select
            v-else-if="typeDefinition(property, item.type)?.input === 'boolean'"
            class="form-select microschema-value-control"
            :value="item.data ?? ''"
            @change="updateItemData(item, $event.target.value, index)"
          >
            <option value="" />
            <option value="1">{{ labels.yes }}</option>
            <option value="0">{{ labels.no }}</option>
          </select>
          <input
            v-else
            class="form-control microschema-value-control"
            :type="scalarInputType(typeDefinition(property, item.type), item.data)"
            :step="item.type === 'Number' ? 'any' : undefined"
            :value="item.data ?? ''"
            @input="updateItemData(item, $event.target.value, index)"
            @click="rememberSelection(itemKey(item), $event)"
            @keyup="rememberSelection(itemKey(item), $event)"
            @select="rememberSelection(itemKey(item), $event)"
          >

          <DataSourcePicker
            v-if="supportsDataSourceTemplate(typeDefinition(property, item.type))"
            compact
            :catalog="catalogForItem(item)"
            :labels="labels"
            :terminal-types="dataSourceTerminalTypes(typeDefinition(property, item.type))"
            @insert="insertItemDataSource(item, $event, index)"
          />

          <button
            type="button"
            class="btn btn-outline-danger microschema-remove-item"
            :aria-label="labels.removeItem"
            :title="labels.removeItem"
            @click="removeItem(index)"
          >
            <span class="icon-trash" aria-hidden="true" />
            <span class="visually-hidden">{{ labels.removeItem }}</span>
          </button>
        </div>
        <div
          v-if="unknownPlaceholders(item.data, catalogForItem(item)).length"
          class="form-text text-warning"
        >{{ labels.unknownDataSource }}: {{ unknownPlaceholders(item.data, catalogForItem(item)).join(', ') }}</div>
      </div>

      <div class="d-flex flex-wrap gap-2">
        <button
          type="button"
          class="btn btn-sm btn-outline-secondary"
          @click="addItem"
        >
          <span class="icon-plus" aria-hidden="true" />
          {{ labels.addItem }}
        </button>
        <button
          v-if="collections.length"
          type="button"
          class="btn btn-sm btn-outline-primary"
          @click="addCollection"
        >
          <span class="icon-copy" aria-hidden="true" />
          {{ labels.addCollection }}
        </button>
      </div>
    </template>
  </div>
</template>
