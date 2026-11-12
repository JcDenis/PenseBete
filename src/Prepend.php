<?php

declare(strict_types=1);

namespace Dotclear\Plugin\PenseBete;

use Dotclear\App;
use Dotclear\Helper\Process\TraitProcess;

/**
 * @brief       PenseBete module prepend.
 * @ingroup     PenseBete
 *
 * @author      Jean-Christian Paul Denis
 * @copyright   AGPL-3.0
 */
class Prepend
{
    use TraitProcess;

    public static function init(): bool
    {
        return self::status(My::checkContext(My::PREPEND));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        // User permission to manage the lists of a blog
        App::auth()->setPermissionType(
            My::id(),
            __('manage todo lists')
        );

        return true;
    }
}
