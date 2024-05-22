document.addEventListener('DOMContentLoaded', function() {
  setupRepeater(
      'postTypeTriggersRepeater',
      'addPostTypeTrigger'
  );
  setupRepeater(
      'taxonomyTriggersRepeater',
      'addTaxonomyTrigger'
  );

  function setupRepeater(containerId, buttonId) {
      const repeaterContainer = document.getElementById(containerId);
      const addButton = document.getElementById(buttonId);
      const template = repeaterContainer.getAttribute('data-template');

      addButton.addEventListener('click', function() {
          const index = repeaterContainer.querySelectorAll('.repeater-item').length;
          const newItem = document.createElement('div');
          newItem.innerHTML = template.replace(/__index__/g, index);
          repeaterContainer.appendChild(newItem);
      });

      addEventListenerToRepeater(repeaterContainer);
  }

  function addEventListenerToRepeater(container) {
      container.addEventListener('click', function(event) {
          if (event.target.classList.contains('add-target')) {
              addItem(event, '.target-item', 'data-template', '__target_index__', 'target');
          } else if (event.target.classList.contains('add-time-field')) {
              addItem(event, '.time-field-item', 'data-template', '__time_field_index__', 'time-field');
          } else if (event.target.classList.contains('delete')) {
              removeItem(event.target);
          }
      });
  }

  function addItem(event, itemClass, templateAttr, indexPlaceholder, type) {
      const repeaterContainer = event.target.nextElementSibling;
      const template = repeaterContainer.getAttribute(templateAttr);
      const parentIndex = event.target.closest('.repeater-item').querySelector('select').name.match(/\[(\d+)\]/)[1];
      const index = repeaterContainer.querySelectorAll(itemClass).length;
      const newItem = document.createElement('div');
      newItem.innerHTML = template.replace(/__index__/g, parentIndex).replace(new RegExp(indexPlaceholder, 'g'), index);
      repeaterContainer.appendChild(newItem);
  }

  function removeItem(button) {
      const item = button.closest('.repeater-item, .target-item, .time-field-item');
      if (item) {
          item.parentNode.removeChild(item);
      }
  }
});

// Toggle the visibility of the Target Value input field based on the selected target type
function toggleTargetValueInput(selectElement) {
  const targetValueInput = selectElement.nextElementSibling;
  const selectedValue = selectElement.value;
  if (['template', 'gutenberg'].includes(selectedValue)) {
      targetValueInput.style.display = '';
  } else {
      targetValueInput.style.display = 'none';
  }
}

// Toggle the visibility of the target fields based on the selected post type or taxonomy
function toggleTargetFields(selectElement) {
  const targetRepeater = selectElement.closest('.repeater-item').querySelector('.target-repeater');
  if (selectElement.value === '') {
      targetRepeater.style.display = 'none';
  } else {
      targetRepeater.style.display = '';
  }
}
