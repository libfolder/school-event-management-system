<?php

namespace Controllers;

class EventTypeController extends BaseController
{
    public function index()
    {
        $models = $this->f3->get('models');
        $eventTypes = $models['EventType']->all();

        $this->render('event-types/index.htm', [
            'title' => 'انواع رویدادها',
            'eventTypes' => $eventTypes,
        ], 'layout.htm');
    }

    public function create()
    {
        $this->render('event-types/edit.htm', [
            'title' => 'اضافه کردن نوع رویداد',
            'action' => 'create',
            'form_action' => '/event-types/store',
            'item' => [],
        ], 'layout.htm');
    }

    public function store()
    {
        $this->validateEventType();

        if ($this->hasErrors()) {
            $this->renderWithErrors('event-types/edit.htm', [
                'title' => 'اضافه کردن نوع رویداد',
                'action' => 'create',
                'form_action' => '/event-types/store',
                'item' => [],
            ]);
            return;
        }

        $models = $this->f3->get('models');
        $eventTypeModel = $models['EventType'];

        $score = $this->validateInt('default_score', 'امتیاز پیش‌فرض', -100, 100);
        $isPositive = $score >= 0 ? 1 : 0;

        $eventTypeModel->create([
            'name' => $this->postClean('name'),
            'description' => $this->postClean('description'),
            'default_score' => $score,
            'is_positive' => $isPositive,
        ]);

        $this->redirect('/event-types');
    }

    public function edit($f3, $params)
    {
        $models = $this->f3->get('models');
        $item = $models['EventType']->find((int)$params['id']);

        if ($item->dry()) {
            $f3->error(404, 'نوع رویداد یافت نشد');
            return;
        }

        $this->render('event-types/edit.htm', [
            'title' => 'ویرایش نوع رویداد',
            'action' => 'edit',
            'form_action' => '/event-types/' . $params['id'] . '/update',
            'item' => $item,
        ], 'layout.htm');
    }

    public function update($f3, $params)
    {
        $this->validateEventType();

        if ($this->hasErrors()) {
            $models = $this->f3->get('models');
            $this->renderWithErrors('event-types/edit.htm', [
                'title' => 'ویرایش نوع رویداد',
                'action' => 'edit',
                'form_action' => '/event-types/' . $params['id'] . '/update',
                'item' => $models['EventType']->find((int)$params['id']),
            ]);
            return;
        }

        $models = $this->f3->get('models');
        $eventTypeModel = $models['EventType'];

        $score = $this->validateInt('default_score', 'امتیاز پیش‌فرض', -100, 100);
        $isPositive = $score >= 0 ? 1 : 0;

        $eventTypeModel->update((int)$params['id'], [
            'name' => $this->postClean('name'),
            'description' => $this->postClean('description'),
            'default_score' => $score,
            'is_positive' => $isPositive,
        ]);

        $this->redirect('/event-types');
    }

    private function validateEventType(): void
    {
        $this->validateRequired([
            'name' => 'نام نوع رویداد',
            'default_score' => 'امتیاز پیش‌فرض',
        ]);
        $this->validateLength('name', 'نام نوع رویداد', 2, 100);
        $this->validateLength('description', 'توضیحات', 0, 1000);
        $this->validateInt('default_score', 'امتیاز پیش‌فرض', -100, 100);
    }
}
