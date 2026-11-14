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

    // Details of todos, shown by the expand buttons as on the posts list
    dotclear.expandContent({
      line: form.querySelector('#todos thead tr'),
      lines: form.querySelectorAll('#todos tbody tr.line[data-note]'),
      callback: (line) => {
        const target = dotclear.node(line);
        const id = `${target.id}_note`;
        let tr = document.getElementById(id);
        if (tr) {
          tr.style.display = tr.style.display === 'none' ? '' : 'none';
          target.classList.toggle('expand');
          return;
        }
        tr = document.createElement('tr');
        tr.id = id;
        const td = document.createElement('td');
        td.colSpan = target.children.length;
        td.className = 'expand';
        td.style.whiteSpace = 'pre-line';
        td.textContent = target.dataset.note;
        tr.appendChild(td);
        target.classList.add('expand');
        target.parentNode.insertBefore(tr, target.nextSibling);
      },
    });

    // Headers in cells on small screens
    dotclear.responsiveCellHeaders(form.querySelector('#todos'), '#todos', 1, true);

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
