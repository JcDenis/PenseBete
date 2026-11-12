<?php

declare(strict_types=1);

namespace Dotclear\Plugin\PenseBete\ClaudetteMcp;

use Dotclear\Plugin\ClaudetteMcp\McpToolException;
use Dotclear\Plugin\PenseBete\Todo;
use Dotclear\Plugin\PenseBete\Todos;

/**
 * @brief       PenseBete tool for the plugin ClaudetteMcp: update a todo.
 * @ingroup     PenseBete
 *
 * @author      Jean-Christian Paul Denis
 * @copyright   AGPL-3.0
 */
class UpdateTodo extends TodoTool
{
    public function name(): string
    {
        return 'update_todo';
    }

    public function title(): string
    {
        return 'Update a todo';
    }

    public function description(): string
    {
        return 'Update a todo of the blog (plugin PenseBete, see list_todos): mark it as done or as to do again, ' .
            'change its title or its note, or move it to another list. Only the given arguments are changed. ' .
            'A todo can not be deleted with this tool: when the user asks to delete a todo, mark it as done ' .
            'and tell the user that done todos are deleted from the blog administration. ' .
            'To move a todo, use the existing list with the closest name, like with add_todo. ' .
            'Only do this when the user asks for it.';
    }

    public function properties(): array
    {
        return [
            'id' => [
                'type'        => 'integer',
                'description' => 'The todo ID (see list_todos).',
            ],
            'status' => [
                'type'        => 'string',
                'enum'        => array_keys(self::STATUSES),
                'description' => 'The new status.',
            ],
            'title' => [
                'type'        => 'string',
                'description' => 'The new title, in one line of ' . Todos::TITLE_LENGTH . ' characters at most.',
            ],
            'note' => [
                'type'        => 'string',
                'description' => 'The new note, it replaces the current one. An empty text removes the note.',
            ],
            'list' => [
                'type'        => 'string',
                'description' => 'The name of the list to move the todo to.',
            ],
        ];
    }

    public function required(): array
    {
        return ['id'];
    }

    public function readOnly(): bool
    {
        return false;
    }

    public function call(array $args): array
    {
        $todo = Todos::get($this->int($args, 'id'));
        if (!$todo instanceof Todo) {
            throw new McpToolException('Unknown todo, see list_todos');
        }

        $status = $this->has($args, 'status') ? $this->todoStatus($args) : null;
        $title  = $this->has($args, 'title') ? $this->todoTitle($args) : null;
        $note   = $this->has($args, 'note') ? $this->str($args, 'note') : null;
        $list   = $this->str($args, 'list') !== '' ? $this->todoList($args) : null;

        if (is_null($status) && is_null($title) && is_null($note) && is_null($list)) {
            throw new McpToolException('Nothing to change, give at least one of: status, title, note, list');
        }

        return $this->describe(Todos::update($todo->id, $title, $note, $list, $status));
    }
}
