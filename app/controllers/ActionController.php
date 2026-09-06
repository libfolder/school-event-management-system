<?php

namespace Controllers;

class ActionController extends BaseController
{
    public function index()
    {
        $models = $this->f3->get('models');
        $actions = $models['Action']->all();

        $this->render('actions/index.htm', [
            'title' => 'مدیریت اقدامات',
            'actions' => $actions,
        ], 'layout.htm');
    }

    public function create()
    {
        $models = $this->f3->get('models');

        $this->render('actions/edit.htm', [
            'title' => 'ثبت اقدام جدید',
            'action' => 'create',
            'form_action' => '/actions/store',
            'students' => $models['Student']->allWithClass(),
            'events' => $models['Event']->all(),
            'item' => [],
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
        $actionDate = $this->postRaw('action_date');
        $actionDate = $actionDate !== '' ? \App\Helpers\JalaliDate::parse($actionDate) : null;

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

        $this->render('actions/edit.htm', [
            'title' => 'ویرایش اقدام',
            'action' => 'edit',
            'form_action' => '/actions/' . $params['id'] . '/update',
            'students' => $models['Student']->allWithClass(),
            'events' => $models['Event']->all(),
            'item' => $action,
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
        $actionDate = $this->postRaw('action_date');
        $actionDate = $actionDate !== '' ? \App\Helpers\JalaliDate::parse($actionDate) : null;

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

        $actionDate = $this->postRaw('action_date');
        if ($actionDate !== '') {
            $this->validateDate('action_date', 'تاریخ اقدام');
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
