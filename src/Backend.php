<?php

declare(strict_types=1);

namespace Dotclear\Plugin\PenseBete;

use ArrayObject;
use Dotclear\App;
use Dotclear\Core\Backend\Menus;
use Dotclear\Core\Backend\UserPrefFilter;
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

        // Display options of the todos list (sort and number per page), also set from user preferences
        App::behavior()->addBehavior('adminFiltersListsV3', function (ArrayObject $filters): string {
            $filters->append(new UserPrefFilter(
                My::id(),
                My::name(),
                ManageVars::sortbyCombo(),
                ManageVars::SORTBY,
                'desc',
                __('todos per page'),
                ManageVars::PER_PAGE
            ));

            return '';
        });

        return true;
    }
}
