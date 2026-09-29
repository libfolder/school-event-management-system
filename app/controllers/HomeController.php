<?php

namespace Controllers;

class HomeController extends BaseController
{
    public function index()
    {
        $models = $this->f3->get('models');
        $studentModel = $models['Student'];
        $classModel = $models['Class'];
        $eventModel = $models['Event'];
        $actionModel = $models['Action'];

        $classes = $classModel->all();
        foreach ($classes as $index => $class) {
            $classes[$index]['row_number'] = $index + 1;
        }
        $positiveScoreStudents = $eventModel->getStudentsByScoreRaw(20, true);
        foreach ($positiveScoreStudents as $index => $student) {
            $positiveScoreStudents[$index]['row_number'] = $index + 1;
        }
        $negativeScoreStudents = $eventModel->getStudentsByScoreRaw(20, false);
        foreach ($negativeScoreStudents as $index => $student) {
            $negativeScoreStudents[$index]['row_number'] = $index + 1;
        }
        $topAbsentees = $eventModel->topAbsentees(20);
        foreach ($topAbsentees as $index => $student) {
            $topAbsentees[$index]['row_number'] = $index + 1;
        }
        $openRecords = $actionModel->getOpenRecords();
        foreach ($openRecords as $index => $record) {
            $openRecords[$index]['row_number'] = $index + 1;
            $openRecords[$index]['action_type_label'] = \App\Models\Action::getActionTypeLabel($record['action_type']);
        }
        $followUpStudents = $actionModel->getStudentsWithFollowUp();

        $this->render('home.htm', [
            'title' => $this->f3->get('app_name'),
            'help' => 'این صفحه نمای کلی سیستم است. می‌توانید آمار کلاس‌ها، لیست دانش‌آموزان برتر، دانش‌آموزان با امتیاز منفی و گزارش‌های لحظه‌ای را مشاهده کنید.',
            'classes' => $classes,
            'positiveScoreStudents' => $positiveScoreStudents,
            'negativeScoreStudents' => $negativeScoreStudents,
            'topAbsentees' => $topAbsentees,
            'openRecords' => $openRecords,
            'followUpStudents' => $followUpStudents,
        ], 'layout.htm');
    }
}
