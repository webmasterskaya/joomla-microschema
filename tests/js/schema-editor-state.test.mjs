import assert from 'node:assert/strict';
import test from 'node:test';
import { computed, reactive } from 'vue';
import {
  collectionDefinition,
  createCollectionValue,
  createPropertyValue,
  ensureRequiredProperties,
  formatCalendarValue,
  filterDataSourceFields,
  hasProperty,
  insertPlaceholder,
  isDataSourcePlaceholder,
  isKnownPlaceholder,
  isPlainProperty,
  objectSummary,
  orderActiveProperties,
  parseCalendarValue,
  supportsDataSourceTemplate,
  unknownPlaceholders,
} from '../../build/media_source/com_microschema/js/schema-state.js';

const scalar = {
  name: 'headline',
  required: true,
  multiple: false,
  types: [{ name: 'string', kind: 'scalar', input: 'text' }],
};

const object = {
  name: 'publisher',
  required: false,
  multiple: false,
  types: [{ name: 'Organization', kind: 'object', input: 'text' }],
};

test('plain scalar properties keep the legacy scalar value shape', () => {
  assert.equal(isPlainProperty(scalar), true);
  assert.equal(createPropertyValue(scalar), '');
});

test('object properties use the legacy type/data wrapper', () => {
  assert.deepEqual(createPropertyValue(object), {
    type: 'Organization',
    data: {},
  });
});

test('collection templates extend the existing type/data wrapper without changing static items', () => {
  assert.deepEqual(createCollectionValue(object, 'content.category.articles'), {
    type: 'Organization',
    data: {},
    collection: 'content.category.articles',
  });
  assert.equal(collectionDefinition({
    collections: [{ name: 'content.category.articles', label: 'Articles' }],
  }, 'content.category.articles')?.label, 'Articles');
});

test('required properties are initialized without replacing existing values', () => {
  const value = { headline: 'Existing' };
  ensureRequiredProperties(value, { properties: [scalar] });
  assert.equal(value.headline, 'Existing');
});

test('required collections always contain an editable item', () => {
  const value = { author: [] };
  const author = {
    name: 'author',
    required: true,
    multiple: true,
    types: [{ name: 'Person', kind: 'object', input: 'text' }],
  };

  ensureRequiredProperties(value, { properties: [author] });
  assert.deepEqual(value.author, [{ type: 'Person', data: {} }]);
});

test('object summaries count populated child properties', () => {
  assert.equal(objectSummary({ name: 'Example', url: '' }), '1');
});

test('property presence updates Vue computed values when a property is added or removed', () => {
  const value = reactive({});
  const isActive = computed(() => hasProperty(value, 'publisher'));

  assert.equal(isActive.value, false);

  value.publisher = { type: 'Organization', data: {} };
  assert.equal(isActive.value, true);

  delete value.publisher;
  assert.equal(isActive.value, false);
});

test('newly added properties are rendered after properties already present in the form', () => {
  const properties = [
    scalar,
    object,
    {
      name: 'datePublished',
      required: false,
      multiple: false,
      types: [{ name: 'Date', kind: 'scalar', input: 'calendar-date' }],
    },
  ];
  const value = {
    headline: 'Example',
    publisher: { type: 'Organization', data: {} },
    datePublished: '2026-07-29',
  };

  assert.deepEqual(
    orderActiveProperties(properties, value, ['publisher']).map((property) => property.name),
    ['headline', 'datePublished', 'publisher'],
  );
});

test('Schema.org DateTime values are adapted for the Joomla calendar', () => {
  const type = { name: 'DateTime', kind: 'scalar', input: 'calendar-datetime' };

  assert.equal(formatCalendarValue('2026-07-29T13:45:00+03:00', type), '2026-07-29 13:45:00');
  assert.equal(formatCalendarValue('2026-07-29 13:45:00', type), '2026-07-29 13:45:00');
  assert.equal(formatCalendarValue('2026-07-29T13:45Z', type), '2026-07-29 13:45');
  assert.equal(parseCalendarValue('2026-07-29 13:45:00', type), '2026-07-29T13:45:00');
  assert.equal(parseCalendarValue('0000-00-00 00:00:00', type), '');
});

test('placeholders are inserted at the current selection', () => {
  assert.equal(insertPlaceholder('Hello world', '{article.title}', 6, 11), 'Hello {article.title}');
  assert.equal(insertPlaceholder('', '{article.title}'), '{article.title}');
});

test('data source templates support text, URL, and calendar values', () => {
  assert.equal(supportsDataSourceTemplate({ kind: 'scalar', input: 'text' }), true);
  assert.equal(supportsDataSourceTemplate({ kind: 'scalar', input: 'url' }), true);
  assert.equal(supportsDataSourceTemplate({ kind: 'scalar', input: 'calendar-date' }), true);
  assert.equal(supportsDataSourceTemplate({ kind: 'scalar', input: 'calendar-datetime' }), true);
  assert.equal(supportsDataSourceTemplate({ kind: 'scalar', input: 'number' }), true);
  assert.equal(supportsDataSourceTemplate({ kind: 'object', input: 'text' }), false);
});

test('calendar placeholders must occupy the complete value', () => {
  assert.equal(isDataSourcePlaceholder('{article.created}'), true);
  assert.equal(isDataSourcePlaceholder('{article.author.registerDate}'), true);
  assert.equal(isDataSourcePlaceholder('Published {article.created}'), false);
  assert.equal(isDataSourcePlaceholder('{article.created} suffix'), false);
});

test('data source fields can be filtered by the calendar value type', () => {
  const fields = [
    { name: 'title', type: 'String', object: false },
    { name: 'created', type: 'DateTime', object: false },
    {
      name: 'author',
      type: 'JoomlaUser',
      object: true,
      fields: [
        { name: 'name', type: 'String', object: false },
        { name: 'registerDate', type: 'DateTime', object: false },
      ],
    },
  ];

  assert.deepEqual(filterDataSourceFields(fields, ['DateTime']), [
    { name: 'created', type: 'DateTime', object: false },
    {
      name: 'author',
      type: 'JoomlaUser',
      object: true,
      fields: [{ name: 'registerDate', type: 'DateTime', object: false }],
    },
  ]);
});

test('unknown placeholders are reported once', () => {
  const catalog = {
    sources: [{
      name: 'article',
      fields: [
        { name: 'title', type: 'String', object: false },
        {
          name: 'author',
          type: 'User',
          object: true,
          fields: [{ name: 'name', type: 'String', object: false }],
        },
      ],
    }],
  };

  assert.equal(isKnownPlaceholder('{article.title}', catalog), true);
  assert.equal(isKnownPlaceholder('{article.author.name}', catalog), true);
  assert.equal(isKnownPlaceholder('{article.author}', catalog), false);

  assert.deepEqual(
    unknownPlaceholders('{article.title} {article.author.name} {missing.value} {missing.value}', catalog),
    ['{missing.value}'],
  );
});
