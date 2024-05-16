document.addEventListener('DOMContentLoaded', function () {
  document.getElementById('addPostTypeTrigger').addEventListener('click', addPostTypeTrigger);
  document.getElementById('addDateFieldTrigger').addEventListener('click', addDateFieldTrigger);
});

function addPostTypeTrigger() {
  const container = document.getElementById('postTypeTriggersRepeater');
  const newItem = document.createElement('div');
  newItem.className = 'repeater-item';
  newItem.innerHTML = `
      <input type="text" name="mon_plugin_options[postType][new][type]" placeholder="Post Type" />
      <textarea name="mon_plugin_options[postType][new][targets]" placeholder="Targets"></textarea>
      <button type="button" onclick="removePostTypeTrigger(this)">Remove</button>
  `;
  container.appendChild(newItem);
}

function removePostTypeTrigger(button) {
  const item = button.parentNode;
  item.parentNode.removeChild(item);
}

function addDateFieldTrigger() {
  const container = document.getElementById('dateFieldTriggersRepeater');
  const newItem = document.createElement('div');
  newItem.className = 'repeater-item';
  newItem.innerHTML = `
      <input type="text" name="mon_plugin_options[dateField][new][fieldNames]" placeholder="Field Names (comma separated)" />
      <textarea name="mon_plugin_options[dateField][new][targets]" placeholder="Targets"></textarea>
      <button type="button" onclick="removeDateFieldTrigger(this)">Remove</button>
  `;
  container.appendChild(newItem);
}

function removeDateFieldTrigger(button) {
  const item = button.parentNode;
  item.parentNode.removeChild(item);
}
