<?php

namespace Controllers;

class ClassController extends BaseController
{
    public function index()
    {
        $models = $this->f3->get('models');
        $classes = $models['Class']->all();
        foreach ($classes as $index => $class) {
            $classes[$index]['row_number'] = $index + 1;
        }

        $this->render('classes/index.htm', [
            'title' => 'مدیریت کلاس‌ها',
            'help' => 'لیست کلاس‌ها به همراه نام معلم و تعداد دانش‌آموزان نمایش داده می‌شود. برای مدیریت دانش‌آموزان یک کلاس، روی نام کلاس کلیک کنید.',
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
        foreach ($students as $index => $student) {
            $students[$index]['row_number'] = $index + 1;
        }

        $this->render('classes/students.htm', [
            'title' => 'دانش‌آموزان ' . $class['name'],
            'help' => 'فهرست دانش‌آموزان این کلاس. برای ویرایش اطلاعات هر دانش‌آموز، روی دکمه «ویرایش» کلیک کنید.',
            'class' => $class,
            'students' => $students,
        ], 'layout.htm');
    }

    public function create()
    {
        $this->render('classes/edit.htm', [
            'title' => 'اضافه کردن کلاس',
            'help' => 'نام کلاس و پایه تحصیلی اجباری هستند. نام معلم اختیاری است.',
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
            'teacher_name' => $this->postClean('teacher_name') ?: null,
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
            'help' => 'نام کلاس و پایه تحصیلی اجباری هستند. نام معلم اختیاری است.',
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
            'teacher_name' => $this->postClean('teacher_name') ?: null,
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
        ]);
        $this->validateLength('name', 'نام کلاس', 2, 50);
        $this->validateLength('teacher_name', 'نام معلم', 0, 100);
        $this->validateInt('grade', 'پایه', 1, 12);
    }
}
