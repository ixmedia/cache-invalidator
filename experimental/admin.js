document.addEventListener('DOMContentLoaded', function() {
  const postTypeRepeaterContainer = document.getElementById('postTypeTriggersRepeater');
  const addPostTypeTriggerButton = document.getElementById('addPostTypeTrigger');
  const postTypeTemplate = postTypeRepeaterContainer.getAttribute('data-template');

  addPostTypeTriggerButton.addEventListener('click', function() {
      const index = postTypeRepeaterContainer.querySelectorAll('.repeater-item').length;
      const newItem = document.createElement('div');
      newItem.innerHTML = postTypeTemplate.replace(/__index__/g, index);
      postTypeRepeaterContainer.appendChild(newItem);
  });

  postTypeRepeaterContainer.addEventListener('click', function(event) {
      if (event.target.classList.contains('add-target')) {
          const targetRepeaterContainer = event.target.nextElementSibling;
          const targetTemplate = targetRepeaterContainer.getAttribute('data-template');
          const postTypeIndex = event.target.closest('.repeater-item').querySelector('select').name.match(/\[(\d+)\]/)[1];
          const targetIndex = targetRepeaterContainer.querySelectorAll('.target-item').length;
          const newItem = document.createElement('div');
          newItem.innerHTML = targetTemplate.replace(/__index__/g, postTypeIndex).replace(/__target_index__/g, targetIndex);
          targetRepeaterContainer.appendChild(newItem);
      }

      if (event.target.classList.contains('delete')) {
          if (event.target.closest('.repeater-item')) {
              removePostTypeTrigger(event.target);
          } else if (event.target.closest('.target-item')) {
              removeTarget(event.target);
          }
      }
  });
});

function removePostTypeTrigger(button) {
  const item = button.closest('.repeater-item');
  item.parentNode.removeChild(item);
}

function removeTarget(button) {
  const item = button.closest('.target-item');
  item.parentNode.removeChild(item);
}

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

// Toggle the visibility of the target fields based on the selected post type
function toggleTargetFields(selectElement) {
  const targetRepeater = selectElement.closest('.repeater-item').querySelector('.target-repeater');
  if (selectElement.value === '') {
      targetRepeater.style.display = 'none';
  } else {
      targetRepeater.style.display = '';
  }
}
