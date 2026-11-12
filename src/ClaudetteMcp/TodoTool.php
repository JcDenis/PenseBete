<?php

declare(strict_types=1);

namespace Dotclear\Plugin\PenseBete\ClaudetteMcp;

use Dotclear\App;
use Dotclear\Helper\Date;
use Dotclear\Plugin\ClaudetteMcp\McpTool;
use Dotclear\Plugin\ClaudetteMcp\McpToolException;
use Dotclear\Plugin\PenseBete\My;
use Dotclear\Plugin\PenseBete\Todo;
use Dotclear\Plugin\PenseBete\Todos;
use Exception;

/**
 * @brief       PenseBete tools helper for the plugin ClaudetteMcp.
 * @ingroup     PenseBete
 *
 * ClaudetteMcp connects an AI assistant to the blog: these tools let the assistant read and complete the todo lists.
 * These classes are only loaded by ClaudetteMcp, which is not required by PenseBete.
 *
 * Tools run in frontend context, where Dotclear checks nothing: the permission of the plugin is checked here,
 * and the todos handler only works on the todos of the current blog.
 * There is no tool to delete a todo: this is only done from the blog administration.
 *
 * Names, descriptions and error messages are read by the assistant: they are not translated.
 *
 * @author      Jean-Christian Paul Denis
 * @copyright   AGPL-3.0
 */
abstract class TodoTool extends McpTool
{
    /**
     * The todos statuses, by the names the tools use.
     *
     * @var     array<string, int>  STATUSES
     */
    protected const STATUSES = [
        'todo' => Todos::STATUS_TODO,
        'done' => Todos::STATUS_DONE,
    ];

    /**
     * Like in backend: the user must have the permission of the plugin on the blog.
     */
    public function available(): bool
    {
        return $this->can(My::id());
    }

    /**
     * Get the list name argument, the default list if none.
     *
     * @param   array<string, mixed>    $args   The arguments
     *
     * @throws  McpToolException    If this is not a list name
     */
    protected function todoList(array $args): string
    {
        try {
            return Todos::cleanList($this->str($args, 'list'));
        } catch (Exception) {
            throw new McpToolException(sprintf(
                'A list name is a single word of %s characters at most, made of letters, digits, "-" and "_"',
                Todos::LIST_LENGTH
            ));
        }
    }

    /**
     * Get the title argument.
     *
     * @param   array<string, mixed>    $args   The arguments
     *
     * @throws  McpToolException    If there is no title or a too long one
     */
    protected function todoTitle(array $args): string
    {
        $title = trim((string) preg_replace('/\s+/u', ' ', $this->str($args, 'title')));
        if ($title === '') {
            throw new McpToolException('The title of a todo can not be empty');
        }

        // A longer title would be cut
        if (mb_strlen($title) > Todos::TITLE_LENGTH) {
            throw new McpToolException(sprintf(
                'The title of a todo is one line of %s characters at most: put the rest in "note"',
                Todos::TITLE_LENGTH
            ));
        }

        return $title;
    }

    /**
     * Get the status argument.
     *
     * @param   array<string, mixed>    $args   The arguments
     *
     * @throws  McpToolException    If status is unknown
     */
    protected function todoStatus(array $args): int
    {
        $status = $this->str($args, 'status');
        if (!isset(self::STATUSES[$status])) {
            throw new McpToolException('Unknown status, use one of: ' . implode(', ', array_keys(self::STATUSES)));
        }

        return self::STATUSES[$status];
    }

    /**
     * Describe a todo.
     *
     * Dates are in the timezone of the user, like in the blog administration.
     *
     * @return  array<string, mixed>
     */
    protected function describe(Todo $todo): array
    {
        $user_tz = is_string($user_tz = App::auth()->getInfo('user_tz')) ? $user_tz : 'UTC';

        return [
            'id'      => $todo->id,
            'list'    => $todo->list,
            'title'   => $todo->title,
            'note'    => $todo->note,
            'status'  => $todo->isDone() ? 'done' : 'todo',
            'user'    => $todo->user,
            'created' => Date::dt2str('%Y-%m-%d %H:%M', $todo->created, $user_tz),
            'updated' => Date::dt2str('%Y-%m-%d %H:%M', $todo->updated, $user_tz),
        ];
    }
}
