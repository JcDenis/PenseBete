<?php

declare(strict_types=1);

namespace Dotclear\Plugin\PenseBete;

use Dotclear\App;
use Dotclear\Helper\Date;
use Dotclear\Helper\Html\Form\Checkbox;
use Dotclear\Helper\Html\Form\Form;
use Dotclear\Helper\Html\Form\Input;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Link;
use Dotclear\Helper\Html\Form\Note;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Html\Form\Textarea;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Process\TraitProcess;
use Exception;

/**
 * @brief       PenseBete todo edition.
 * @ingroup     PenseBete
 *
 * This page is called by the manage page.
 *
 * @author      Jean-Christian Paul Denis
 * @copyright   AGPL-3.0
 */
class ManageTodo
{
    use TraitProcess;

    private static ManageVars $vars;

    private static ?Todo $todo = null;

    public static function init(): bool
    {
        if (self::status(My::checkContext(My::MANAGE))) {
            self::$vars = new ManageVars();
            self::$todo = Todos::get(self::$vars->id);
        }

        return self::status();
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        if (!self::$todo instanceof Todo) {
            return true;
        }

        try {
            if (isset($_POST['save'])) {
                Todos::update(
                    self::$todo->id,
                    ManageVars::post('todo_title'),
                    ManageVars::post('todo_note'),
                    ManageVars::post('todo_list'),
                    isset($_POST['todo_done']) ? Todos::STATUS_DONE : Todos::STATUS_TODO
                );

                App::backend()->notices()->addSuccessNotice(__('Todo successfully updated.'));
                My::redirect(self::$vars->params());
            }

            if (isset($_POST['delete'])) {
                Todos::delete([self::$todo->id]);

                App::backend()->notices()->addSuccessNotice(__('Todo successfully deleted.'));
                My::redirect(self::$vars->params());
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

        $back = My::manageUrl(self::$vars->params());

        App::backend()->page()->openModule(My::name(), Manage::head());

        echo
        App::backend()->page()->breadcrumb([
            Html::escapeHTML(App::blog()->name()) => '',
            My::name()                            => $back,
            __('Edit a todo')                     => '',
        ]) .
        App::backend()->notices()->getNotices();

        if (self::$todo instanceof Todo) {
            echo
            Manage::datalist(Todos::getLists())->render() .
            self::todoForm(self::$todo)->render();
        } else {
            echo (new Note())
                ->class('error')
                ->text(__('This todo does not exist.'))
                ->render();
        }

        echo (new Para())
            ->items([
                (new Link())
                    ->class('back')
                    ->href($back)
                    ->text(__('Back to the todos')),
            ])
            ->render();

        App::backend()->page()->helpBlock(My::id());

        App::backend()->page()->closeModule();
    }

    private static function todoForm(Todo $todo): Form
    {
        // After an error, the form keeps what has been typed
        $posted  = isset($_POST['save']);
        $user_tz = is_string($user_tz = App::auth()->getInfo('user_tz')) ? $user_tz : 'UTC';

        return (new Form('todo-form'))
            ->method('post')
            ->action(My::manageUrl([... self::$vars->params(), 'id' => $todo->id]))
            ->fields([
                (new Para())
                    ->items([
                        (new Input('todo_title'))
                            ->class('maximal')
                            ->maxlength(Todos::TITLE_LENGTH)
                            ->required(true)
                            ->value(Html::escapeHTML($posted ? ManageVars::post('todo_title') : $todo->title))
                            ->label((new Label(__('To do:'), Label::OL_TF))),
                    ]),
                (new Para())
                    ->items([
                        Manage::listInput('todo_list', $posted ? ManageVars::post('todo_list') : $todo->list)
                            ->label((new Label(__('List:'), Label::OL_TF))),
                    ]),
                (new Para())
                    ->items([
                        (new Textarea('todo_note', Html::escapeHTML($posted ? ManageVars::post('todo_note') : $todo->note)))
                            ->class('maximal')
                            ->cols(60)
                            ->rows(8)
                            ->label((new Label(__('Details:'), Label::OL_TF))),
                    ]),
                (new Para())
                    ->items([
                        (new Checkbox('todo_done', $posted ? isset($_POST['todo_done']) : $todo->isDone()))
                            ->value('1')
                            ->label((new Label(__('Done'), Label::IL_FT))),
                    ]),
                (new Note())
                    ->class('form-note')
                    ->text(sprintf(
                        __('Added by %1$s on %2$s, last change on %3$s.'),
                        Html::escapeHTML($todo->user),
                        Html::escapeHTML(Date::dt2str(__('%Y-%m-%d %H:%M'), $todo->created, $user_tz)),
                        Html::escapeHTML(Date::dt2str(__('%Y-%m-%d %H:%M'), $todo->updated, $user_tz))
                    )),
                (new Para())
                    ->class('form-buttons')
                    ->items([
                        (new Submit('save', __('Save'))),
                        (new Submit('delete', __('Delete')))
                            ->class('delete'),
                        ... My::hiddenFields(),
                    ]),
            ]);
    }
}
