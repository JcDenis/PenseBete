# README

[![Release](https://img.shields.io/github/v/release/JcDenis/PenseBete?color=lightblue)](https://github.com/JcDenis/PenseBete/releases)
![Date](https://img.shields.io/github/release-date/JcDenis/PenseBete?color=red)
[![Dotclear](https://img.shields.io/badge/dotclear-v2.40-137bbb.svg)](https://dotclear.org/telechargement)
[![License](https://img.shields.io/github/license/JcDenis/PenseBete?color=white)](https://github.com/JcDenis/PenseBete/blob/master/LICENSE)

## ABOUT

_PenseBete_ is a plugin for the open-source web publishing software called [Dotclear](https://www.dotclear.org).

> Keep lists of things to do in the blog administration

It adds a page of todos to the blog administration, sorted in lists named by a single word.
Todos belong to a blog and are shared by the users of this blog who have the permission of the plugin.
Nothing is shown on the public side of the blog.

With the plugin [ClaudetteMcp](https://github.com/JcDenis/ClaudetteMcp), an AI assistant connected to the blog
can read and complete the lists.

**This is an early version.** See [CONCEPTION.md](CONCEPTION.md) (in French) for the design.

## REQUIREMENTS

* Dotclear 2.40
* PHP 8.3+

## USAGE

1. Install and enable the plugin.
2. Give the permission "manage todo lists" to the users of the blog allowed to use it. Blog administrators always are.
3. In the blog menu, open _Todo lists_.

A todo is one line, with optional details, and is "to do" or "done". Done todos are kept until they are deleted.

A list is a single word (letters, digits, `-` and `_`), saved in lower case: `Plugin` and `plugin` are the same list.
A list is created by adding a first todo to it and disappears with its last todo. Without list, a todo goes to the list `todo`.

Selected todos can be marked as done or to do, moved to another list, or deleted.
Renaming a list moves all its todos; with the name of another list, both lists are merged.

On the dashboard, from the user preferences:

* the favorite _Todo lists_ is an icon giving the number of todos to do,
* an option of the dashboard displays the last todos to do added, and another one sets how many.

## AI ASSISTANT

If the plugin [ClaudetteMcp](https://github.com/JcDenis/ClaudetteMcp) is enabled on the blog, _PenseBete_ adds these tools to its connector:

| Tool | Description |
|---|---|
| `list_todos` | Get the lists of the blog and their todos, by list, status or search |
| `add_todo` | Add a todo to a list |
| `update_todo` | Mark a todo as done or to do, change its title or its note, move it to another list |

The assistant acts with the permissions of the user of the key: this user needs the permission "manage todo lists".
Writing tools are not available with a read only key.
No tool deletes a todo: asked to delete one, the assistant marks it as done, and deleting is done from the blog administration.

ClaudetteMcp is not required. After installing or updating _PenseBete_, reconnect the connector of the assistant to validate the new tools.

## LINKS

* [License](https://github.com/JcDenis/PenseBete/blob/master/LICENSE)
* [Packages & details](https://github.com/JcDenis/PenseBete/releases)
* [Issues & security](https://github.com/JcDenis/PenseBete/issues)

## CONTRIBUTORS

* Jean-Christian Denis (author)
* Claude (AI assistant by Anthropic)

You are welcome to contribute to this code.
