<?php

declare(strict_types=1);

namespace Dotclear\Plugin\PenseBete;

/**
 * @brief       PenseBete todo descriptor.
 * @ingroup     PenseBete
 *
 * @author      Jean-Christian Paul Denis
 * @copyright   AGPL-3.0
 */
class Todo
{
    /**
     * @param   int     $id         The todo ID
     * @param   string  $list       The list name
     * @param   string  $title      The todo, in one line
     * @param   string  $note       The details
     * @param   int     $status     The status
     * @param   string  $user       The ID of the user who created the todo
     * @param   string  $created    The creation date
     * @param   string  $updated    The date of last change of title, details or status
     */
    public function __construct(
        public readonly int $id,
        public readonly string $list,
        public readonly string $title,
        public readonly string $note,
        public readonly int $status,
        public readonly string $user,
        public readonly string $created,
        public readonly string $updated,
    ) {
    }

    public function isDone(): bool
    {
        return $this->status === Todos::STATUS_DONE;
    }
}
