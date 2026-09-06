<?php

namespace Controllers;

class ReportController extends BaseController
{
    public function index()
    {
        $models = $this->f3->get('models');
        $classModel = $models['Class'];
        $eventModel = $models['Event'];

        $classes = $classModel->all();
        $classId = (int)($this->f3->get('GET.class_id') ?? 0);

        $classSummary = $eventModel->getClassSummary();
        $topPositive = $eventModel->getStudentsByScoreRaw(10, true);
        $topNegative = $eventModel->getStudentsByScoreRaw(10, false);
        $topAbsentees = $eventModel->topAbsentees(10);

        $selectedClass = null;
        $classStudents = [];
        if ($classId > 0) {
            $selectedClass = $classModel->find($classId);
            if ($selectedClass !== null && !$selectedClass->dry()) {
                $classStudents = $eventModel->getStudentsByScoreForClass($classId);
            }
        }

        $this->render('reports/index.htm', [
            'title' => 'گزارش‌ها',
            'classes' => $classes,
            'classId' => $classId,
            'classSummary' => $classSummary,
            'topPositive' => $topPositive,
            'topNegative' => $topNegative,
            'topAbsentees' => $topAbsentees,
            'selectedClass' => $selectedClass,
            'classStudents' => $classStudents,
        ], 'layout.htm');
    }

    public function analysis()
    {
        $models = $this->f3->get('models');
        $eventModel = $models['Event'];

        $analysis = $eventModel->analyze();

        $this->render('reports/analysis.htm', [
            'title' => 'تحلیل و نتیجه‌گیری آمار',
            'stats' => $analysis['stats'],
            'findings' => $analysis['findings'],
            'summary' => $analysis['summary'],
        ], 'layout.htm');
    }

    public function classForm()
    {
        // Blank paper form: no database data is rendered
        $this->render('reports/class-form.htm', [
            'title' => 'فرم جمع‌آوری اطلاعات کلاس',
        ], 'reports/print-layout.htm');
    }

    public function attendanceForm()
    {
        $models = $this->f3->get('models');
        $classModel = $models['Class'];

        $classes = $classModel->all();

        // Number of empty rows per class (configurable via ?rows=N, even numbers 2..20)
        $rows = (int)($this->f3->get('GET.rows') ?? 10);
        if ($rows < 2) {
            $rows = 2;
        } elseif ($rows > 20) {
            $rows = 20;
        }

        // Up to six classes, each rendered as a blank attendance table
        $attendanceClasses = [];
        foreach (array_slice($classes, 0, 6) as $cls) {
            $attendanceClasses[] = [
                'class' => $cls,
                'rows' => $rows,
            ];
        }

        $this->render('reports/attendance-form.htm', [
            'title' => 'فرم حضور و غیاب کلاس‌ها',
            'classes' => $classes,
            'attendanceClasses' => $attendanceClasses,
            'rows' => $rows,
            'today' => \App\Helpers\JalaliDate::format(date('Y-m-d')),
        ], 'reports/print-layout.htm');
    }
}

