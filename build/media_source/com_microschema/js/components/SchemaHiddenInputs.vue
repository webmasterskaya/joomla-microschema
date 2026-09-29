<script>
import {
  hasProperty,
  isPlainProperty,
  isRecord,
  typeDefinition,
} from '../schema-state.js';

export default {
  name: 'SchemaHiddenInputs',
  props: {
    data: {
      type: Object,
      required: true,
    },
    prefix: {
      type: String,
      required: true,
    },
    schemaType: {
      type: String,
      required: true,
    },
    schemas: {
      type: Object,
      required: true,
    },
  },
  methods: {
    hasProperty,
    isPlainProperty,
    isRecord,
    typeDefinition,
    items(property) {
      const value = this.data[property.name];

      if (property.multiple) {
        return Array.isArray(value) ? value : [];
      }

      return value && typeof value === 'object' ? [value] : [];
    },
    scalar(value) {
      if (value === null || value === undefined) {
        return '';
      }

      return typeof value === 'boolean' ? (value ? '1' : '0') : String(value);
    },
  },
};
</script>

<template>
  <template v-if="schemas[schemaType]">
    <template
      v-for="property in schemas[schemaType].properties"
      :key="property.name"
    >
      <input
        v-if="isPlainProperty(property) && hasProperty(data, property.name)"
        type="hidden"
        :name="`${prefix}[${property.name}]`"
        :value="scalar(data[property.name])"
      >

      <template v-else-if="hasProperty(data, property.name)">
        <template
          v-for="(item, index) in items(property)"
          :key="`${property.name}-${index}`"
        >
          <input
            v-if="property.multiple && item.collection"
            type="hidden"
            :name="`${prefix}[${property.name}][${index}][collection]`"
            :value="item.collection"
          >
          <input
            type="hidden"
            :name="property.multiple
              ? `${prefix}[${property.name}][${index}][type]`
              : `${prefix}[${property.name}][type]`"
            :value="item.type"
          >

          <SchemaHiddenInputs
            v-if="typeDefinition(property, item.type)?.kind === 'object' && isRecord(item.data)"
            :data="item.data"
            :prefix="property.multiple
              ? `${prefix}[${property.name}][${index}][data]`
              : `${prefix}[${property.name}][data]`"
            :schema-type="item.type"
            :schemas="schemas"
          />
          <input
            v-else
            type="hidden"
            :name="property.multiple
              ? `${prefix}[${property.name}][${index}][data]`
              : `${prefix}[${property.name}][data]`"
            :value="scalar(item.data)"
          >
        </template>
      </template>
    </template>
  </template>
</template>
