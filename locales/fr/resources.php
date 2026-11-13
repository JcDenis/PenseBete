<?php

/**
 * @file
 * @brief       The plugin PenseBete help resource
 * @ingroup     PenseBete
 *
 * @author      Jean-Christian Paul Denis
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

\Dotclear\App::backend()->resources()
    ->set('help', 'PenseBete', __DIR__ . '/help/help.html')
    ->set('help', 'PenseBete-todo', __DIR__ . '/help/todo.html')
    ->set('help', 'PenseBete-rename', __DIR__ . '/help/rename.html');
