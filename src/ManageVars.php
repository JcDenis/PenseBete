<?php

declare(strict_types=1);

namespace Dotclear\Plugin\PenseBete;

use Dotclear\Core\Backend\Filter\Filters;
use Dotclear\Core\Backend\Filter\FiltersLibrary;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Stack\Filter;

/**
 * @brief       PenseBete manage page request values.
 * @ingroup     PenseBete
 *
 * The todos list uses the filters and display options of Dotclear lists.
 *
 * @author      Jean-Christian Paul Denis
 * @copyright   AGPL-3.0
 */
class ManageVars
{
    /**
     * The default number of todos per page.
     *
     * @var     int     PER_PAGE
     */
    public const PER_PAGE = 30;

    /**
     * The default sort field.
     *
     * @var     string  SORTBY
     */
    public const SORTBY = 'todo_upddt';

    /**
     * The status filter value for done todos (no value means todos to do).
     *
     * @var     string  STATUS_DONE
     */
    public const STATUS_DONE = 'done';

    /**
     * The status filter value for all todos.
     *
     * @var     string  STATUS_ALL
     */
    public const STATUS_ALL = 'all';

    /**
     * The list filters and display options.
     */
    public readonly Filters $filters;

    /**
     * The list to show, empty for all lists.
     */
    public readonly string $list;

    /**
     * The status to show, empty for todos to do.
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
     * The number of todos per page.
     */
    public readonly int $nb;

    /**
     * The sort field.
     */
    public readonly string $sortby;

    /**
     * The sort order is descending.
     */
    public readonly bool $desc;

    /**
     * The ID of the todo to edit, 0 for none.
     */
    public readonly int $id;

    /**
     * Show the form of a new todo.
     */
    public readonly bool $new;

    /**
     * Show the form to rename a list.
     */
    public readonly bool $rename;

    public function __construct()
    {
        // Sort and number per page options come from user preferences, see Backend
        $this->filters = new Filters(My::id());
        $this->filters->add([
            FiltersLibrary::getPageFilter(),
            FiltersLibrary::getSearchFilter(),
            self::listFilter(),
            self::statusFilter(),
        ]);

        $nb     = (int) $this->value('nb');
        $sortby = $this->value('sortby');

        $this->list   = $this->value('list');
        $this->status = $this->value('status');
        $this->query  = mb_substr(trim($this->value('q')), 0, 100);
        $this->page   = max(1, (int) $this->value('page'));
        $this->nb     = $nb > 0 ? $nb : self::PER_PAGE;
        $this->sortby = in_array($sortby, Todos::SORTS, true) ? $sortby : self::SORTBY;
        $this->desc   = $this->value('order') !== 'asc';

        $this->id     = max(0, self::number('id'));
        $this->new    = $this->id === 0 && self::request('new') !== '';
        $this->rename = $this->id === 0 && !$this->new && self::request('rename') !== '';
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
     * Only the values which are not the default ones.
     *
     * @return  array<string, string|int>
     */
    public function params(): array
    {
        $order  = $this->desc ? 'desc' : 'asc';
        $params = [];
        if ($this->list !== '') {
            $params['list'] = $this->list;
        }
        if ($this->status !== '') {
            $params['status'] = $this->status;
        }
        if ($this->query !== '') {
            $params['q'] = $this->query;
        }
        if ($this->filters->userOptionSortby() !== '' && $this->sortby !== $this->filters->userOptionSortby()) {
            $params['sortby'] = $this->sortby;
        }
        if ($this->filters->userOptionOrder() !== '' && $order !== $this->filters->userOptionOrder()) {
            $params['order'] = $order;
        }
        if ($this->filters->userOptionNb() > 0 && $this->nb !== $this->filters->userOptionNb()) {
            $params['nb'] = $this->nb;
        }
        if ($this->page > 1) {
            $params['page'] = $this->page;
        }

        return $params;
    }

    /**
     * Get the sort fields of the todos list.
     *
     * @return  array<string, string>
     */
    public static function sortbyCombo(): array
    {
        return [
            __('Last update')   => 'todo_upddt',
            __('Creation date') => 'todo_creadt',
            __('Todo')          => 'todo_title',
            __('List')          => 'todo_list',
        ];
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
     * Get the lists filter: only existing lists can be chosen.
     */
    private static function listFilter(): Filter
    {
        $options = [__('All lists') => ''];
        foreach (Todos::getLists() as $list) {
            $options[Html::escapeHTML(sprintf('%s (%s)', $list['name'], $list['todo']))] = $list['name'];
        }

        return (new Filter('list'))
            ->title(__('List:'))
            ->options($options)
            ->prime(true);
    }

    /**
     * Get the status filter: without value, todos to do are shown.
     */
    private static function statusFilter(): Filter
    {
        return (new Filter('status'))
            ->title(__('Status:'))
            ->options([
                __('To do')        => '',
                __('Done')         => self::STATUS_DONE,
                __('All statuses') => self::STATUS_ALL,
            ])
            ->prime(true);
    }

    /**
     * Get a filter value as a text.
     */
    private function value(string $id): string
    {
        $value = $this->filters->value($id);

        return is_scalar($value) ? (string) $value : '';
    }

    /**
     * Get a number from the request.
     */
    private static function number(string $name): int
    {
        return isset($_REQUEST[$name]) && is_numeric($_REQUEST[$name]) ? (int) $_REQUEST[$name] : 0;
    }
}
