/*global dotclear */
'use strict';

dotclear.ready(() => {
  // DOM ready and content loaded

  const msg = dotclear.getData('pensebete');

  // Todos list
  const form = document.querySelector('#todos-form');
  if (form) {
    for (const elt of form.querySelectorAll('.checkboxes-helpers')) {
      dotclear.checkboxesHelpers(elt, undefined, '#todos-form td input[type=checkbox]', '#todos-form #do-action');
    }
    dotclear.condSubmit('#todos-form td input[type="checkbox"]', '#todos-form #do-action');

    // The list name is only needed to move todos
    const action = form.querySelector('#action');
    const move = form.querySelector('label.move-list');
    const toggle = () => {
      if (move) {
        move.style.display = action.value === 'move' ? '' : 'none';
      }
    };
    action?.addEventListener('change', toggle);
    toggle();

    form.querySelector('#do-action')?.addEventListener('click', (event) => {
      if (action.value !== 'delete' || globalThis.confirm(msg.confirm_delete)) {
        return true;
      }
      event.preventDefault();
      return false;
    });
  }

  // Todo edition
  document.querySelector('#todo-form #delete')?.addEventListener('click', (event) => {
    if (globalThis.confirm(msg.confirm_delete_one)) {
      return true;
    }
    event.preventDefault();
    return false;
  });
});
