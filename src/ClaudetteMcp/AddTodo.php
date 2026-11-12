<?php

declare(strict_types=1);

namespace Dotclear\Plugin\PenseBete\ClaudetteMcp;

use Dotclear\Plugin\PenseBete\Todos;

/**
 * @brief       PenseBete tool for the plugin ClaudetteMcp: add a todo.
 * @ingroup     PenseBete
 *
 * @author      Jean-Christian Paul Denis
 * @copyright   AGPL-3.0
 */
class AddTodo extends TodoTool
{
    public function name(): string
    {
        return 'add_todo';
    }

    public function title(): string
    {
        return 'Add a todo';
    }

    public function description(): string
    {
        return 'Add a todo to a list of the blog (plugin PenseBete, see list_todos). ' .
            'A list is named by a single word, and is created by its first todo. ' .
            'When the user names a list, use the existing list with the closest name (see "lists" in list_todos), ' .
            'whatever the case, the accents or the plural: create a new list only if none matches or if the user asks for a new one. ' .
            'Without list, the todo goes to the list "' . Todos::DEFAULT_LIST . '". ' .
            'The result tells if a list was created ("new_list").';
    }

    public function properties(): array
    {
        return [
            'title' => [
                'type'        => 'string',
                'description' => 'What there is to do, in one line of ' . Todos::TITLE_LENGTH . ' characters at most.',
            ],
            'list' => [
                'type'        => 'string',
                'description' => 'The list name: a single word of ' . Todos::LIST_LENGTH . ' characters at most, ' .
                    'made of letters, digits, "-" and "_". It is saved in lower case.',
            ],
            'note' => [
                'type'        => 'string',
                'description' => 'Some details, if the title is not enough.',
            ],
        ];
    }

    public function required(): array
    {
        return ['title'];
    }

    public function readOnly(): bool
    {
        return false;
    }

    public function call(array $args): array
    {
        $title = $this->todoTitle($args);
        $list  = $this->todoList($args);
        $new   = !in_array($list, Todos::getListsNames(), true);

        return $this->describe(Todos::add($title, $list, $this->str($args, 'note'))) + [
            'new_list' => $new,
        ];
    }
}
