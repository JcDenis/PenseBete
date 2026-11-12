<?php

declare(strict_types=1);

namespace Dotclear\Plugin\PenseBete;

use Dotclear\App;
use Dotclear\Helper\Process\TraitProcess;
use Exception;

/**
 * @brief       PenseBete module install.
 * @ingroup     PenseBete
 *
 * @author      Jean-Christian Paul Denis
 * @copyright   AGPL-3.0
 */
class Install
{
    use TraitProcess;

    public static function init(): bool
    {
        return self::status(My::checkContext(My::INSTALL));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        try {
            $struct = App::db()->structure();

            $struct->table(Todos::TABLE_NAME)
                ->field('todo_id', 'bigint', 0, false)
                ->field('blog_id', 'varchar', 32, false)
                ->field('user_id', 'varchar', 32, false, "''")
                ->field('todo_list', 'varchar', Todos::LIST_LENGTH, false, "'" . Todos::DEFAULT_LIST . "'")
                ->field('todo_title', 'varchar', Todos::TITLE_LENGTH, false)
                ->field('todo_note', 'text', 0, true)
                ->field('todo_status', 'smallint', 0, false, Todos::STATUS_TODO)
                ->field('todo_creadt', 'timestamp', 0, false, 'now()')
                ->field('todo_upddt', 'timestamp', 0, false, 'now()')

                ->primary('pk_pensebete', 'todo_id')
                ->index('idx_pensebete_blog_list', 'btree', 'blog_id', 'todo_list')
                ->index('idx_pensebete_blog_status', 'btree', 'blog_id', 'todo_status')
                // Todos of a blog are deleted with the blog.
                // No reference to user: todos are shared by the users of the blog, they stay when their creator is deleted.
                ->reference('fk_pensebete_blog', 'blog_id', 'blog', 'blog_id', 'cascade', 'cascade')
            ;

            App::db()->structure()->synchronize($struct);

            return true;
        } catch (Exception $exception) {
            App::error()->add($exception->getMessage());

            return false;
        }
    }
}
