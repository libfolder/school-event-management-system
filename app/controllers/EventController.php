<?php

namespace Controllers;

class EventController extends BaseController
{
    public function index()
    {
        $models = $this->f3->get('models');
        $events = $models['Event']->all();

        $this->render('events/index.htm', [
            'title' => 'مدیریت رویدادها',
            'events' => $events,
        ], 'layout.htm');
    }

    public function create()
    {
        $models = $this->f3->get('models');

        $this->render('events/edit.htm', [
            'title' => 'ثبت رویداد جدید',
            'action' => 'create',
            'form_action' => '/events/store',
            'students' => $models['Student']->allWithClass(),
            'eventTypes' => $models['EventType']->all(),
            'event' => [],
        ], 'layout.htm');
    }

    public function store()
    {
        $this->validateEvent();

        if ($this->hasErrors()) {
            $models = $this->f3->get('models');
            $this->renderWithErrors('events/edit.htm', [
                'title' => 'ثبت رویداد جدید',
                'action' => 'create',
                'form_action' => '/events/store',
                'students' => $models['Student']->allWithClass(),
                'eventTypes' => $models['EventType']->all(),
                'event' => [],
            ]);
            return;
        }

        $models = $this->f3->get('models');
        $eventModel = $models['Event'];

        $eventTypeId = (int)$this->postRaw('event_type_id');
        $eventType = $models['EventType']->find($eventTypeId);

        $score = (int)$this->postRaw('score');
        $eventId = $eventModel->create([
            'student_id' => (int)$this->postRaw('student_id'),
            'event_type_id' => $eventTypeId,
            'score' => $score,
            'description' => $this->postClean('description'),
            'teacher_id' => 1,
            'event_date' => \App\Helpers\JalaliDate::parse($this->postRaw('event_date')),
        ]);

        $this->checkAndCreateActions((int)$eventId, $score, (int)$this->postRaw('student_id'));

        $this->redirect('/events');
    }

    public function edit($f3, $params)
    {
        $models = $this->f3->get('models');
        $event = $models['Event']->find((int)$params['id']);

        if (!$event) {
            $f3->error(404, 'رویداد یافت نشد');
            return;
        }

        $this->render('events/edit.htm', [
            'title' => 'ویرایش رویداد',
            'action' => 'edit',
            'form_action' => '/events/' . $params['id'] . '/update',
            'students' => $models['Student']->allWithClass(),
            'eventTypes' => $models['EventType']->all(),
            'event' => $event,
        ], 'layout.htm');
    }

    public function update($f3, $params)
    {
        $this->validateEvent();

        if ($this->hasErrors()) {
            $models = $this->f3->get('models');
            $this->renderWithErrors('events/edit.htm', [
                'title' => 'ویرایش رویداد',
                'action' => 'edit',
                'form_action' => '/events/' . $params['id'] . '/update',
                'students' => $models['Student']->allWithClass(),
                'eventTypes' => $models['EventType']->all(),
                'event' => [],
            ]);
            return;
        }

        $models = $this->f3->get('models');
        $models['Event']->update((int)$params['id'], [
            'student_id' => (int)$this->postRaw('student_id'),
            'event_type_id' => (int)$this->postRaw('event_type_id'),
            'score' => (int)$this->postRaw('score'),
            'description' => $this->postClean('description'),
            'teacher_id' => 1,
            'event_date' => \App\Helpers\JalaliDate::parse($this->postRaw('event_date')),
        ]);

        $this->redirect('/events');
    }

    private function validateEvent(): void
    {
        $this->validateRequired([
            'student_id' => 'دانش‌آموز',
            'event_type_id' => 'نوع رویداد',
            'score' => 'امتیاز',
            'event_date' => 'تاریخ رویداد',
        ]);
        $this->validateExists('student_id', 'دانش‌آموز', 'Student', 'find');
        $this->validateExists('event_type_id', 'نوع رویداد', 'EventType', 'find');
        $this->validateInt('score', 'امتیاز', -100, 100);
        $this->validateDate('event_date', 'تاریخ رویداد');
        $this->validateLength('description', 'توضیحات', 0, 1000);
    }

    public function delete($f3, $params)
    {
        $models = $this->f3->get('models');
        $eventModel = $models['Event'];
        $eventModel->delete((int)$params['id']);

        $this->redirect('/events');
    }

    private function checkAndCreateActions(int $eventId, int $score, int $studentId): void
    {
        $models = $this->f3->get('models');
        $actionModel = $models['Action'];
        $student = $models['Student']->find($studentId);
        $studentName = $student['name'] ?? 'دانش‌آموز';

        if ($score > 0) {
            if ($score >= 20) {
                $actionModel->create([
                    'event_id' => $eventId,
                    'student_id' => $studentId,
                    'action_type' => 'reward',
                    'title' => 'تشویق و تشکر',
                    'description' => 'دانش‌آموز ' . $studentName . ' جوایز دریافت کرد',
                    'is_automatic' => 1,
                    'status' => 'completed',
                    'created_by' => 1,
                ]);
            }
        } else {
            if ($score <= -20) {
                $actionModel->create([
                    'event_id' => $eventId,
                    'student_id' => $studentId,
                    'action_type' => 'parent_meeting',
                    'title' => 'احضار والدین',
                    'description' => 'احضار والدین به دلیل رفتار نامناسب دانش‌آموز ' . $studentName,
                    'is_automatic' => 1,
                    'status' => 'open',
                    'created_by' => 1,
                ]);
            } elseif ($score <= -10) {
                $actionModel->create([
                    'event_id' => $eventId,
                    'student_id' => $studentId,
                    'action_type' => 'teacher_referral',
                    'title' => 'ارجاع به معلم',
                    'description' => 'ارجاع به معلم مربوطه به دلیل رفتار نامناسب',
                    'is_automatic' => 1,
                    'status' => 'completed',
                    'created_by' => 1,
                ]);
            }
        }
    }
}
