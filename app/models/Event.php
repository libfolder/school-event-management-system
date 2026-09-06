<?php

namespace App\Models;

class Event extends BaseModel
{
    protected $table = 'events';

    public function all()
    {
        $sql = 'SELECT e.*, CONCAT(s.first_name, " ", s.last_name) AS student_name,
                       et.name AS event_type_name, et.is_positive,
                       u.name AS teacher_name
                FROM events e
                LEFT JOIN students s ON e.student_id = s.id
                LEFT JOIN event_types et ON e.event_type_id = et.id
                LEFT JOIN users u ON e.teacher_id = u.id
                ORDER BY e.event_date DESC, e.created_at DESC';
        return $this->db->exec($sql);
    }

    public function find(int $id)
    {
        $sql = 'SELECT e.*, CONCAT(s.first_name, " ", s.last_name) AS student_name,
                       et.name AS event_type_name, et.is_positive,
                       s.student_id, et.default_score,
                       u.name AS teacher_name
                FROM events e
                LEFT JOIN students s ON e.student_id = s.id
                LEFT JOIN event_types et ON e.event_type_id = et.id
                LEFT JOIN users u ON e.teacher_id = u.id
                WHERE e.id = :id';
        $result = $this->db->exec($sql, ['id' => $id]);
        return $result[0] ?? null;
    }

    public function getByStudent(int $studentId)
    {
        $sql = 'SELECT e.*, et.name AS event_type_name, et.is_positive, u.name AS teacher_name
                FROM events e
                LEFT JOIN event_types et ON e.event_type_id = et.id
                LEFT JOIN users u ON e.teacher_id = u.id
                WHERE e.student_id = :id
                ORDER BY e.event_date DESC, e.created_at DESC';
        return $this->db->exec($sql, ['id' => $studentId]);
    }

    public function create(array $data)
    {
        $mapper = $this->mapper($this->table);
        $mapper->reset();
        $mapper->student_id = $data['student_id'];
        $mapper->event_type_id = $data['event_type_id'];
        $mapper->score = $data['score'];
        $mapper->description = $data['description'];
        $mapper->teacher_id = $data['teacher_id'];
        $mapper->event_date = $data['event_date'];
        $mapper->insert();
        return $mapper->get('id');
    }

    public function update(int $id, array $data)
    {
        $mapper = $this->mapper($this->table);
        $mapper->load(['id = ?', $id]);
        $mapper->student_id = $data['student_id'];
        $mapper->event_type_id = $data['event_type_id'];
        $mapper->score = $data['score'];
        $mapper->description = $data['description'];
        $mapper->teacher_id = $data['teacher_id'];
        $mapper->event_date = $data['event_date'];
        return $mapper->update();
    }

    public function delete(int $id)
    {
        $mapper = $this->mapper($this->table);
        $mapper->load(['id = ?', $id]);
        return $mapper->erase();
    }

    public function getStudentsByScore($limit = 20, $positive = true)
    {
        $sign = $positive ? '>=' : '<=';
        $order = $positive ? 'DESC' : 'ASC';
        $sql = 'SELECT s.id, s.student_id, CONCAT(s.first_name, " ", s.last_name) AS name, c.name AS class_name,
                       COALESCE(SUM(e.score), 0) AS total_score,
                       COUNT(e.id) AS event_count
                FROM students s
                LEFT JOIN classes c ON s.class_id = c.id
                LEFT JOIN events e ON s.id = e.student_id AND e.score > 0
                WHERE 1=1
                GROUP BY s.id, s.student_id, s.first_name, s.last_name, c.name
                HAVING total_score > 0
                ORDER BY total_score ' . $order . '
                LIMIT :limit';
        return $this->db->exec($sql, ['limit' => $limit]);
    }

    public function getStudentsByScoreRaw($limit = 20, $positive = true)
    {
        $order = $positive ? 'DESC' : 'ASC';
        $sql = 'SELECT s.id, s.student_id, CONCAT(s.first_name, " ", s.last_name) AS name, c.name AS class_name,
                       COALESCE(SUM(e.score), 0) AS total_score,
                       COUNT(e.id) AS event_count
                FROM students s
                LEFT JOIN classes c ON s.class_id = c.id
                LEFT JOIN events e ON s.id = e.student_id AND e.score ' . ($positive ? '> 0' : '< 0') . '
                GROUP BY s.id, s.student_id, s.first_name, s.last_name, c.name
                HAVING total_score ' . ($positive ? '> 0' : '< 0') . '
                ORDER BY total_score ' . $order . '
                LIMIT :limit';
        return $this->db->exec($sql, ['limit' => $limit]);
    }

    public function topAbsentees(int $limit = 20)
    {
        $sql = 'SELECT s.id, s.student_id, CONCAT(s.first_name, " ", s.last_name) AS name, c.name AS class_name,
                       COUNT(e.id) AS absence_count
                FROM students s
                LEFT JOIN classes c ON s.class_id = c.id
                INNER JOIN events e ON s.id = e.student_id
                     AND e.event_type_id = (SELECT id FROM event_types WHERE name = "غیبت روزانه" LIMIT 1)
                GROUP BY s.id, s.student_id, s.first_name, s.last_name, c.name
                HAVING absence_count > 0
                ORDER BY absence_count DESC
                LIMIT :limit';
        return $this->db->exec($sql, ['limit' => $limit]);
    }

    public function getClassSummary()
    {
        $sql = 'SELECT c.id, c.name, c.grade, c.section,
                       COUNT(DISTINCT s.id) AS student_count,
                       COUNT(e.id) AS event_count,
                       COALESCE(SUM(e.score), 0) AS total_score,
                       COALESCE(SUM(CASE WHEN e.score > 0 THEN e.score ELSE 0 END), 0) AS positive_score,
                       COALESCE(SUM(CASE WHEN e.score < 0 THEN e.score ELSE 0 END), 0) AS negative_score
                FROM classes c
                LEFT JOIN students s ON s.class_id = c.id
                LEFT JOIN events e ON s.id = e.student_id
                GROUP BY c.id, c.name, c.grade, c.section
                ORDER BY c.grade ASC, c.section ASC';
        return $this->db->exec($sql);
    }

    public function getStudentsByScoreForClass(int $classId)
    {
        $sql = 'SELECT s.id, s.student_id, CONCAT(s.first_name, " ", s.last_name) AS name,
                       COALESCE(SUM(e.score), 0) AS total_score,
                       COUNT(e.id) AS event_count
                FROM students s
                LEFT JOIN events e ON s.id = e.student_id
                WHERE s.class_id = :class_id
                GROUP BY s.id, s.student_id, s.first_name, s.last_name
                ORDER BY total_score DESC';
        return $this->db->exec($sql, ['class_id' => $classId]);
    }

    public function getAttendanceForClass(int $classId)
    {
        $sql = 'SELECT s.id, s.student_id, CONCAT(s.first_name, " ", s.last_name) AS name,
                       COALESCE(SUM(CASE WHEN et.name = "غیبت روزانه" THEN 1 ELSE 0 END), 0) AS absence_count,
                       COALESCE(SUM(CASE WHEN et.name = "تاخیر ورود به مدرسه" THEN 1 ELSE 0 END), 0) AS delay_count
                FROM students s
                LEFT JOIN events e ON s.id = e.student_id
                LEFT JOIN event_types et ON e.event_type_id = et.id
                WHERE s.class_id = :class_id
                GROUP BY s.id, s.student_id, s.first_name, s.last_name
                HAVING absence_count > 0 OR delay_count > 0
                ORDER BY absence_count DESC, delay_count DESC';
        return $this->db->exec($sql, ['class_id' => $classId]);
    }

    public function getEventsForClass(int $classId, int $limit = 8)
    {
        $sql = 'SELECT e.id, CONCAT(s.first_name, " ", s.last_name) AS student_name,
                       et.name AS event_type_name, e.score, e.description, e.event_date
                FROM events e
                INNER JOIN students s ON e.student_id = s.id
                LEFT JOIN event_types et ON e.event_type_id = et.id
                WHERE s.class_id = :class_id AND e.score < 0
                ORDER BY e.event_date DESC, e.created_at DESC
                LIMIT :limit';
        return $this->db->exec($sql, ['class_id' => $classId, 'limit' => $limit]);
    }

    public function getEventsForClassByTypes(int $classId, array $typeNames, int $limit = 8)
    {
        $placeholders = [];
        foreach ($typeNames as $i => $name) {
            $placeholders[] = ':t' . $i;
        }
        $params = ['class_id' => $classId, 'limit' => $limit];
        foreach ($typeNames as $i => $name) {
            $params['t' . $i] = $name;
        }

        $sql = 'SELECT e.id, CONCAT(s.first_name, " ", s.last_name) AS student_name,
                       et.name AS event_type_name, e.score, e.description, e.event_date
                FROM events e
                INNER JOIN students s ON e.student_id = s.id
                INNER JOIN event_types et ON e.event_type_id = et.id
                WHERE s.class_id = :class_id
                  AND et.name IN (' . implode(', ', $placeholders) . ')
                ORDER BY e.event_date DESC, e.created_at DESC
                LIMIT :limit';
        return $this->db->exec($sql, $params);
    }

    public function getPositiveEventsForClass(int $classId, int $limit = 8)
    {
        $sql = 'SELECT e.id, CONCAT(s.first_name, " ", s.last_name) AS student_name,
                       et.name AS event_type_name, e.score, e.description, e.event_date
                FROM events e
                INNER JOIN students s ON e.student_id = s.id
                INNER JOIN event_types et ON e.event_type_id = et.id
                WHERE s.class_id = :class_id AND e.score > 0
                ORDER BY e.event_date DESC, e.created_at DESC
                LIMIT :limit';
        return $this->db->exec($sql, ['class_id' => $classId, 'limit' => $limit]);
    }

    /* =========================================================
       Statistics analysis: aggregates + auto-generated findings
       and recommendations based on recorded data.
       ========================================================= */

    public function getAnalysisStats(): array
    {
        $stats = [];

        // Score distribution + averages
        $row = $this->db->exec(
            'SELECT COUNT(*) AS event_count,
                    COALESCE(SUM(score), 0) AS total_score,
                    COALESCE(SUM(CASE WHEN score > 0 THEN score ELSE 0 END), 0) AS positive_score,
                    COALESCE(SUM(CASE WHEN score < 0 THEN score ELSE 0 END), 0) AS negative_score,
                    COALESCE(AVG(score), 0) AS avg_score
             FROM events'
        )[0];
        $stats['score'] = array_map(fn($v) => is_string($v) ? $v : (float)$v, $row);

        // Students distribution
        $row = $this->db->exec(
            'SELECT COUNT(*) AS student_count,
                    SUM(total > 0) AS positive_students,
                    SUM(total < 0) AS negative_students,
                    SUM(total = 0) AS zero_students
             FROM (SELECT s.id, COALESCE(SUM(e.score), 0) AS total
                   FROM students s
                   LEFT JOIN events e ON e.student_id = s.id
                   GROUP BY s.id) t'
        )[0];
        $stats['students'] = array_map(fn($v) => (int)$v, $row);

        // Students without any recorded event
        $row = $this->db->exec(
            'SELECT COUNT(*) AS inactive
             FROM students s
             WHERE NOT EXISTS (SELECT 1 FROM events e WHERE e.student_id = s.id)'
        )[0];
        $stats['students']['inactive'] = (int)$row['inactive'];

        // Most frequent event types
        $stats['topTypes'] = $this->db->exec(
            'SELECT et.name, et.is_positive, et.default_score,
                    COUNT(e.id) AS usage_count,
                    COALESCE(SUM(e.score), 0) AS score_sum
             FROM event_types et
             LEFT JOIN events e ON e.event_type_id = et.id
             GROUP BY et.id, et.name, et.is_positive, et.default_score
             HAVING usage_count > 0
             ORDER BY usage_count DESC, score_sum ASC
             LIMIT 10'
        );

        // Absence totals
        $row = $this->db->exec(
            'SELECT COUNT(*) AS absence_count
             FROM events e
             WHERE e.event_type_id = (SELECT id FROM event_types WHERE name = "غیبت روزانه" LIMIT 1)'
        )[0];
        $stats['absences'] = ['count' => (int)$row['absence_count']];

        // Open follow-up actions
        $row = $this->db->exec(
            'SELECT COUNT(*) AS open_count,
                    SUM(action_type = "parent_meeting") AS parent_meetings,
                    SUM(action_type = "mentor_referral") AS mentor_referrals
             FROM actions
             WHERE status = "open"'
        )[0];
        $stats['actions'] = array_map(fn($v) => (int)$v, $row);

        return $stats;
    }

    /**
     * Analyze the recorded statistics, draw conclusions and
     * propose actionable solutions for detected problems.
     */
    public function analyze(): array
    {
        $stats = $this->getAnalysisStats();
        $findings = [];

        $eventCount = $stats['score']['event_count'];
        $total = $stats['score']['total_score'];
        $posScore = $stats['score']['positive_score'];
        $negScore = abs($stats['score']['negative_score']);
        $avg = $stats['score']['avg_score'];
        $studentCount = $stats['students']['student_count'];
        $posStudents = $stats['students']['positive_students'];
        $negStudents = $stats['students']['negative_students'];
        $inactive = $stats['students']['inactive'];
        $absences = $stats['absences']['count'];
        $openActions = $stats['actions']['open_count'];

        // 1) Overall balance
        $balance = $posScore > 0 ? round(($posScore - $negScore) / max($posScore, $negScore) * 100) : -100;
        if ($negScore > $posScore) {
            $findings[] = [
                'level' => 'danger',
                'icon' => '⚠️',
                'title' => 'غلبه رویدادهای منفی بر مثبت',
                'text' => 'جمع امتیاز منفی (' . $negScore . ') از جمع امتیاز مثبت (' . $posScore . ') بیشتر است. '
                    . 'میزان عدم توازن حدود ' . abs($balance) . '% است؛ '
                    . 'یعنی رویه ثبت، بیشتر متوجه تخلفات بوده تا تشویق‌ها.',
                'solutions' => [
                    'برای هر دانش‌آموز منفی، حداقل یک رویداد مثبت (تشویق، پیشرفت درسی) نیز ثبت شود تا توازن برقرار بماند.',
                    'در جلسه معاونت آموزشی، هدف «نسبت امتیاز مثبت به منفی حداقل ۱ به ۱» برای ماه آینده تصویب شود.',
                ],
            ];
        } elseif ($posScore > $negScore) {
            $findings[] = [
                'level' => 'success',
                'icon' => '✅',
                'title' => 'توازن مثبت رویدادها',
                'text' => 'امتیازهای مثبت (' . $posScore . ') بر منفی (' . $negScore . ') غلبه دارد؛ '
                    . 'روند ثبت رویدادها به سمت تشویق و تقویت رفتار مطلوب است.',
                'solutions' => [
                    'الگوی فعلی ثبت تشویق‌ها ادامه یافته و در گفت‌وگوی ماهانه با مشاور مدرسه بازبینی شود.',
                ],
            ];
        }

        // 2) Low average / weak monitoring
        if ($studentCount > 0) {
            $perStudent = round($eventCount / $studentCount, 1);
            if ($perStudent < 3) {
                $findings[] = [
                    'level' => 'warning',
                    'icon' => '📉',
                    'title' => 'پوشش ناکافی ثبت رویداد',
                    'text' => 'میانگین ثبت رویداد برای هر دانش‌آموز فقط ' . $perStudent . ' مورد است؛ '
                        . 'با این حجم، ردیابی روند رفتاری هر دانش‌آموز ممکن نیست.',
                    'solutions' => [
                        'ثبت رویداد به چک‌لیست روزانه معلمان اضافه شود (مثلاً دو رویداد روزانه برای هر کلاس).',
                        'معلمان موظف شوند هفتگی حداقل یک رویداد برای هر دانش‌آموز ثبت کنند.',
                    ],
                ];
            } else {
                $findings[] = [
                    'level' => 'info',
                    'icon' => '📈',
                    'title' => 'پوشش قابل قبول ثبت رویداد',
                    'text' => 'میانگین ' . $perStudent . ' رویداد برای هر دانش‌آموز ثبت شده است.',
                    'solutions' => [
                        'کیفیت توضیحات (description) رویدادها به‌صورت نمونه‌ای بازبینی شود تا در تحلیل‌های بعدی قابل استفاده باشد.',
                    ],
                ];
            }
        }

        // 3) Unrecorded (inactive) students
        if ($inactive > 0) {
            $findings[] = [
                'level' => 'warning',
                'icon' => '👤',
                'title' => 'دانش‌آموزان بدون هیچ رویداد ثبت‌شده',
                'text' => $inactive . ' دانش‌آموز از ' . $studentCount . ' نفر هیچ رویدادی (نه مثبت و نه منفی) ندارند؛ '
                    . 'این دانش‌آموزان در سیستم «نامرئی» هستند و افت یا پیشرفت آن‌ها ردیابی نمی‌شود.',
                'solutions' => [
                    'فهرست این دانش‌آموزان به مشاور مدرسه ارسال شود تا با مصاحبه کوتاه وضعیت آن‌ها مشخص شود.',
                    'برای هر دانش‌آموز بدون رویداد، معلم کلاس موظف به ثبت حداقل یک «حضور به‌موقع» یا رویداد مشابه در هفته باشد.',
                ],
            ];
        }

        // 4) Negative-score concentration
        if ($negStudents > 0) {
            $ratio = round($negStudents / $studentCount * 100);
            $topNeg = $this->getStudentsByScoreRaw(2, false);
            $names = [];
            foreach ($topNeg as $s) {
                $names[] = $s['name'] . ' (' . $s['class_name'] . ') با ' . $s['event_count'] . ' رویداد منفی';
            }
            $findings[] = [
                'level' => $ratio >= 30 ? 'danger' : 'info',
                'icon' => '🎯',
                'title' => 'تمرکز امتیاز منفی روی تعداد کمی از دانش‌آموزان',
                'text' => $negStudents . ' دانش‌آموز (' . $ratio . '% کل) دارای امتیاز منفی هستند. '
                    . 'بیشترین حجم امتیاز منفی: ' . ($names ? implode(' و ', $names) : '-') . '.',
                'solutions' => [
                    'برای دانش‌آموزان اول فهرست منفی، جلسه مشاوره فردی (mentor_referral) در هفته جاری تشکیل شود.',
                    'ریشه غیبت‌های تکراری (غیبت روزانه، بیشترین رویداد ثبت‌شده) با تماس والدین بررسی شود.',
                ],
            ];
        }

        // 5) Open actions backlog
        if ($openActions > 0) {
            $findings[] = [
                'level' => 'warning',
                'icon' => '📋',
                'title' => 'اقدامات پیگیری باز',
                'text' => $openActions . ' اقدام (احضار والدین / معرفی به مشاور) در وضعیت «باز» مانده و نتیجه‌ای ثبت نشده است.',
                'solutions' => [
                    'مسئول پیگیری هر اقدام مشخص و مهلت یک‌هفته‌ای برای ثبت نتیجه تعیین شود.',
                    'در داشبورد خانه، ستون «روزهای بازمانده» برای هر اقدام اضافه شود تا اقدامات معوق برجسته شوند.',
                ],
            ];
        }

        // 6) Absence volume
        if ($absences >= 5) {
            $findings[] = [
                'level' => 'danger',
                'icon' => '🕐',
                'title' => 'حجم بالای غیبت روزانه',
                'text' => $absences . ' مورد غیبت روزانه (هر مورد ۱۵- امتیاز) ثبت شده که بیشترین سهم امتیاز منفی را دارد.',
                'solutions' => [
                    'الگوی غیبت‌ها (روز هفته / درس خاص) از طریق فیلتر گزارش کلاس استخراج شود.',
                    'سیستم تلفنی یادآوری غیبت برای والدین فعال شود.',
                ],
            ];
        }

        return [
            'stats' => $stats,
            'findings' => $findings,
            'summary' => [
                'event_count' => $eventCount,
                'total_score' => $total,
                'avg_score' => round($avg, 1),
                'per_student' => $studentCount > 0 ? round($eventCount / $studentCount, 1) : 0,
                'balance' => $balance,
            ],
        ];
    }
}
