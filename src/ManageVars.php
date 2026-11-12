<?php

declare(strict_types=1);

namespace Dotclear\Plugin\PenseBete;

/**
 * @brief       PenseBete manage page request values.
 * @ingroup     PenseBete
 *
 * @author      Jean-Christian Paul Denis
 * @copyright   AGPL-3.0
 */
class ManageVars
{
    /**
     * The number of todos per page.
     *
     * @var     int     PER_PAGE
     */
    public const PER_PAGE = 30;

    /**
     * @var     string  STATUS_TODO
     */
    public const STATUS_TODO = 'todo';

    /**
     * @var     string  STATUS_DONE
     */
    public const STATUS_DONE = 'done';

    /**
     * @var     string  STATUS_ALL
     */
    public const STATUS_ALL = 'all';

    /**
     * The list to show, empty for all lists.
     */
    public readonly string $list;

    /**
     * The status to show.
     */
    public readonly string $status;

    /**
     * The searched text.
     */
    public readonly string $query;

    /**
     * The page number.
     */
    public readonly int $page;

    /**
     * The ID of the todo to edit, 0 for none.
     */
    public readonly int $id;

    /**
     * Show the form of a new todo.
     */
    public readonly bool $new;

    public function __construct()
    {
        $status = self::request('status');

        // The list name is not checked here: it is only used to search
        $this->list   = mb_strtolower(self::request('list'));
        $this->status = in_array($status, [self::STATUS_DONE, self::STATUS_ALL], true) ? $status : self::STATUS_TODO;
        $this->query  = mb_substr(self::request('q'), 0, 100);
        $this->page   = max(1, self::number('page'));
        $this->id     = max(0, self::number('id'));
        $this->new    = $this->id === 0 && self::request('new') !== '';
    }

    /**
     * Get the todos status to show, null for all.
     */
    public function todoStatus(): ?int
    {
        return match ($this->status) {
            self::STATUS_DONE => Todos::STATUS_DONE,
            self::STATUS_ALL  => null,
            default           => Todos::STATUS_TODO,
        };
    }

    /**
     * Get the URL parameters to come back to the same list.
     *
     * @return  array<string, string|int>
     */
    public function params(): array
    {
        $params = [];
        if ($this->list !== '') {
            $params['list'] = $this->list;
        }
        if ($this->status !== self::STATUS_TODO) {
            $params['status'] = $this->status;
        }
        if ($this->query !== '') {
            $params['q'] = $this->query;
        }
        if ($this->page > 1) {
            $params['page'] = $this->page;
        }

        return $params;
    }

    /**
     * Get a text from the request.
     */
    public static function request(string $name): string
    {
        return isset($_REQUEST[$name]) && is_string($_REQUEST[$name]) ? trim($_REQUEST[$name]) : '';
    }

    /**
     * Get a text from a submitted form.
     */
    public static function post(string $name): string
    {
        return isset($_POST[$name]) && is_string($_POST[$name]) ? trim($_POST[$name]) : '';
    }

    /**
     * Get a number from the request.
     */
    private static function number(string $name): int
    {
        return isset($_REQUEST[$name]) && is_numeric($_REQUEST[$name]) ? (int) $_REQUEST[$name] : 0;
    }
}
