<?php

declare(strict_types=1);

namespace Dotclear\Plugin\PenseBete;

use ArrayObject;
use Dotclear\App;
use Dotclear\Core\Backend\Favorites;
use Dotclear\Core\Backend\UserPrefFilter;
use Dotclear\Helper\Html\Form\Checkbox;
use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\Fieldset;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Legend;
use Dotclear\Helper\Html\Form\Li;
use Dotclear\Helper\Html\Form\Link;
use Dotclear\Helper\Html\Form\Number;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Form\Ul;
use Dotclear\Helper\Html\Html;

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
     * User preference: display the last todos on dashboard.
     *
     * @var     string  PREF_DASHBOARD_ITEM
     */
    public const PREF_DASHBOARD_ITEM = 'dashboard_item';

    /**
     * User preference: number of todos displayed on dashboard.
     *
     * @var     string  PREF_DASHBOARD_NB
     */
    public const PREF_DASHBOARD_NB = 'dashboard_nb';

    /**
     * Default number of todos displayed on dashboard.
     *
     * @var     int     DASHBOARD_NB
     */
    public const DASHBOARD_NB = 5;

    /**
     * Maximum number of todos displayed on dashboard.
     *
     * @var     int     DASHBOARD_MAX
     */
    public const DASHBOARD_MAX = 50;

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

    /**
     * Dashboard: the last todos added and still to do.
     *
     * @param   ArrayObject<int, mixed>     $contents   The contents
     */
    public static function adminDashboardContentsV2(ArrayObject $contents): string
    {
        if (!My::prefs()->getBool(self::PREF_DASHBOARD_ITEM, false)) {
            return '';
        }

        $todos = Todos::search('', Todos::STATUS_TODO, '', self::dashboardNb(), 0, 'todo_creadt', true);
        if ($todos === []) {
            // Nothing to do, nothing to say
            return '';
        }

        $lines = [];
        foreach ($todos as $todo) {
            $link = (new Link())
                ->href(My::manageUrl(['id' => $todo->id]))
                ->text(Html::escapeHTML($todo->title));
            if ($todo->note !== '') {
                $link->title(Html::escapeHTML($todo->note));
            }

            $lines[] = (new Li())
                ->separator(' ')
                ->items([
                    $link,
                    (new Text(null, '(' . Html::escapeHTML($todo->list) . ')')),
                ]);
        }

        $count = Todos::count('', Todos::STATUS_TODO);

        $contents->append(new ArrayObject([
            (new Div(My::id() . '-dashboard'))
                ->class(['box', 'small'])
                ->items([
                    (new Text('h3', App::backend()->helper()->adminIcon(My::icons(), true, '', '', 'icon-small') . ' ' . My::name())),
                    (new Ul())
                        ->items($lines),
                    (new Para())
                        ->items([
                            (new Link())
                                ->href(My::manageUrl())
                                ->text(sprintf(__('One todo to do', '%d todos to do', $count), $count)),
                        ]),
                ])
            ->render(),
        ]));

        return '';
    }

    /**
     * User preferences: options of the dashboard.
     */
    public static function adminDashboardOptionsFormV2(): string
    {
        echo (new Fieldset())
            ->legend(new Legend(My::name()))
            ->fields([
                (new Para())
                    ->items([
                        (new Checkbox(My::id() . '_' . self::PREF_DASHBOARD_ITEM, (bool) My::prefs()->getBool(self::PREF_DASHBOARD_ITEM, false)))
                            ->value(1)
                            ->label(new Label(__('Display the last todos to do on dashboard'), Label::IL_FT)),
                    ]),
                (new Para())
                    ->items([
                        (new Number(My::id() . '_' . self::PREF_DASHBOARD_NB, 1, self::DASHBOARD_MAX, self::dashboardNb()))
                            ->label(new Label(__('Number of todos to display:'), Label::IL_TF)),
                    ]),
            ])
        ->render();

        return '';
    }

    /**
     * User preferences: save the options of the dashboard.
     *
     * @param   string  $user_id    The user identifier
     */
    public static function adminAfterDashboardOptionsUpdate(?string $user_id = null): string
    {
        if (is_null($user_id)) {
            return '';
        }

        $nb = $_POST[My::id() . '_' . self::PREF_DASHBOARD_NB] ?? null;
        $nb = is_numeric($nb) ? (int) $nb : self::DASHBOARD_NB;

        My::prefs()->put(self::PREF_DASHBOARD_ITEM, !empty($_POST[My::id() . '_' . self::PREF_DASHBOARD_ITEM]), App::userWorkspace()::WS_BOOL);
        My::prefs()->put(self::PREF_DASHBOARD_NB, min(max($nb, 1), self::DASHBOARD_MAX), App::userWorkspace()::WS_INT);

        return '';
    }

    /**
     * Get the number of todos the user wants on dashboard.
     */
    private static function dashboardNb(): int
    {
        $nb = (int) My::prefs()->getInt(self::PREF_DASHBOARD_NB, false);

        return $nb < 1 ? self::DASHBOARD_NB : min($nb, self::DASHBOARD_MAX);
    }
}
