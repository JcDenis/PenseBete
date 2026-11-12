<?php

declare(strict_types=1);

namespace Dotclear\Plugin\PenseBete;

use Dotclear\Core\Backend\Menus;
use Dotclear\Helper\Process\TraitProcess;

/**
 * @brief       PenseBete backend class.
 * @ingroup     PenseBete
 *
 * @author      Jean-Christian Paul Denis
 * @copyright   AGPL-3.0
 */
class Backend
{
    use TraitProcess;

    public static function init(): bool
    {
        return self::status(My::checkContext(My::BACKEND));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        My::addBackendMenuItem(Menus::MENU_BLOG);

        return true;
    }
}
