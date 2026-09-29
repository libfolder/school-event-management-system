<?php

namespace Controllers;

class AttendanceController extends BaseController
{
    public function index()
    {
        $models = $this->f3->get('models');
        $classModel = $models['Class'];

        $classId = $this->f3->get('GET.class_id') ?? '';
        $date = $this->f3->get('GET.date') ?? '';
        $status = $this->f3->get('GET.status') ?? '';

        $attendances = $models['Attendance']->allWithFilters($classId, $date, $status);
        $classes = $classModel->all();

        $stats = $models['Attendance']->getStats($classId ?: null, $date ?: null, null);

        $this->render('attendance/index.htm', [
            'title' => 'مدیریت حضور و غیاب',
            'help' => 'فهرست رکوردهای حضور و غیاب را مشاهده می‌کنید. می‌توانید بر اساس کلاس، تاریخ یا وضعیت فیلتر کنید.',
            'attendances' => $attendances,
            'classes' => $classes,
            'classId' => $classId,
            'date' => $date,
            'status' => $status,
            'stats' => $stats,
        ], 'layout.htm');
    }

    public function create()
    {
        $models = $this->f3->get('models');
        $classes = $models['Class']->all();
        $allStudents = $models['Student']->allWithClass();

        $selectedClassId = (int)($this->f3->get('GET.class_id') ?? 0);
        $selectedDate = $this->f3->get('GET.date') ?? '';

        $students = [];
        $existingRecords = [];
        if ($selectedClassId > 0 && $selectedDate !== '') {
            $students = $models['Student']->all();
            $students = array_filter($students, function ($s) use ($selectedClassId) {
                return (int)$s['class_id'] === $selectedClassId;
            });

            $records = $models['Attendance']->findByDateAndClass($selectedDate, $selectedClassId);
            foreach ($records as $record) {
                $existingRecords[(int)$record['student_id']] = $record;
            }
        }

        $this->render('attendance/create.htm', [
            'title' => 'ثبت حضور و غیاب',
            'help' => 'برای ثبت سریع یک دانش‌آموز، از فرم بالایی استفاده کنید. برای ثبت گروهی کلاس، ابتدا کلاس و تاریخ را انتخاب کنید.',
            'action' => 'create',
            'form_action' => '/attendance/store',
            'classes' => $classes,
            'allStudents' => $allStudents,
            'selectedClassId' => $selectedClassId,
            'selectedDate' => $selectedDate,
            'students' => $students,
            'existingRecords' => $existingRecords,
        ], 'layout.htm');
    }

    public function store()
    {
        $models = $this->f3->get('models');

        $classId = (int)$this->postRaw('class_id');
        $date = $this->postRaw('attendance_date');
        $recordedBy = 1;

        $studentIdRaw = $this->f3->get('POST.student_id');
        $isQuickAdd = !is_array($studentIdRaw);

        if ($isQuickAdd) {
            $studentId = (int)($studentIdRaw ?? 0);
            $status = $this->postRaw('status');
            $period = $this->postClean('period');
            $note = $this->postClean('notes');

            if ($studentId <= 0) {
                $this->setError('student_id', 'لطفاً دانش‌آموز را انتخاب کنید.');
            }
            if ($date === '') {
                $this->setError('attendance_date', 'لطفاً تاریخ را وارد کنید.');
            }
            if ($status === '') {
                $this->setError('status', 'لطفاً وضعیت را انتخاب کنید.');
            }
            if (!$this->validateExists('student_id', 'دانش‌آموز', 'Student', 'find')) {
                // validationExists sets error if not found
            }
            if ($date !== '' && !\App\Helpers\JalaliDate::parse($date)) {
                $this->setError('attendance_date', 'تاریخ وارد شده معتبر نیست.');
            }

            if ($this->hasErrors()) {
                $classes = $models['Class']->all();
                $allStudents = $models['Student']->allWithClass();
                $this->renderWithErrors('attendance/create.htm', [
                    'title' => 'ثبت حضور و غیاب',
                    'action' => 'create',
                    'form_action' => '/attendance/store',
                    'classes' => $classes,
                    'allStudents' => $allStudents,
                    'selectedClassId' => $classId,
                    'selectedDate' => $date,
                    'students' => [],
                    'existingRecords' => [],
                ]);
                return;
            }

            $studentIdInt = (int)$studentIdRaw;
            if ($models['Attendance']->recordExists($studentIdInt, $date)) {
                $models['Attendance']->updateByStudentAndDate($studentIdInt, $date, [
                    'student_id' => $studentIdInt,
                    'attendance_date' => $date,
                    'status' => $status,
                    'period' => $period,
                    'notes' => $note,
                    'recorded_by' => $recordedBy,
                ]);
            } else {
                $models['Attendance']->create([
                    'student_id' => $studentIdInt,
                    'attendance_date' => $date,
                    'status' => $status,
                    'period' => $period,
                    'notes' => $note,
                    'recorded_by' => $recordedBy,
                ]);
            }

            $this->redirect('/attendance');
        }

        $studentIds = $studentIdRaw ?? [];
        $statuses = $this->f3->get('POST.status') ?? [];
        $periods = $this->f3->get('POST.period') ?? [];
        $notes = $this->f3->get('POST.notes') ?? [];

        if ($classId <= 0) {
            $this->setError('class_id', 'لطفاً کلاس را انتخاب کنید.');
        }

        if ($date === '') {
            $this->setError('attendance_date', 'لطفاً تاریخ را وارد کنید.');
        }

        if (empty($studentIds)) {
            $this->setError('students', 'هیچ دانش‌آموزی انتخاب نشده است.');
        }

        if ($this->hasErrors()) {
            $classes = $models['Class']->all();
            $students = $models['Student']->all();
            $students = array_filter($students, function ($s) use ($classId) {
                return (int)$s['class_id'] === $classId;
            });

            $existingRecords = [];
            if ($classId > 0 && $date !== '') {
                $records = $models['Attendance']->findByDateAndClass($date, $classId);
                foreach ($records as $record) {
                    $existingRecords[(int)$record['student_id']] = $record;
                }
            }

            $this->renderWithErrors('attendance/create.htm', [
                'title' => 'ثبت حضور و غیاب',
                'action' => 'create',
                'form_action' => '/attendance/store',
                'classes' => $classes,
                'selectedClassId' => $classId,
                'selectedDate' => $date,
                'students' => $students,
                'existingRecords' => $existingRecords,
            ]);
            return;
        }

        foreach ($studentIds as $index => $studentId) {
            $studentIdInt = (int)$studentId;
            $status = $statuses[$index] ?? 'present';
            $period = $periods[$index] ?? '';
            $note = $notes[$index] ?? '';

            if ($models['Attendance']->recordExists($studentIdInt, $date)) {
                $models['Attendance']->updateByStudentAndDate($studentIdInt, $date, [
                    'student_id' => $studentIdInt,
                    'attendance_date' => $date,
                    'status' => $status,
                    'period' => $period,
                    'notes' => $note,
                    'recorded_by' => $recordedBy,
                ]);
            } else {
                $models['Attendance']->create([
                    'student_id' => $studentIdInt,
                    'attendance_date' => $date,
                    'status' => $status,
                    'period' => $period,
                    'notes' => $note,
                    'recorded_by' => $recordedBy,
                ]);
            }
        }

        $this->redirect('/attendance');
    }

    public function edit($f3, $params)
    {
        $models = $this->f3->get('models');
        $attendance = $models['Attendance']->findOne((int)$params['id']);

        if (!$attendance) {
            $f3->error(404, 'رکورد حضور و غیاب یافت نشد');
            return;
        }

        $classes = $models['Class']->all();

        $this->render('attendance/edit.htm', [
            'title' => 'ویرایش حضور و غیاب',
            'help' => 'وضعیت حضور دانش‌آموز را تغییر دهید. تاریخ، وضعیت و توضیحات را می‌توانید ویرایش کنید.',
            'action' => 'edit',
            'form_action' => '/attendance/' . $params['id'] . '/update',
            'classes' => $classes,
            'students' => $models['Student']->allWithClass(),
            'attendance' => $attendance,
        ], 'layout.htm');
    }

    public function update($f3, $params)
    {
        $this->validateAttendance();

        if ($this->hasErrors()) {
            $models = $this->f3->get('models');
            $attendance = $models['Attendance']->findOne((int)$params['id']);
            $classes = $models['Class']->all();

            $this->renderWithErrors('attendance/edit.htm', [
                'title' => 'ویرایش حضور و غیاب',
                'action' => 'edit',
                'form_action' => '/attendance/' . $params['id'] . '/update',
                'classes' => $classes,
                'attendance' => $attendance,
            ]);
            return;
        }

        $models = $this->f3->get('models');
        $models['Attendance']->update((int)$params['id'], [
            'student_id' => (int)$this->postRaw('student_id'),
            'attendance_date' => $this->postRaw('attendance_date'),
            'status' => $this->postRaw('status'),
            'period' => $this->postClean('period'),
            'notes' => $this->postClean('notes'),
            'recorded_by' => 1,
        ]);

        $this->redirect('/attendance');
    }

    public function delete($f3, $params)
    {
        $models = $this->f3->get('models');
        $models['Attendance']->delete((int)$params['id']);

        $this->redirect('/attendance');
    }

    private function validateAttendance(): void
    {
        $this->validateRequired([
            'student_id' => 'دانش‌آموز',
            'attendance_date' => 'تاریخ حضور',
            'status' => 'وضعیت حضور',
        ]);
        $this->validateExists('student_id', 'دانش‌آموز', 'Student', 'find');
        $this->validateDate('attendance_date', 'تاریخ حضور');
        $this->validateIn('status', 'وضعیت حضور', ['present', 'absent', 'late', 'excused']);
        $this->validateLength('period', 'بازه زمانی', 0, 50);
        $this->validateLength('notes', 'توضیحات', 0, 500);
    }
}
