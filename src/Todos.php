<?php

declare(strict_types=1);

namespace Dotclear\Plugin\PenseBete;

use Dotclear\App;
use Dotclear\Database\MetaRecord;
use Dotclear\Database\Statement\DeleteStatement;
use Dotclear\Database\Statement\SelectStatement;
use Dotclear\Database\Statement\UpdateStatement;
use Dotclear\Helper\Text;
use Exception;
use Throwable;

/**
 * @brief       PenseBete todos handler.
 * @ingroup     PenseBete
 *
 * Todos belong to a blog and are shared by the users of this blog who have the permission of the plugin.
 * A list is a single word: there is no table of lists, a list exists as long as a todo uses it.
 *
 * All methods work on current blog and do NOT check permissions: callers must do it.
 *
 * @author      Jean-Christian Paul Denis
 * @copyright   AGPL-3.0
 */
class Todos
{
    /**
     * The todos table name (lower case, whatever the database is).
     *
     * @var     string  TABLE_NAME
     */
    public const TABLE_NAME = 'pensebete';

    /**
     * @var     int     STATUS_TODO
     */
    public const STATUS_TODO = 0;

    /**
     * @var     int     STATUS_DONE
     */
    public const STATUS_DONE = 1;

    /**
     * The list used when none is given.
     *
     * @var     string  DEFAULT_LIST
     */
    public const DEFAULT_LIST = 'todo';

    /**
     * @var     int     LIST_LENGTH
     */
    public const LIST_LENGTH = 32;

    /**
     * @var     int     TITLE_LENGTH
     */
    public const TITLE_LENGTH = 255;

    /**
     * The fields a list of todos can be sorted by.
     *
     * @var     array<int, string>  SORTS
     */
    public const SORTS = ['todo_upddt', 'todo_creadt', 'todo_title', 'todo_list'];

    /**
     * Get full table name.
     */
    public static function table(): string
    {
        return App::db()->con()->prefix() . self::TABLE_NAME;
    }

    /**
     * Clean a list name.
     *
     * A list name is a single word, saved in lower case. Without name, this is the default list.
     *
     * @throws  Exception   If the name is not a single word
     */
    public static function cleanList(string $list): string
    {
        $list = mb_strtolower(trim($list));
        if ($list === '') {
            return self::DEFAULT_LIST;
        }

        if (mb_strlen($list) > self::LIST_LENGTH || preg_match('/^[\p{L}\p{N}_-]+$/u', $list) !== 1) {
            throw new Exception(sprintf(
                __('A list name is a single word of %s characters at most, made of letters, digits, "-" and "_".'),
                self::LIST_LENGTH
            ));
        }

        return $list;
    }

    /**
     * Clean a todo title.
     *
     * A title is a single line.
     *
     * @throws  Exception   If the title is empty
     */
    public static function cleanTitle(string $title): string
    {
        $title = trim((string) preg_replace('/\s+/u', ' ', $title));
        if ($title === '') {
            throw new Exception(__('You must say what there is to do.'));
        }

        return mb_substr($title, 0, self::TITLE_LENGTH);
    }

    /**
     * Check a status.
     */
    public static function isStatus(int $status): bool
    {
        return in_array($status, [self::STATUS_TODO, self::STATUS_DONE], true);
    }

    /**
     * Get the lists of current blog, with their numbers of todos.
     *
     * @return  array<int, array{name: string, todo: int, done: int}>
     */
    public static function getLists(): array
    {
        $sql = new SelectStatement();
        $rs  = $sql
            ->columns([
                'todo_list',
                'todo_status',
                $sql->count('todo_id', 'nb_todo'),
            ])
            ->from(self::table())
            ->where('blog_id = ' . $sql->quote(App::blog()->id()))
            ->group(['todo_list', 'todo_status'])
            ->order('todo_list ASC')
            ->select();

        $lists = [];
        if ($rs instanceof MetaRecord) {
            while ($rs->fetch()) {
                $name = self::str($rs, 'todo_list');
                // Prefixed key: a list name can be a number, which PHP would turn into an integer key
                $key = 'list_' . $name;
                $lists[$key] ??= ['name' => $name, 'todo' => 0, 'done' => 0];
                $lists[$key][$rs->intField('todo_status') === self::STATUS_DONE ? 'done' : 'todo'] += $rs->intField('nb_todo');
            }
        }

        return array_values($lists);
    }

    /**
     * Get the lists names of current blog.
     *
     * @return  array<int, string>
     */
    public static function getListsNames(): array
    {
        return array_map(fn (array $list): string => $list['name'], self::getLists());
    }

    /**
     * Count todos of current blog.
     *
     * @param   string      $list       The list name, empty for all lists
     * @param   null|int    $status     The status, null for all
     * @param   string      $query      Some text to find in title or details, whatever the case and the accents
     */
    public static function count(string $list = '', ?int $status = null, string $query = ''): int
    {
        if (trim($query) !== '') {
            return count(self::match(self::search($list, $status), $query));
        }

        $sql = new SelectStatement();
        $sql
            ->column($sql->count('todo_id'))
            ->from(self::table());

        self::filter($sql, $list, $status);

        $rs = $sql->select();

        return $rs instanceof MetaRecord ? $rs->cardinal() : 0;
    }

    /**
     * Search todos of current blog.
     *
     * Without sort field, todos to do come first, then the last changed ones.
     *
     * @param   string      $list       The list name, empty for all lists
     * @param   null|int    $status     The status, null for all
     * @param   string      $query      Some text to find in title or details, whatever the case and the accents
     * @param   int         $limit      The maximum number of todos, 0 for all
     * @param   int         $offset     The number of todos to skip
     * @param   string      $sortby     The sort field, one of SORTS
     * @param   bool        $desc       The sort order is descending
     *
     * @return  array<int, Todo>
     */
    public static function search(string $list = '', ?int $status = null, string $query = '', int $limit = 0, int $offset = 0, string $sortby = '', bool $desc = true): array
    {
        if (trim($query) !== '') {
            // The text is not searched by the database: case, accents and escaping of LIKE differ from one database to another
            $todos = self::match(self::search($list, $status, '', 0, 0, $sortby, $desc), $query);

            return $limit > 0 ? array_slice($todos, max(0, $offset), $limit) : $todos;
        }

        $sql = new SelectStatement();
        $sql
            ->columns([
                'todo_id',
                'user_id',
                'todo_list',
                'todo_title',
                'todo_note',
                'todo_status',
                'todo_creadt',
                'todo_upddt',
            ])
            ->from(self::table());

        if (in_array($sortby, self::SORTS, true)) {
            $order = $desc ? ' DESC' : ' ASC';
            // Texts are sorted whatever their case and accents
            $field = in_array($sortby, ['todo_title', 'todo_list'], true) ? App::db()->con()->lexFields($sortby) : $sortby;
            $sql->order([$field . $order, 'todo_id' . $order]);
        } else {
            $sql->order(['todo_status ASC', 'todo_upddt DESC', 'todo_id DESC']);
        }

        self::filter($sql, $list, $status);

        if ($limit > 0) {
            $sql->limit([max(0, $offset), $limit]);
        }

        $rs    = $sql->select();
        $todos = [];
        if ($rs instanceof MetaRecord) {
            while ($rs->fetch()) {
                $todos[] = self::fromRecord($rs);
            }
        }

        return $todos;
    }

    /**
     * Get a todo of current blog.
     */
    public static function get(int $id): ?Todo
    {
        $sql = new SelectStatement();
        $rs  = $sql
            ->columns([
                'todo_id',
                'user_id',
                'todo_list',
                'todo_title',
                'todo_note',
                'todo_status',
                'todo_creadt',
                'todo_upddt',
            ])
            ->from(self::table())
            ->where('blog_id = ' . $sql->quote(App::blog()->id()))
            ->and('todo_id = ' . $id)
            ->select();

        return $rs instanceof MetaRecord && $rs->fetch() ? self::fromRecord($rs) : null;
    }

    /**
     * Add a todo to current blog, in the name of current user.
     *
     * @param   string  $title  The todo, in one line
     * @param   string  $list   The list name, empty for the default list
     * @param   string  $note   The details
     *
     * @throws  Exception   If title or list name are wrong
     */
    public static function add(string $title, string $list = '', string $note = ''): Todo
    {
        $title = self::cleanTitle($title);
        $list  = self::cleanList($list);
        $note  = trim($note);
        $date  = date('Y-m-d H:i:s');

        $cur = App::db()->con()->openCursor(self::table());
        $cur->setStrField('blog_id', App::blog()->id());
        $cur->setStrField('user_id', App::auth()->userID());
        $cur->setStrField('todo_list', $list);
        $cur->setStrField('todo_title', $title);
        $cur->setStrField('todo_note', $note === '' ? null : $note);
        $cur->setIntField('todo_status', self::STATUS_TODO);
        $cur->setStrField('todo_creadt', $date);
        $cur->setStrField('todo_upddt', $date);

        App::db()->con()->writeLock(self::table());

        try {
            $sql = new SelectStatement();
            $rs  = $sql
                ->column($sql->max('todo_id'))
                ->from(self::table())
                ->select();

            $id = $rs instanceof MetaRecord ? $rs->cardinal() + 1 : 1;

            $cur->setIntField('todo_id', $id);
            $cur->insert();

            App::db()->con()->unlock();
        } catch (Throwable $exception) {
            App::db()->con()->unlock();

            throw $exception;
        }

        return new Todo(
            id:      $id,
            list:    $list,
            title:   $title,
            note:    $note,
            status:  self::STATUS_TODO,
            user:    App::auth()->userID(),
            created: $date,
            updated: $date,
        );
    }

    /**
     * Update a todo of current blog.
     *
     * Only given values are changed.
     * The update date only follows title, details and status: moving a todo to another list keeps the date it was done.
     *
     * @param   int             $id         The todo ID
     * @param   null|string     $title      The todo, in one line
     * @param   null|string     $note       The details
     * @param   null|string     $list       The list name
     * @param   null|int        $status     The status
     *
     * @throws  Exception   If todo does not exist or values are wrong
     */
    public static function update(int $id, ?string $title = null, ?string $note = null, ?string $list = null, ?int $status = null): Todo
    {
        $todo = self::get($id);
        if (!$todo instanceof Todo) {
            throw new Exception(__('This todo does not exist.'));
        }

        $title  = is_null($title) ? $todo->title : self::cleanTitle($title);
        $note   = is_null($note) ? $todo->note : trim($note);
        $list   = is_null($list) ? $todo->list : self::cleanList($list);
        $status = is_null($status) ? $todo->status : $status;

        if (!self::isStatus($status)) {
            throw new Exception(__('Unknown status.'));
        }

        $changed = $title !== $todo->title || $note !== $todo->note || $status !== $todo->status;
        if (!$changed && $list === $todo->list) {
            return $todo;
        }

        $date = $changed ? date('Y-m-d H:i:s') : $todo->updated;

        $cur = App::db()->con()->openCursor(self::table());
        $cur->setStrField('todo_list', $list);
        $cur->setStrField('todo_title', $title);
        $cur->setStrField('todo_note', $note === '' ? null : $note);
        $cur->setIntField('todo_status', $status);
        $cur->setStrField('todo_upddt', $date);

        $sql = new UpdateStatement();
        $sql
            ->where('blog_id = ' . $sql->quote(App::blog()->id()))
            ->and('todo_id = ' . $todo->id)
            ->update($cur);

        return new Todo(
            id:      $todo->id,
            list:    $list,
            title:   $title,
            note:    $note,
            status:  $status,
            user:    $todo->user,
            created: $todo->created,
            updated: $date,
        );
    }

    /**
     * Set the status of some todos of current blog.
     *
     * @param   array<int, int>     $ids        The todos IDs
     * @param   int                 $status     The status
     *
     * @throws  Exception   If status is wrong
     */
    public static function setStatus(array $ids, int $status): void
    {
        if (!self::isStatus($status)) {
            throw new Exception(__('Unknown status.'));
        }

        if ($ids === []) {
            return;
        }

        // Only todos with another status: the update date of the others must not change
        $sql = new UpdateStatement();
        $sql
            ->ref(self::table())
            ->set('todo_status = ' . $status)
            ->set('todo_upddt = ' . $sql->quote(date('Y-m-d H:i:s')))
            ->where('blog_id = ' . $sql->quote(App::blog()->id()))
            ->and('todo_id' . $sql->in(array_values($ids), 'int'))
            ->and('todo_status <> ' . $status)
            ->update();
    }

    /**
     * Move some todos of current blog to a list.
     *
     * @param   array<int, int>     $ids    The todos IDs
     * @param   string              $list   The list name
     *
     * @throws  Exception   If list name is wrong
     */
    public static function move(array $ids, string $list): void
    {
        $list = self::cleanList($list);

        if ($ids === []) {
            return;
        }

        $sql = new UpdateStatement();
        $sql
            ->ref(self::table())
            ->set('todo_list = ' . $sql->quote($list))
            ->where('blog_id = ' . $sql->quote(App::blog()->id()))
            ->and('todo_id' . $sql->in(array_values($ids), 'int'))
            ->update();
    }

    /**
     * Delete some todos of current blog.
     *
     * @param   array<int, int>     $ids    The todos IDs
     */
    public static function delete(array $ids): void
    {
        if ($ids === []) {
            return;
        }

        $sql = new DeleteStatement();
        $sql
            ->from(self::table())
            ->where('blog_id = ' . $sql->quote(App::blog()->id()))
            ->and('todo_id' . $sql->in(array_values($ids), 'int'))
            ->delete();
    }

    /**
     * Rename a list of current blog.
     *
     * If the new name is the one of another list, both lists are merged.
     *
     * @param   string  $from   The current list name
     * @param   string  $to     The new list name
     *
     * @throws  Exception   If a list name is wrong or the list does not exist
     */
    public static function renameList(string $from, string $to): void
    {
        $from = self::cleanList($from);
        $to   = self::cleanList($to);

        if (!in_array($from, self::getListsNames(), true)) {
            throw new Exception(__('This list does not exist.'));
        }

        if ($from === $to) {
            return;
        }

        $sql = new UpdateStatement();
        $sql
            ->ref(self::table())
            ->set('todo_list = ' . $sql->quote($to))
            ->where('blog_id = ' . $sql->quote(App::blog()->id()))
            ->and('todo_list = ' . $sql->quote($from))
            ->update();
    }

    /**
     * Add search conditions to a query.
     *
     * @param   SelectStatement     $sql        The query
     * @param   string              $list       The list name, empty for all lists
     * @param   null|int            $status     The status, null for all
     */
    private static function filter(SelectStatement $sql, string $list, ?int $status): void
    {
        $sql->where('blog_id = ' . $sql->quote(App::blog()->id()));

        $list = mb_strtolower(trim($list));
        if ($list !== '') {
            $sql->and('todo_list = ' . $sql->quote($list));
        }

        if (!is_null($status)) {
            $sql->and('todo_status = ' . $status);
        }
    }

    /**
     * Keep the todos having some text in their title or details.
     *
     * @param   array<int, Todo>    $todos  The todos
     * @param   string              $query  The text to find
     *
     * @return  array<int, Todo>
     */
    private static function match(array $todos, string $query): array
    {
        $query = self::normalize($query);

        return array_values(array_filter(
            $todos,
            fn (Todo $todo): bool => str_contains(self::normalize($todo->title . "\n" . $todo->note), $query)
        ));
    }

    /**
     * Get a text ready to be compared: lower case, without accents.
     */
    private static function normalize(string $text): string
    {
        return mb_strtolower(Text::deaccent(trim($text)));
    }

    /**
     * Get a todo from current row of a record.
     */
    private static function fromRecord(MetaRecord $rs): Todo
    {
        return new Todo(
            id:      $rs->intField('todo_id'),
            list:    self::str($rs, 'todo_list'),
            title:   self::str($rs, 'todo_title'),
            note:    self::str($rs, 'todo_note'),
            status:  $rs->intField('todo_status'),
            user:    self::str($rs, 'user_id'),
            created: self::str($rs, 'todo_creadt'),
            updated: self::str($rs, 'todo_upddt'),
        );
    }

    /**
     * Get a field as a string, even if the database gives a number (a title or a list name made of digits).
     */
    private static function str(MetaRecord $rs, string $field): string
    {
        $value = $rs->field($field);

        return is_scalar($value) ? (string) $value : '';
    }
}
