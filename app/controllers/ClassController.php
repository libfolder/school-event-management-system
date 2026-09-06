<?php

namespace Controllers;

class ClassController extends BaseController
{
    public function index()
    {
        $models = $this->f3->get('models');
        $classes = $models['Class']->all();

        $this->render('classes/index.htm', [
            'title' => 'مدیریت کلاس‌ها',
            'classes' => $classes,
        ], 'layout.htm');
    }

    public function students($f3, $params)
    {
        $models = $this->f3->get('models');
        $class = $models['Class']->findWithStats((int)$params['id']);

        if ($class === null) {
            $f3->error(404, 'کلاس یافت نشد');
            return;
        }

        $students = $models['Class']->students((int)$params['id']);

        $this->render('classes/students.htm', [
            'title' => 'دانش‌آموزان ' . $class['name'],
            'class' => $class,
            'students' => $students,
        ], 'layout.htm');
    }

    public function create()
    {
        $this->render('classes/edit.htm', [
            'title' => 'اضافه کردن کلاس',
            'action' => 'create',
            'form_action' => '/classes/store',
            'class' => [],
        ], 'layout.htm');
    }

    public function store()
    {
        $this->validateClass();

        if ($this->hasErrors()) {
            $this->renderWithErrors('classes/edit.htm', [
                'title' => 'اضافه کردن کلاس',
                'action' => 'create',
                'form_action' => '/classes/store',
                'class' => [],
            ]);
            return;
        }

        $models = $this->f3->get('models');
        $models['Class']->create([
            'name' => $this->postClean('name'),
            'grade' => $this->validateInt('grade', 'پایه', 1, 12),
            'section' => $this->postClean('section'),
        ]);

        $this->redirect('/classes');
    }

    public function edit($f3, $params)
    {
        $models = $this->f3->get('models');
        $class = $models['Class']->find((int)$params['id']);

        if ($class->dry()) {
            $f3->error(404, 'کلاس یافت نشد');
            return;
        }

        $this->render('classes/edit.htm', [
            'title' => 'ویرایش کلاس',
            'action' => 'edit',
            'form_action' => '/classes/' . $params['id'] . '/update',
            'class' => $class,
        ], 'layout.htm');
    }

    public function update($f3, $params)
    {
        $this->validateClass();

        if ($this->hasErrors()) {
            $models = $this->f3->get('models');
            $this->renderWithErrors('classes/edit.htm', [
                'title' => 'ویرایش کلاس',
                'action' => 'edit',
                'form_action' => '/classes/' . $params['id'] . '/update',
                'class' => $models['Class']->find((int)$params['id']),
            ]);
            return;
        }

        $models = $this->f3->get('models');
        $models['Class']->update((int)$params['id'], [
            'name' => $this->postClean('name'),
            'grade' => $this->validateInt('grade', 'پایه', 1, 12),
            'section' => $this->postClean('section'),
        ]);

        $this->redirect('/classes');
    }

    public function delete($f3, $params)
    {
        $models = $this->f3->get('models');
        $classModel = $models['Class'];
        $classModel->delete((int)$params['id']);

        $this->redirect('/classes');
    }

    private function validateClass(): void
    {
        $this->validateRequired([
            'name' => 'نام کلاس',
            'grade' => 'پایه',
            'section' => 'بخش',
        ]);
        $this->validateLength('name', 'نام کلاس', 2, 50);
        $this->validateLength('section', 'بخش', 1, 10);
        $this->validateInt('grade', 'پایه', 1, 12);
    }
}
