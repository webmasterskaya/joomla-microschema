import { createApp } from 'vue';
import SchemaEditor from './components/SchemaEditor.vue';

const mountEditors = () => {
  const definitions = window.Joomla?.getOptions('com_microschema.schemaEditors', {}) || {};

  document.querySelectorAll('[data-microschema-schema-editor]').forEach((element) => {
    const definition = definitions[element.id];

    if (!definition || element.dataset.microschemaMounted === 'true') {
      return;
    }

    element.dataset.microschemaMounted = 'true';
    createApp(SchemaEditor, { config: definition }).mount(element);
  });
};

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', mountEditors, { once: true });
} else {
  mountEditors();
}

document.addEventListener('joomla:updated', mountEditors);
