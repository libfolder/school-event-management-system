<?php

namespace Controllers;

class ImportController extends BaseController
{
    public function index()
    {
        $models = $this->f3->get('models');
        $classes = $models['Class']->all();

        $this->render('import/index.htm', [
            'title' => 'وارد کردن دانش‌آموزان',
            'help' => 'فایل CSV یا TXT با جداکننده کاما آپلود کنید. ستون‌ها: کد ملی، نام، نام خانوادگی، نام معلم.',
            'classes' => $classes,
        ], 'layout.htm');
    }

    public function upload()
    {
        $file = $this->f3->get('FILES.file');
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            $this->setError('file', 'خطا در آپلود فایل.');
            $this->redirect('/import');
            return;
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['csv', 'txt'])) {
            $this->setError('file', 'فرمت فایل باید CSV یا TXT باشد.');
            $this->redirect('/import');
            return;
        }

        $content = file_get_contents($file['tmp_name']);
        $lines = explode(PHP_EOL, trim($content));
        
        $students = [];
        $errors = [];
        $lineNumber = 0;

        foreach ($lines as $line) {
            $lineNumber++;
            $line = trim($line);
            if ($line === '') continue;

            $parts = str_getcsv($line, "\t");
            if (count($parts) < 4) {
                $parts = str_getcsv($line);
            }

            if (count($parts) < 4) {
                $errors[] = "خط در خط {$lineNumber}: داده ناقص است. فرمت expected: کد ملی, نام, نام خانوادگی, نام معلم";
                continue;
            }

            $melliCode = trim($parts[0]);
            $firstName = trim($parts[1]);
            $lastName = trim($parts[2]);
            $teacherName = trim($parts[3]);

            if ($melliCode === '' || $firstName === '' || $lastName === '') {
                $errors[] = "خط در خط {$lineNumber}: کد ملی، نام و نام خانوادگی الزامی است.";
                continue;
            }

            $students[] = [
                'row' => $lineNumber,
                'melli_code' => $melliCode,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'teacher_name' => $teacherName,
            ];
        }

        $selectedClassId = (int)($this->postRaw('class_id') ?? 0);

        $this->f3->set('import_data', $students);
        $this->f3->set('import_errors', $errors);
        $this->f3->set('import_filename', $file['name']);
        $this->f3->set('import_class_id', $selectedClassId);

        $this->render('import/preview.htm', [
            'title' => 'پیش‌نمایش وارد کردن',
            'help' => 'رکوردهای خوانده‌شده از فایل را بررسی کنید. در صورت صحت داده‌ها، روی «تأیید و وارد کردن» کلیک کنید.',
            'students' => $students,
            'errors' => $errors,
            'filename' => $file['name'],
            'selectedClassId' => $selectedClassId,
        ], 'layout.htm');
    }

    public function confirm()
    {
        $melliCodes = $this->f3->get('POST.melli_codes') ?? [];
        $firstNames = $this->f3->get('POST.first_names') ?? [];
        $lastNames = $this->f3->get('POST.last_names') ?? [];

        if (!is_array($melliCodes) || !is_array($firstNames) || !is_array($lastNames) || count($melliCodes) === 0) {
            $this->redirect('/import');
            return;
        }

        $selectedClassId = (int)($this->postRaw('class_id') ?? 0);

        $models = $this->f3->get('models');
        $studentModel = $models['Student'];

        $created = 0;
        $skipped = 0;
        $errors = [];

        $count = count($melliCodes);
        for ($i = 0; $i < $count; $i++) {
            $melliCode = trim((string)($melliCodes[$i] ?? ''));
            $firstName = trim((string)($firstNames[$i] ?? ''));
            $lastName = trim((string)($lastNames[$i] ?? ''));

            if ($melliCode === '' || $firstName === '' || $lastName === '') {
                $skipped++;
                continue;
            }

            if ($studentModel->melliCodeExists($melliCode, 0)) {
                $skipped++;
                continue;
            }

            $classId = $selectedClassId > 0 ? $selectedClassId : 1;

            $studentModel->create([
                'student_id' => $melliCode,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'melli_code' => $melliCode,
                'grade' => '',
                'class_id' => $classId,
            ]);
            $created++;
        }

        $this->render('import/result.htm', [
            'title' => 'نتیجه وارد کردن',
            'help' => 'تعداد دانش‌آموزان ثبت‌شده و رد شده در این صفحه نمایش داده می‌شود.',
            'created' => $created,
            'skipped' => $skipped,
            'errors' => $errors,
        ], 'layout.htm');
    }

}
