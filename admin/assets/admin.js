document.addEventListener('DOMContentLoaded', function () {
    const postTypeRepeaterContainer = document.getElementById('postTypeTriggersRepeater');
    const addPostTypeTriggerButton = document.getElementById('addPostTypeTrigger');
    const postTypeTemplate = postTypeRepeaterContainer.getAttribute('data-template');

    addPostTypeTriggerButton.addEventListener('click', function () {
        const index = postTypeRepeaterContainer.querySelectorAll('.repeater-item').length;
        const newItem = document.createElement('div');
        newItem.innerHTML = postTypeTemplate.replace(/__index__/g, index);
        postTypeRepeaterContainer.appendChild(newItem);
    });

    const taxonomyRepeaterContainer = document.getElementById('taxonomyTriggersRepeater');
    const addTaxonomyTriggerButton = document.getElementById('addTaxonomyTrigger');
    const taxonomyTemplate = taxonomyRepeaterContainer.getAttribute('data-template');

    addTaxonomyTriggerButton.addEventListener('click', function () {
        const index = taxonomyRepeaterContainer.querySelectorAll('.repeater-item').length;
        const newItem = document.createElement('div');
        newItem.innerHTML = taxonomyTemplate.replace(/__index__/g, index);
        taxonomyRepeaterContainer.appendChild(newItem);
    });

    function addEventListenerToRepeater(container) {
        container.addEventListener('click', function (event) {
            const addTargetBtn = event.target.closest('.add-target');
            if (addTargetBtn) {
                const targetRepeaterContainer = addTargetBtn.previousElementSibling;
                const targetTemplate = targetRepeaterContainer.getAttribute('data-template');
                const parentIndex = addTargetBtn.closest('.repeater-item').querySelector('select').name.match(/\[(\d+)\]/)[1];
                const targetIndex = targetRepeaterContainer.querySelectorAll('.target-item').length;
                const newItem = document.createElement('div');
                newItem.innerHTML = targetTemplate.replace(/__index__/g, parentIndex).replace(/__target_index__/g, targetIndex);
                targetRepeaterContainer.appendChild(newItem);
            }

            const addTimeFieldBtn = event.target.closest('.add-time-field');
            if (addTimeFieldBtn) {
                const timeFieldRepeaterContainer = addTimeFieldBtn.previousElementSibling;
                const timeFieldTemplate = timeFieldRepeaterContainer.getAttribute('data-template');
                const parentIndex = addTimeFieldBtn.closest('.repeater-item').querySelector('select').name.match(/\[(\d+)\]/)[1];
                const timeFieldIndex = timeFieldRepeaterContainer.querySelectorAll('.time-field-item').length;
                const newItem = document.createElement('div');
                newItem.innerHTML = timeFieldTemplate.replace(/__index__/g, parentIndex).replace(/__time_field_index__/g, timeFieldIndex);
                timeFieldRepeaterContainer.appendChild(newItem);
            }

            const deleteBtn = event.target.closest('.delete');
            if (deleteBtn) {
                if (deleteBtn.closest('.target-item')) {
                    removeTarget(deleteBtn);
                } else if (deleteBtn.closest('.time-field-item')) {
                    removeTimeField(deleteBtn);
                } else if (deleteBtn.closest('.repeater-item')) {
                    removeRepeaterItem(deleteBtn);
                }
            }
        });
    }

    addEventListenerToRepeater(postTypeRepeaterContainer);
    addEventListenerToRepeater(taxonomyRepeaterContainer);
});

function removeRepeaterItem(button) {
    const item = button.closest('.repeater-item');
    item.parentNode.removeChild(item);
}

function removeTarget(button) {
    const item = button.closest('.target-item');
    item.parentNode.removeChild(item);
}

function removeTimeField(button) {
    const item = button.closest('.time-field-item');
    item.parentNode.removeChild(item);
}

// Toggle the visibility of the Target Value input field based on the selected target type
function toggleTargetValueInput(selectElement) {
    const targetValueInput = selectElement.nextElementSibling;
    const selectedValue = selectElement.value;
    if (['template', 'gutenberg', 'archive'].includes(selectedValue)) {
        targetValueInput.style.display = '';
    } else {
        targetValueInput.style.display = 'none';
    }
}

// Toggle the visibility of the target fields based on the selected post type or taxonomy
function toggleTargetFields(selectElement) {
    const repeaterItem = selectElement.closest('.repeater-item');
    const targetRepeater = repeaterItem.querySelector('.target-repeater');
    const timeFieldsRepeater = repeaterItem.querySelector('.time-fields-repeater');
    if (selectElement.value === '') {
        targetRepeater.style.display = 'none';
        if (timeFieldsRepeater) timeFieldsRepeater.style.display = 'none';
    } else {
        targetRepeater.style.display = '';
        if (timeFieldsRepeater) timeFieldsRepeater.style.display = '';
    }
}

