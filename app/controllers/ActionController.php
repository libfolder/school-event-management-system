<?php

namespace Controllers;

class ActionController extends BaseController
{
    public function index()
    {
        $models = $this->f3->get('models');
        $actions = $models['Action']->all();
        foreach ($actions as $index => $action) {
            $actions[$index]['row_number'] = $index + 1;
        }

        $this->render('actions/index.htm', [
            'title' => 'مدیریت اقدامات',
            'help' => 'لیست اقدامات ثبت‌شده مانند احضار والدین، معرفی به مشاور، پاداش و ... نمایش داده می‌شود.',
            'actions' => $actions,
        ], 'layout.htm');
    }

    public function create()
    {
        $models = $this->f3->get('models');
        $preselectedEventId = (int)($this->f3->get('GET.event_id') ?? 0);
        $preselectedStudentId = (int)($this->f3->get('GET.student_id') ?? 0);

        $this->render('actions/edit.htm', [
            'title' => 'ثبت اقدام جدید',
            'help' => 'یک دانش‌آموز و رویداد مرتبط را انتخاب کنید. نوع اقدام مشخص کنید و در صورت نیاز نتیجه را ثبت کنید.',
            'action' => 'create',
            'form_action' => '/actions/store',
            'students' => $models['Student']->allWithClass(),
            'events' => $models['Event']->all(),
            'item' => [],
            'preselected_event_id' => $preselectedEventId,
            'preselected_student_id' => $preselectedStudentId,
            'current_jalali_year' => \App\Helpers\JalaliDate::currentYear(),
        ], 'layout.htm');
    }

    public function store()
    {
        $this->validateAction();

        if ($this->hasErrors()) {
            $models = $this->f3->get('models');
            $this->renderWithErrors('actions/edit.htm', [
                'title' => 'ثبت اقدام جدید',
                'action' => 'create',
                'form_action' => '/actions/store',
                'students' => $models['Student']->allWithClass(),
                'events' => $models['Event']->all(),
                'item' => [],
            ]);
            return;
        }

        $models = $this->f3->get('models');
        $actionModel = $models['Action'];

        $eventId = $this->postRaw('event_id');
        $actionDate = date('Y-m-d');

        $actionModel->create([
            'event_id' => $eventId !== '' ? (int)$eventId : null,
            'student_id' => (int)$this->postRaw('student_id'),
            'action_type' => $this->postRaw('action_type'),
            'title' => $this->postClean('title'),
            'description' => $this->postClean('description'),
            'is_automatic' => 0,
            'status' => $this->postRaw('status'),
            'result' => null,
            'action_date' => $actionDate,
            'created_by' => 1,
        ]);

        $this->redirect('/actions');
    }

    public function edit($f3, $params)
    {
        $models = $this->f3->get('models');
        $action = $models['Action']->find((int)$params['id']);

        if (!$action) {
            $f3->error(404, 'اقدام یافت نشد');
            return;
        }

        $preselectedStudentId = (int)($this->f3->get('GET.student_id') ?? 0);

        $this->render('actions/edit.htm', [
            'title' => 'ویرایش اقدام',
            'help' => 'وضعیت اقدام را می‌توانید به «تکمیل شده» تغییر دهید و نتیجه را ثبت کنید.',
            'action' => 'edit',
            'form_action' => '/actions/' . $params['id'] . '/update',
            'students' => $models['Student']->allWithClass(),
            'events' => $models['Event']->all(),
            'item' => $action,
            'preselected_student_id' => $preselectedStudentId,
        ], 'layout.htm');
    }

    public function update($f3, $params)
    {
        $this->validateAction(true);

        if ($this->hasErrors()) {
            $models = $this->f3->get('models');
            $this->renderWithErrors('actions/edit.htm', [
                'title' => 'ویرایش اقدام',
                'action' => 'edit',
                'form_action' => '/actions/' . $params['id'] . '/update',
                'students' => $models['Student']->allWithClass(),
                'events' => $models['Event']->all(),
                'item' => [],
            ]);
            return;
        }

        $models = $this->f3->get('models');
        $actionModel = $models['Action'];

        $eventId = $this->postRaw('event_id');
        $actionDate = date('Y-m-d');

        $actionModel->update((int)$params['id'], [
            'event_id' => $eventId !== '' ? (int)$eventId : null,
            'student_id' => (int)$this->postRaw('student_id'),
            'action_type' => $this->postRaw('action_type'),
            'title' => $this->postClean('title'),
            'description' => $this->postClean('description'),
            'is_automatic' => 0,
            'status' => $this->postRaw('status'),
            'result' => $this->postRaw('result') ?: null,
            'action_date' => $actionDate,
        ]);

        $this->redirect('/actions');
    }

    private function validateAction(bool $isUpdate = false): void
    {
        $required = [
            'student_id' => 'دانش‌آموز',
            'action_type' => 'نوع اقدام',
            'title' => 'عنوان',
            'status' => 'وضعیت',
        ];
        $this->validateRequired($required);
        $this->validateExists('student_id', 'دانش‌آموز', 'Student', 'find');

        $this->validateIn('action_type', 'نوع اقدام', [
            'reward', 'honor_board', 'student_of_week', 'prize',
            'teacher_referral', 'parent_meeting', 'mentor_referral',
        ]);
        $this->validateIn('status', 'وضعیت', ['open', 'completed']);
        $this->validateLength('title', 'عنوان', 2, 255);
        $this->validateLength('description', 'توضیحات', 0, 1000);

        $eventId = $this->postRaw('event_id');
        if ($eventId !== '') {
            $this->validateExists('event_id', 'رویداد مرتبط', 'Event', 'find');
        }

        if ($isUpdate) {
            $this->validateLength('result', 'نتیجه نهایی', 0, 1000);
        }
    }

    public function complete($f3, $params)
    {
        $models = $this->f3->get('models');
        $actionModel = $models['Action'];
        $result = $this->f3->clean($this->f3->get('POST.result'));
        $actionModel->complete((int)$params['id'], $result);

        $this->redirect('/actions');
    }

    public function delete($f3, $params)
    {
        $models = $this->f3->get('models');
        $actionModel = $models['Action'];
        $actionModel->delete((int)$params['id']);

        $this->redirect('/actions');
    }
}
