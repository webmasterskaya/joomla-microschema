const schemaTabNames = ['organization', 'website'];

const getTabTarget = (trigger) => {
  const target = trigger.getAttribute('data-bs-target')
    || trigger.getAttribute('href')
    || trigger.getAttribute('aria-controls');

  return target ? target.replace(/^#/, '') : '';
};

const findTabTriggers = (name) => [...document.querySelectorAll('[role="tab"], [data-bs-toggle="tab"]')]
  .filter((trigger) => {
    const target = getTabTarget(trigger);

    return target === name || target.endsWith(`-${name}`);
  });

const toggleSchemaTabs = () => {
  const enabledField = document.querySelector('input[name="jform[schemaorg_enabled]"]:checked');
  const visible = enabledField?.value === '1';
  let activeTabWasHidden = false;

  schemaTabNames.forEach((name) => {
    findTabTriggers(name).forEach((trigger) => {
      const tabContainer = trigger.closest('li') || trigger;

      if (!visible && trigger.classList.contains('active')) {
        activeTabWasHidden = true;
      }

      tabContainer.hidden = !visible;

      const target = getTabTarget(trigger);
      const pane = target ? document.getElementById(target) : null;

      if (pane) {
        pane.hidden = !visible;
      }
    });
  });

  if (activeTabWasHidden) {
    const settingsTab = findTabTriggers('settings')[0];

    settingsTab?.click();
  }
};

document.addEventListener('DOMContentLoaded', toggleSchemaTabs);
document.addEventListener('joomla:updated', toggleSchemaTabs);
document.addEventListener('change', (event) => {
  if (event.target instanceof HTMLInputElement && event.target.name === 'jform[schemaorg_enabled]') {
    toggleSchemaTabs();
  }
});
