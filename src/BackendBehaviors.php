<?php

declare(strict_types=1);

namespace Dotclear\Plugin\PenseBete;

use ArrayObject;
use Dotclear\App;
use Dotclear\Core\Backend\Favorites;
use Dotclear\Core\Backend\UserPrefFilter;

/**
 * @brief       PenseBete backend behaviors.
 * @ingroup     PenseBete
 *
 * Everything here is only registered for users having the permission of the plugin on the blog,
 * see Backend.
 *
 * @author      Jean-Christian Paul Denis
 * @copyright   AGPL-3.0
 */
class BackendBehaviors
{
    /**
     * Display options of the todos list (sort and number per page), also set from user preferences.
     *
     * @param   ArrayObject<int, mixed>     $filters    The lists options
     */
    public static function adminFiltersListsV3(ArrayObject $filters): string
    {
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
    }

    /**
     * Favorite: users choose to put it in their menu and on their dashboard from their preferences.
     */
    public static function adminDashboardFavoritesV2(Favorites $favs): string
    {
        $favs->register(My::id(), [
            'title'          => My::name(),
            'url'            => My::manageUrl(),
            'permissions'    => App::auth()->makePermissions([My::id()]),
            'active_cb'      => self::adminDashboardFavoritesActive(...),
            'dashboard_cb'   => self::adminDashboardFavoritesCallback(...),
            'menu-icon'      => My::icon(),
            'dashboard-icon' => My::icon(),
        ]);

        return '';
    }

    /**
     * Is a page of the plugin displayed.
     *
     * @param   string                  $request    The request
     * @param   array<string, mixed>    $params     The parameters
     */
    public static function adminDashboardFavoritesActive(string $request, array $params): bool
    {
        return isset($params['p']) && $params['p'] === My::id();
    }

    /**
     * The dashboard icon tells how many todos are left to do.
     *
     * @param   ArrayObject<string, mixed>  $icon   The icon
     */
    public static function adminDashboardFavoritesCallback(ArrayObject $icon): void
    {
        $count = Todos::count('', Todos::STATUS_TODO);
        if ($count > 0) {
            $icon['title'] = sprintf(__('One todo to do', '%d todos to do', $count), $count);
        }
    }
}
