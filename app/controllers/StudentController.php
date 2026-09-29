<?php

namespace Controllers;

class StudentController extends BaseController
{
    public function index()
    {
        $models = $this->f3->get('models');
        $page = max(1, (int)($this->f3->get('GET.page') ?? 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $totalCount = $models['Student']->countWithClass();
        $totalPages = (int)ceil($totalCount / $limit);
        $students = $models['Student']->allWithClass(null, $limit, $offset);
        foreach ($students as $index => $student) {
            $students[$index]['row_number'] = $offset + $index + 1;
        }

        $this->render('students/index.htm', [
            'title' => 'مدیریت دانش‌آموزان',
            'help' => 'در این صفحه فهرست تمام دانش‌آموزان نمایش داده می‌شود. برای افزودن دانش‌آموز جدید از دکمه «دانش‌آموز جدید» استفاده کنید.',
            'students' => $students,
            'search' => '',
            'page' => $page,
            'total_pages' => $totalPages,
            'total_count' => $totalCount,
            'pages' => range(1, $totalPages),
            'list_url' => '/students',
            'query' => '',
        ], 'layout.htm');
    }

    public function search()
    {
        $models = $this->f3->get('models');
        $term = trim($this->f3->get('GET.q') ?? '');
        $page = max(1, (int)($this->f3->get('GET.page') ?? 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $totalCount = $models['Student']->countWithClass($term);
        $totalPages = (int)ceil($totalCount / $limit);
        $students = $models['Student']->allWithClass($term, $limit, $offset);
        foreach ($students as $index => $student) {
            $students[$index]['row_number'] = $offset + $index + 1;
        }

        $this->render('students/index.htm', [
            'title' => 'مدیریت دانش‌آموزان',
            'help' => 'نتایج جستجو بر اساس نام، شماره دانش‌آموزی یا کلاس نمایش داده می‌شود.',
            'students' => $students,
            'search' => $term,
            'page' => $page,
            'total_pages' => $totalPages,
            'total_count' => $totalCount,
            'pages' => range(1, $totalPages),
            'list_url' => '/students/search',
            'query' => '&q=' . urlencode($term),
        ], 'layout.htm');
    }

    public function create()
    {
        $models = $this->f3->get('models');
        $classes = $models['Class']->all();

        $this->render('students/edit.htm', [
            'title' => 'اضافه کردن دانش‌آموز',
            'help' => 'فیلدهای اجباری با علامت * مشخص شده‌اند. شماره دانش‌آموزی و کد ملی باید منحصر به فرد باشند.',
            'action' => 'create',
            'form_action' => '/students/store',
            'classes' => $classes,
            'student' => [],
        ], 'layout.htm');
    }

    public function store()
    {
        $this->validateStudent((int)0);

        if ($this->hasErrors()) {
            $models = $this->f3->get('models');
            $this->renderWithErrors('students/edit.htm', [
                'title' => 'اضافه کردن دانش‌آموز',
                'action' => 'create',
                'form_action' => '/students/store',
                'classes' => $models['Class']->all(),
                'student' => [],
            ]);
            return;
        }

        $models = $this->f3->get('models');
        $models['Student']->create($this->studentData());

        $this->redirect('/students');
    }

    public function edit($f3, $params)
    {
        $models = $this->f3->get('models');
        $student = $models['Student']->find((int)$params['id']);

        if (!$student) {
            $f3->error(404, 'دانش‌آموز یافت نشد');
            return;
        }

        $classes = $models['Class']->all();

        $this->render('students/edit.htm', [
            'title' => 'ویرایش دانش‌آموز',
            'help' => 'می‌توانید اطلاعات دانش‌آموز را ویرایش کنید. شماره دانش‌آموزی و کد ملی باید منحصر به فرد باشند.',
            'action' => 'edit',
            'form_action' => '/students/' . $params['id'] . '/update',
            'classes' => $classes,
            'student' => $student,
        ], 'layout.htm');
    }

    public function update($f3, $params)
    {
        $this->validateStudent((int)$params['id']);

        if ($this->hasErrors()) {
            $models = $this->f3->get('models');
            $student = $models['Student']->find((int)$params['id']);
            $this->renderWithErrors('students/edit.htm', [
                'title' => 'ویرایش دانش‌آموز',
                'action' => 'edit',
                'form_action' => '/students/' . $params['id'] . '/update',
                'classes' => $models['Class']->all(),
                'student' => $student ?? [],
            ]);
            return;
        }

        $models = $this->f3->get('models');
        $models['Student']->update((int)$params['id'], $this->studentData());

        $this->redirect('/students');
    }

    public function show($f3, $params)
    {
        $models = $this->f3->get('models');
        $student = $models['Student']->find((int)$params['id']);

        if (!$student) {
            $f3->error(404, 'دانش‌آموز یافت نشد');
            return;
        }

        $student['total_score'] = $models['Student']->getScore((int)$params['id']);
        $events = $models['Event']->getByStudent((int)$params['id']);

        $this->render('students/show.htm', [
            'title' => $student['name'],
            'help' => 'مشاهده مشخصات و رویدادهای دانش‌آموز.',
            'student' => $student,
            'events' => $events,
        ], 'layout.htm');
    }

    public function delete($f3, $params)
    {
        $models = $this->f3->get('models');
        $studentModel = $models['Student'];
        $studentModel->delete((int)$params['id']);

        $this->redirect('/students');
    }

    private function studentData(): array
    {
        return [
            'student_id' => $this->postClean('student_id'),
            'first_name' => $this->postClean('first_name'),
            'last_name' => $this->postClean('last_name'),
            'melli_code' => $this->postClean('melli_code'),
            'father_name' => $this->postClean('father_name'),
            'mother_name' => $this->postClean('mother_name'),
            'grade' => $this->postClean('grade'),
            'mother_phone' => $this->postClean('mother_phone'),
            'father_phone' => $this->postClean('father_phone'),
            'address' => $this->postClean('address'),
            'photo' => $this->postClean('photo'),
            'class_id' => (int)$this->postRaw('class_id'),
        ];
    }

    private function validateStudent(int $ignoreId): void
    {
        $this->validateRequired([
            'student_id' => 'شماره دانش‌آموزی',
            'first_name' => 'نام',
            'last_name' => 'نام خانوادگی',
            'class_id' => 'کلاس',
        ]);
        $this->validateLength('student_id', 'شماره دانش‌آموزی', 2, 20);
        $this->validateLength('first_name', 'نام', 2, 60);
        $this->validateLength('last_name', 'نام خانوادگی', 2, 60);
        $this->validateLength('melli_code', 'کد ملی', 10, 10);
        $this->validateLength('father_name', 'نام پدر', 3, 100);
        $this->validateLength('mother_name', 'نام مادر', 3, 100);
        $this->validateLength('grade', 'پایه تحصیلی', 1, 20);
        $this->validateLength('mother_phone', 'تلفن مادر', 8, 20);
        $this->validateLength('father_phone', 'تلفن پدر', 8, 20);
        $this->validateLength('address', 'آدرس', 5, 255);
        $this->validateLength('photo', 'عکس', 3, 255);
        $this->validateExists('class_id', 'کلاس', 'Class', 'find');

        if (!$this->hasErrors()) {
            $models = $this->f3->get('models');
            if ($models['Student']->studentIdExists($this->postClean('student_id'), $ignoreId)) {
                $this->setError('student_id', 'این شماره دانش‌آموزی قبلاً ثبت شده است.');
            }
            if ($models['Student']->melliCodeExists($this->postClean('melli_code'), $ignoreId)) {
                $this->setError('melli_code', 'این کد ملی قبلاً ثبت شده است.');
            }
        }
    }
}
