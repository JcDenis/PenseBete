# PenseBete

Notes for AI assistants working on the Dotclear plugin PenseBete.

**Read first** the shared conventions of the author's Dotclear modules:
https://raw.githubusercontent.com/JcDenis/gh2da/master/CONVENTIONS.md
The rules below take precedence for this module.

## Module

Todo lists in the blog administration (menu Blog), with tools for an AI assistant connected through ClaudetteMcp.
Nothing on the public side: no URL, no widget, no template tag.

Required Dotclear and PHP versions: see `_define.php`.

## Structure

* `src/Todos.php`: all database work on table `pensebete` (lists, search, add, update, move, delete, rename).
  Its methods work on the current blog and **do not check permissions**: callers must do it.
* `src/Todo.php`: a todo (read only object).
* `src/Install.php`: table structure. A change of the table needs a migration there.
* `src/Prepend.php`: permission type `PenseBete` ("manage todo lists"). `My::isAllowed()` checks it on the current blog.
* `src/Manage.php` (list, filters, actions on selected todos, quick buttons), `ManageTodo.php` (add and edit form),
  `ManageRename.php` (rename or merge lists), `ManageVars.php` (filters and display options); `js/manage.js`.
* `src/BackendBehaviors.php`: filters options (`adminFiltersListsV3`), dashboard favorite with the number of todos to do,
  dashboard module of the last todos and its user preferences (namespace `PenseBete`).
* `src/ClaudetteMcp/`: tools `list_todos`, `add_todo`, `update_todo` (`TodoTool.php` holds the shared checks),
  registered in `src/Frontend.php` with the behavior `ClaudetteMcpAddTool`.

## Pitfalls

* A list is a single word stored in lower case (`Todos::cleanList()`); there is no table of lists:
  a list exists as long as a todo uses it. Default list: `todo`.
* Text search is done in PHP (`Todos::match()`, case and accent insensitive), not in SQL: keep it that way,
  for the same result on MySQL, PostgreSQL and SQLite.
* No reference from `user_id` to the user table, on purpose: todos are shared by the blog and outlive their creator.
* Two statuses only (`STATUS_TODO`, `STATUS_DONE`), no trash, no due date, no reminder: decided, see `CONCEPTION.md`.
* MCP tools: no delete tool (asked to delete, the assistant marks the todo as done), permission checked in `available()`,
  writing tools return `false` in `readOnly()`. Names and descriptions in English, not translated.
  A tool name must not reuse a ClaudetteMcp tool name. Each new tool must be validated by the user in the connector: keep it to three.
* `Frontend::init()` must not be limited to some URL types, or the tools are never registered.
* The author uses PenseBete on his personal blog for his own lists: a change of the tools changes how he works with them.

## Testing

* Extra admin pages tested by CI are listed in `.github/dotclear-test.txt`.
* Check the list with filters, the add, edit and rename pages, the actions on selected todos,
  the dashboard favorite and module, in English and French.
* Tools: through a ClaudetteMcp connector on a test blog (the connector must be reconnected to validate new tools).

## Design

Read `CONCEPTION.md` (in French) before changing the design, the data model, the permissions or the tools,
and update it with the change.
