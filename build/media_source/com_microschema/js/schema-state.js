export const cloneValue = (value) => {
  if (value === undefined) {
    return undefined;
  }

  return JSON.parse(JSON.stringify(value));
};

export const isRecord = (value) => value !== null && typeof value === 'object' && !Array.isArray(value);

export const hasProperty = (value, propertyName) => isRecord(value) && propertyName in value;

export const orderActiveProperties = (properties, value, addedPropertyNames = []) => {
  const activeProperties = properties.filter((property) => (
    property.required || hasProperty(value, property.name)
  ));
  const activeByName = new Map(activeProperties.map((property) => [property.name, property]));
  const addedNames = addedPropertyNames.filter((name) => activeByName.has(name));
  const addedNameSet = new Set(addedNames);

  return [
    ...activeProperties.filter((property) => !addedNameSet.has(property.name)),
    ...addedNames.map((name) => activeByName.get(name)),
  ];
};

export const typeDefinition = (property, typeName) => (
  property.types.find((type) => type.name === typeName) || property.types[0]
);

export const isPlainProperty = (property) => (
  !property.multiple
  && property.types.length === 1
  && property.types[0].kind !== 'object'
);

export const supportsDataSourceTemplate = (type) => (
  type?.kind !== 'object'
  && ['text', 'url', 'number', 'calendar-date', 'calendar-datetime'].includes(type?.input)
);

export const isDataSourcePlaceholder = (value) => (
  /^\{[A-Za-z][A-Za-z0-9_-]*\.[A-Za-z0-9_-]+(?:\.[A-Za-z0-9_-]+)*\}$/.test(String(value ?? ''))
);

export const filterDataSourceFields = (fields, terminalTypes = []) => {
  if (!terminalTypes.length) {
    return fields || [];
  }

  return (fields || []).reduce((filtered, field) => {
    if (!field.object) {
      if (terminalTypes.includes(field.type)) {
        filtered.push(field);
      }

      return filtered;
    }

    const nestedFields = filterDataSourceFields(field.fields, terminalTypes);

    if (nestedFields.length) {
      filtered.push({ ...field, fields: nestedFields });
    }

    return filtered;
  }, []);
};

export const insertPlaceholder = (value, token, selectionStart = null, selectionEnd = null) => {
  const current = value === null || value === undefined ? '' : String(value);
  const start = Number.isInteger(selectionStart) ? selectionStart : current.length;
  const end = Number.isInteger(selectionEnd) ? selectionEnd : start;

  return `${current.slice(0, start)}${token}${current.slice(end)}`;
};

export const extractPlaceholders = (value) => (
  String(value ?? '').match(/\{[A-Za-z][A-Za-z0-9_-]*\.[A-Za-z0-9_-]+(?:\.[A-Za-z0-9_-]+)*\}/g) || []
);

export const isKnownPlaceholder = (token, catalog) => {
  const match = token.match(/^\{([A-Za-z][A-Za-z0-9_-]*)\.([A-Za-z0-9_-]+(?:\.[A-Za-z0-9_-]+)*)\}$/);

  if (!match) {
    return false;
  }

  const source = (catalog?.sources || []).find((item) => item.name === match[1]);
  let fields = source?.fields || [];
  const segments = match[2].split('.');

  for (const [index, segment] of segments.entries()) {
    const field = fields.find((item) => item.name === segment);

    if (!field) {
      return false;
    }

    if (index === segments.length - 1) {
      return !field.object;
    }

    if (!field.object) {
      return false;
    }

    fields = field.fields || [];
  }

  return false;
};

export const unknownPlaceholders = (value, catalog) => (
  [...new Set(extractPlaceholders(value).filter((token) => !isKnownPlaceholder(token, catalog)))]
);

export const createTypedValue = (property, typeName = '') => {
  const type = typeDefinition(property, typeName);

  return {
    type: type?.name || '',
    data: type?.kind === 'object' ? {} : '',
  };
};

export const createCollectionValue = (property, collectionName) => ({
  ...createTypedValue(property),
  collection: collectionName,
});

export const collectionDefinition = (catalog, collectionName) => (
  (catalog?.collections || []).find((collection) => collection.name === collectionName) || null
);

export const createPropertyValue = (property) => {
  if (isPlainProperty(property)) {
    return '';
  }

  if (property.multiple) {
    return property.required ? [createTypedValue(property)] : [];
  }

  return createTypedValue(property);
};

export const normalizeObjectValue = (value) => (isRecord(value) ? cloneValue(value) : {});

export const ensureRequiredProperties = (value, schema) => {
  if (!isRecord(value) || !schema) {
    return value;
  }

  schema.properties.forEach((property) => {
    if (property.required && !hasProperty(value, property.name)) {
      value[property.name] = createPropertyValue(property);
      return;
    }

    if (
      property.required
      && property.multiple
      && (!Array.isArray(value[property.name]) || value[property.name].length === 0)
    ) {
      value[property.name] = [createTypedValue(property)];
    }
  });

  return value;
};

export const objectSummary = (value) => {
  if (!isRecord(value)) {
    return '';
  }

  const populated = Object.values(value).filter((item) => {
    if (Array.isArray(item)) {
      return item.length > 0;
    }

    if (isRecord(item)) {
      return objectSummary(item) !== '';
    }

    return item !== '' && item !== null && item !== undefined;
  }).length;

  return populated > 0 ? String(populated) : '';
};

export const formatCalendarValue = (value, type) => {
  if (value === null || value === undefined) {
    return '';
  }

  const stringValue = String(value);

  if (type?.input !== 'calendar-datetime') {
    return stringValue;
  }

  const normalized = stringValue.replace(
    /^(\d{4}-\d{2}-\d{2})T(\d{2}:\d{2})/,
    '$1 $2',
  );
  const match = normalized.match(
    /^(\d{4}-\d{2}-\d{2} \d{2}:\d{2}(?::\d{2}(?:\.\d{1,3})?)?)(?:Z|[+-]\d{2}:\d{2})?$/,
  );

  return match ? match[1] : normalized;
};

export const parseCalendarValue = (value, type) => {
  const stringValue = value === null || value === undefined ? '' : String(value).trim();

  if (stringValue === '0000-00-00' || stringValue === '0000-00-00 00:00:00') {
    return '';
  }

  if (type?.input !== 'calendar-datetime') {
    return stringValue;
  }

  return stringValue.replace(
    /^(\d{4}-\d{2}-\d{2})\s+(\d{2}:\d{2})/,
    '$1T$2',
  );
};
