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
        $positiveScoreStudents = $eventModel->getStudentsByScoreRaw(20, true);
        $negativeScoreStudents = $eventModel->getStudentsByScoreRaw(20, false);
        $topAbsentees = $eventModel->topAbsentees(20);
        $openRecords = $actionModel->getOpenRecords();
        $followUpStudents = $actionModel->getStudentsWithFollowUp();

        $this->render('home.htm', [
            'title' => $this->f3->get('app_name'),
            'classes' => $classes,
            'positiveScoreStudents' => $positiveScoreStudents,
            'negativeScoreStudents' => $negativeScoreStudents,
            'topAbsentees' => $topAbsentees,
            'openRecords' => $openRecords,
            'followUpStudents' => $followUpStudents,
        ], 'layout.htm');
    }
}
