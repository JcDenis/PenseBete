<?php

declare(strict_types=1);

namespace Dotclear\Plugin\PenseBete;

use Dotclear\App;
use Dotclear\Module\MyPlugin;

/**
 * @brief       PenseBete module definition.
 * @ingroup     PenseBete
 *
 * @author      Jean-Christian Paul Denis
 * @copyright   AGPL-3.0
 */
class My extends MyPlugin
{
    public static function checkCustomContext(int $context): ?bool
    {
        return match ($context) {
            // Users with the permission of the plugin (or blog administrators) manage the lists of the blog
            self::BACKEND, self::MENU, self::MANAGE => App::task()->checkContext('BACKEND')
                && App::blog()->isDefined()
                && self::isAllowed(),

            default => null,
        };
    }

    /**
     * Check if current user can manage the lists of current blog.
     */
    public static function isAllowed(): bool
    {
        return App::auth()->check(App::auth()->makePermissions([self::id()]), App::blog()->id());
    }
}
