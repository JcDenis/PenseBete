<?php

declare(strict_types=1);

namespace Dotclear\Plugin\PenseBete;

use ArrayObject;
use Dotclear\App;
use Dotclear\Helper\Process\TraitProcess;

/**
 * @brief       PenseBete frontend class.
 * @ingroup     PenseBete
 *
 * Nothing is shown on the public side of the blog: this class only gives the tools of the plugin
 * to the plugin ClaudetteMcp, whose endpoint runs in frontend context.
 *
 * @author      Jean-Christian Paul Denis
 * @copyright   AGPL-3.0
 */
class Frontend
{
    use TraitProcess;

    public static function init(): bool
    {
        return self::status(My::checkContext(My::FRONTEND));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        // Tools for AI assistants connected to the blog with the plugin ClaudetteMcp
        App::behavior()->addBehavior('ClaudetteMcpAddTool', function (ArrayObject $tools): void {
            $tools->append(new ClaudetteMcp\ListTodos());
            $tools->append(new ClaudetteMcp\AddTodo());
            $tools->append(new ClaudetteMcp\UpdateTodo());
        });

        return true;
    }
}
