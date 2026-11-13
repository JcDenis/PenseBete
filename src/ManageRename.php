<?php

declare(strict_types=1);

namespace Dotclear\Plugin\PenseBete;

use Dotclear\App;
use Dotclear\Helper\Html\Form\Form;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Link;
use Dotclear\Helper\Html\Form\Note;
use Dotclear\Helper\Html\Form\Option;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Select;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Process\TraitProcess;
use Exception;

/**
 * @brief       PenseBete list renaming.
 * @ingroup     PenseBete
 *
 * Rename a list, or merge it with another one. This page is called by the manage page.
 *
 * @author      Jean-Christian Paul Denis
 * @copyright   AGPL-3.0
 */
class ManageRename
{
    use TraitProcess;

    private static ManageVars $vars;

    public static function init(): bool
    {
        if (self::status(My::checkContext(My::MANAGE))) {
            self::$vars = Manage::vars();
        }

        return self::status();
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        try {
            if (isset($_POST['rename'])) {
                if (ManageVars::post('list_to') === '') {
                    throw new Exception(__('You must give the new name of the list.'));
                }

                $from = Todos::cleanList(ManageVars::post('list_from'));
                $to   = Todos::cleanList(ManageVars::post('list_to'));

                Todos::renameList($from, $to);

                // Follow the renamed list
                $params = self::$vars->params();
                if (self::$vars->list === $from) {
                    $params['list'] = $to;
                }

                App::backend()->notices()->addSuccessNotice(sprintf(__('List "%1$s" renamed to "%2$s".'), Html::escapeHTML($from), Html::escapeHTML($to)));
                My::redirect($params);
            }
        } catch (Exception $exception) {
            App::error()->add($exception->getMessage());
        }

        return true;
    }

    public static function render(): void
    {
        if (!self::status()) {
            return;
        }

        $back  = My::manageUrl(self::$vars->params());
        $lists = Todos::getLists();

        App::backend()->page()->openModule(My::name(), Manage::head());

        echo
        App::backend()->page()->breadcrumb([
            Html::escapeHTML(App::blog()->name()) => '',
            My::name()                            => $back,
            __('Rename a list')                   => '',
        ]) .
        App::backend()->notices()->getNotices();

        if ($lists === []) {
            echo (new Note())
                ->class('info')
                ->text(__('There is no list yet.'))
                ->render();
        } else {
            echo
            Manage::datalist($lists)->render() .
            self::renameForm($lists)->render();
        }

        echo (new Para())
            ->items([
                (new Link())
                    ->class('back')
                    ->href($back)
                    ->text(__('Back to the todos')),
            ])
            ->render();

        App::backend()->page()->helpBlock(My::id() . '-rename');

        App::backend()->page()->closeModule();
    }

    /**
     * @param   array<int, array{name: string, todo: int, done: int}>   $lists  The lists
     */
    private static function renameForm(array $lists): Form
    {
        // After an error, the form keeps what has been typed. Else the list currently shown is proposed.
        $posted = isset($_POST['rename']);

        return (new Form('list-rename-form'))
            ->method('post')
            ->action(My::manageUrl([... self::$vars->params(), 'rename' => 1]))
            ->fields([
                (new Para())
                    ->items([
                        (new Select('list_from'))
                            ->items(array_map(
                                fn (array $list): Option => new Option(
                                    Html::escapeHTML(sprintf('%s (%s)', $list['name'], $list['todo'] + $list['done'])),
                                    Html::escapeHTML($list['name'])
                                ),
                                $lists
                            ))
                            ->default(Html::escapeHTML($posted ? ManageVars::post('list_from') : self::$vars->list))
                            ->label((new Label(__('List:'), Label::OL_TF))),
                    ]),
                (new Para())
                    ->items([
                        Manage::listInput('list_to', $posted ? ManageVars::post('list_to') : '')
                            ->required(true)
                            ->label((new Label(__('New name:'), Label::OL_TF))),
                    ]),
                (new Note())
                    ->class('form-note')
                    ->text(__('All the todos of the list follow. With the name of another list, both lists are merged.')),
                (new Para())
                    ->class('form-buttons')
                    ->items([
                        (new Submit('rename', __('Rename'))),
                        ... My::hiddenFields(),
                    ]),
            ]);
    }
}
