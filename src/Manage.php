<?php

declare(strict_types=1);

namespace Dotclear\Plugin\PenseBete;

use Dotclear\App;
use Dotclear\Core\Backend\Listing\Pager;
use Dotclear\Helper\Date;
use Dotclear\Helper\Html\Form\Caption;
use Dotclear\Helper\Html\Form\Checkbox;
use Dotclear\Helper\Html\Form\Datalist;
use Dotclear\Helper\Html\Form\Details;
use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\Form;
use Dotclear\Helper\Html\Form\Img;
use Dotclear\Helper\Html\Form\Input;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Link;
use Dotclear\Helper\Html\Form\Note;
use Dotclear\Helper\Html\Form\Option;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Select;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Html\Form\Summary;
use Dotclear\Helper\Html\Form\Table;
use Dotclear\Helper\Html\Form\Tbody;
use Dotclear\Helper\Html\Form\Td;
use Dotclear\Helper\Html\Form\Th;
use Dotclear\Helper\Html\Form\Thead;
use Dotclear\Helper\Html\Form\Tr;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Process\TraitProcess;
use Exception;

/**
 * @brief       PenseBete todos management.
 * @ingroup     PenseBete
 *
 * Todos of current blog, for all users having the permission of the plugin.
 *
 * @author      Jean-Christian Paul Denis
 * @copyright   AGPL-3.0
 */
class Manage
{
    use TraitProcess;

    /**
     * The ID of the lists names suggestions.
     *
     * @var     string  DATALIST
     */
    public const DATALIST = 'pensebete-lists';

    /**
     * The ID of the form used by the buttons of the todos lines.
     *
     * @var     string  QUICK_FORM
     */
    private const QUICK_FORM = 'todos-quick-form';

    private static ManageVars $vars;

    /**
     * Show the form of a todo rather than the list.
     */
    private static bool $edit = false;

    public static function init(): bool
    {
        if (self::status(My::checkContext(My::MANAGE))) {
            self::$vars = new ManageVars();
            self::$edit = (self::$vars->id > 0 || self::$vars->new) && ManageTodo::init();
        }

        return self::status();
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        if (self::$edit) {
            return ManageTodo::process();
        }

        $vars = self::$vars;

        try {
            // Change the status of one todo, from the button of its line
            foreach (['done' => Todos::STATUS_DONE, 'undone' => Todos::STATUS_TODO] as $name => $status) {
                if (isset($_POST[$name]) && is_array($_POST[$name])) {
                    $id = array_key_first($_POST[$name]);
                    if (is_int($id) && $id > 0) {
                        Todos::setStatus([$id], $status);

                        App::backend()->notices()->addSuccessNotice($status === Todos::STATUS_DONE ? __('Todo marked as done.') : __('Todo marked as to do.'));
                    }
                    My::redirect($vars->params());
                }
            }

            // Selected todos action
            if (isset($_POST['do-action'])) {
                $ids = [];
                if (isset($_POST['todos']) && is_array($_POST['todos'])) {
                    foreach ($_POST['todos'] as $id) {
                        if (is_numeric($id) && (int) $id > 0) {
                            $ids[] = (int) $id;
                        }
                    }
                }

                if ($ids === []) {
                    throw new Exception(__('No todo selected.'));
                }

                switch (ManageVars::post('action')) {
                    case 'done':
                        Todos::setStatus($ids, Todos::STATUS_DONE);
                        $message = __('Selected todos marked as done.');

                        break;
                    case 'todo':
                        Todos::setStatus($ids, Todos::STATUS_TODO);
                        $message = __('Selected todos marked as to do.');

                        break;
                    case 'move':
                        if (ManageVars::post('move_list') === '') {
                            throw new Exception(__('You must give the name of the list.'));
                        }
                        Todos::move($ids, ManageVars::post('move_list'));
                        $message = __('Selected todos moved.');

                        break;
                    case 'delete':
                        Todos::delete($ids);
                        $message = __('Selected todos deleted.');

                        break;
                    default:
                        throw new Exception(__('Unknown action.'));
                }

                App::backend()->notices()->addSuccessNotice($message);
                My::redirect($vars->params());
            }

            // Rename a list
            if (isset($_POST['rename'])) {
                if (ManageVars::post('list_to') === '') {
                    throw new Exception(__('You must give the new name of the list.'));
                }

                $from = Todos::cleanList(ManageVars::post('list_from'));
                $to   = Todos::cleanList(ManageVars::post('list_to'));

                Todos::renameList($from, $to);

                // Follow the renamed list
                $params = $vars->params();
                if ($vars->list === $from) {
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

        if (self::$edit) {
            ManageTodo::render();

            return;
        }

        $vars  = self::$vars;
        $lists = Todos::getLists();
        $total = Todos::count($vars->list, $vars->todoStatus(), $vars->query);
        $page  = min($vars->page, max(1, (int) ceil($total / ManageVars::PER_PAGE)));
        $todos = Todos::search($vars->list, $vars->todoStatus(), $vars->query, ManageVars::PER_PAGE, ($page - 1) * ManageVars::PER_PAGE);

        App::backend()->page()->openModule(My::name(), self::head());

        echo
        App::backend()->page()->breadcrumb([
            Html::escapeHTML(App::blog()->name()) => '',
            My::name()                            => '',
        ]) .
        App::backend()->notices()->getNotices() .
        (new Para())
            ->class('new-stuff')
            ->items([
                (new Link())
                    ->class(['button', 'add'])
                    ->href(My::manageUrl([... $vars->params(), 'new' => 1]))
                    ->text(__('New todo')),
            ])
            ->render() .
        self::datalist($lists)->render() .
        self::filterForm($lists)->render();

        if ($todos === []) {
            echo (new Note())
                ->class('info')
                ->text($total === 0 && $vars->params() === [] ? __('There is nothing to do.') : __('No todo matches the filter.'))
                ->render();
        } else {
            $pager = new Pager($page, $total, ManageVars::PER_PAGE, 10);

            echo
            $pager->getLinks() .
            self::todosForm($todos, $total)->render() .
            self::quickForm()->render() .
            $pager->getLinks();
        }

        if ($lists !== []) {
            echo self::renameForm($lists)->render();
        }

        App::backend()->page()->helpBlock(My::id());

        App::backend()->page()->closeModule();
    }

    /**
     * Get page head.
     */
    public static function head(): string
    {
        return App::backend()->page()->jsJson('pensebete', [
            'confirm_delete'     => __('Are you sure you want to delete selected todos?'),
            'confirm_delete_one' => __('Are you sure you want to delete this todo?'),
        ]) .
        My::jsLoad('manage');
    }

    /**
     * Get the lists names suggestions, used by the fields where a list name is typed.
     *
     * @param   array<int, array{name: string, todo: int, done: int}>   $lists  The lists
     */
    public static function datalist(array $lists): Datalist
    {
        return (new Datalist(self::DATALIST))
            ->items(array_map(fn (array $list): Option => new Option(null, Html::escapeHTML($list['name'])), $lists));
    }

    /**
     * Get the field where a list name is typed.
     */
    public static function listInput(string $id, string $value = ''): Input
    {
        return (new Input($id))
            ->size(20)
            ->maxlength(Todos::LIST_LENGTH)
            ->list(self::DATALIST)
            ->value(Html::escapeHTML($value));
    }

    /**
     * @param   array<int, array{name: string, todo: int, done: int}>   $lists  The lists
     */
    private static function filterForm(array $lists): Form
    {
        $vars = self::$vars;

        $lists_options = [new Option(__('All lists'), '')];
        $known         = false;
        foreach ($lists as $list) {
            $lists_options[] = new Option(Html::escapeHTML(sprintf('%s (%s)', $list['name'], $list['todo'])), Html::escapeHTML($list['name']));
            $known           = $known || $list['name'] === $vars->list;
        }
        if (!$known && $vars->list !== '') {
            // A list which has no todo anymore
            $lists_options[] = new Option(Html::escapeHTML(sprintf('%s (%s)', $vars->list, 0)), Html::escapeHTML($vars->list));
        }

        return (new Form('todos-filter-form'))
            ->method('get')
            ->action(App::backend()->url()->getBase('admin.plugin.' . My::id()))
            ->fields([
                (new Para())
                    ->class('form-buttons')
                    ->items([
                        (new Select('list'))
                            ->items($lists_options)
                            ->default(Html::escapeHTML($vars->list))
                            ->label((new Label(__('List:'), Label::IL_TF))),
                        (new Select('status'))
                            ->items([
                                new Option(__('To do'), ManageVars::STATUS_TODO),
                                new Option(__('Done'), ManageVars::STATUS_DONE),
                                new Option(__('All statuses'), ManageVars::STATUS_ALL),
                            ])
                            ->default($vars->status)
                            ->label((new Label(__('Status:'), Label::IL_TF))),
                        (new Input('q'))
                            ->size(20)
                            ->maxlength(100)
                            ->value(Html::escapeHTML($vars->query))
                            ->label((new Label(__('Search:'), Label::IL_TF))),
                        (new Submit('todos-filter-submit', __('Apply'))),
                        ... App::backend()->url()->hiddenFormFields('admin.plugin.' . My::id()),
                    ]),
            ]);
    }

    /**
     * @param   array<int, Todo>    $todos  The todos of the page
     * @param   int                 $total  The number of todos matching the filter
     */
    private static function todosForm(array $todos, int $total): Form
    {
        $vars    = self::$vars;
        $user_tz = is_string($user_tz = App::auth()->getInfo('user_tz')) ? $user_tz : 'UTC';
        $rows    = [];
        foreach ($todos as $todo) {
            $title = [
                (new Link())
                    ->href(My::manageUrl([... $vars->params(), 'id' => $todo->id]))
                    ->title(__('Edit this todo'))
                    ->text(Html::escapeHTML($todo->title)),
            ];
            if ($todo->note !== '') {
                $note    = trim((string) preg_replace('/\s+/u', ' ', $todo->note));
                $title[] = (new Note())
                    ->class('form-note')
                    ->text(Html::escapeHTML(mb_strlen($note) > 160 ? mb_substr($note, 0, 160) . '…' : $note));
            }

            $rows[] = (new Tr('todo_' . $todo->id))
                ->class($todo->isDone() ? ['line', 'offline'] : 'line')
                ->cols([
                    (new Td())
                        ->class(['nowrap', 'minimal'])
                        ->items([
                            (new Checkbox(['todos[]', 'todos_' . $todo->id]))
                                ->value($todo->id)
                                ->title(__('Select this todo')),
                        ]),
                    (new Td())
                        ->class('maximal')
                        ->items($title),
                    (new Td())
                        ->class('nowrap')
                        ->items([
                            (new Link())
                                ->href(My::manageUrl(['list' => $todo->list, ... ($vars->status === ManageVars::STATUS_TODO ? [] : ['status' => $vars->status])]))
                                ->title(__('Show this list'))
                                ->text(Html::escapeHTML($todo->list)),
                        ]),
                    (new Td())
                        ->class('nowrap')
                        ->text(Html::escapeHTML(Date::dt2str(__('%Y-%m-%d %H:%M'), $todo->updated, $user_tz))),
                    (new Td())
                        ->class('nowrap')
                        ->text(Html::escapeHTML($todo->user)),
                    (new Td())
                        ->class(['nowrap', 'status'])
                        ->items([
                            $todo->isDone() ?
                                (new Img('images/check-on.svg'))
                                    ->class(['mark', 'mark-check-on'])
                                    ->alt(__('Done')) :
                                (new Img('images/pending.svg'))
                                    ->class(['mark', 'mark-pending'])
                                    ->alt(__('To do')),
                            // These buttons belong to another form: the default button of this one must stay the selected todos action
                            $todo->isDone() ?
                                (new Submit(['undone[' . $todo->id . ']', 'undone_' . $todo->id], __('Reopen')))
                                    ->class('reset')
                                    ->form(self::QUICK_FORM)
                                    ->title(__('Mark this todo as to do')) :
                                (new Submit(['done[' . $todo->id . ']', 'done_' . $todo->id], __('Done')))
                                    ->class('reset')
                                    ->form(self::QUICK_FORM)
                                    ->title(__('Mark this todo as done')),
                        ]),
                ]);
        }

        return (new Form('todos-form'))
            ->method('post')
            ->action(My::manageUrl($vars->params()))
            ->fields([
                (new Div())
                    ->class('table-outer')
                    ->items([
                        (new Table('todos'))
                            ->class('rch')
                            ->caption(new Caption(sprintf(__('Todos (%s)'), $total)))
                            ->thead((new Thead())
                                ->rows([
                                    (new Tr())
                                        ->cols([
                                            (new Th())->text(__('Todo'))->scope('col')->colspan(2)->class('first'),
                                            (new Th())->text(__('List'))->scope('col'),
                                            (new Th())->text(__('Updated'))->scope('col'),
                                            (new Th())->text(__('Added by'))->scope('col'),
                                            (new Th())->text(__('Status'))->scope('col'),
                                        ]),
                                ]))
                            ->tbody((new Tbody())->rows($rows)),
                    ]),
                (new Div())
                    ->class('two-cols')
                    ->items([
                        (new Para())
                            ->class(['col', 'checkboxes-helpers']),
                        (new Para())
                            ->class(['col', 'right', 'form-buttons'])
                            ->items([
                                (new Select('action'))
                                    ->items([
                                        new Option(__('Mark as done'), 'done'),
                                        new Option(__('Mark as to do'), 'todo'),
                                        new Option(__('Move to the list'), 'move'),
                                        new Option(__('Delete'), 'delete'),
                                    ])
                                    ->label((new Label(__('Selected todos action:'), Label::IL_TF))),
                                self::listInput('move_list')
                                    ->label((new Label(__('List:'), Label::IL_TF))->class('move-list')),
                                (new Submit('do-action', __('ok'))),
                                ... My::hiddenFields(),
                            ]),
                    ]),
            ]);
    }

    /**
     * Get the form used by the buttons of the todos lines.
     */
    private static function quickForm(): Form
    {
        return (new Form(self::QUICK_FORM))
            ->method('post')
            ->action(My::manageUrl(self::$vars->params()))
            ->fields(My::hiddenFields());
    }

    /**
     * @param   array<int, array{name: string, todo: int, done: int}>   $lists  The lists
     */
    private static function renameForm(array $lists): Details
    {
        return (new Details('list-rename'))
            ->summary(new Summary(__('Rename a list')))
            ->items([
                (new Form('list-rename-form'))
                    ->method('post')
                    ->action(My::manageUrl(self::$vars->params()))
                    ->fields([
                        (new Para())
                            ->items([
                                (new Select('list_from'))
                                    ->items(array_map(fn (array $list): Option => new Option(Html::escapeHTML($list['name']), Html::escapeHTML($list['name'])), $lists))
                                    ->default(Html::escapeHTML(self::$vars->list))
                                    ->label((new Label(__('List:'), Label::OL_TF))),
                            ]),
                        (new Para())
                            ->items([
                                self::listInput('list_to')
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
                    ]),
            ]);
    }
}
