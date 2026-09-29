<?php

namespace App\Models;

class Attendance extends BaseModel
{
    protected $table = 'attendance';

    public function allWithFilters(?string $classId = null, ?string $date = null, ?string $status = null)
    {
        $params = [];
        $where = 'WHERE 1=1';

        if ($classId !== null && $classId !== '') {
            $where .= ' AND s.class_id = :class_id';
            $params['class_id'] = (int)$classId;
        }

        if ($date !== null && $date !== '') {
            $mysqlDate = \App\Helpers\JalaliDate::parse($date);
            if ($mysqlDate !== null) {
                $where .= ' AND a.attendance_date = :date';
                $params['date'] = $mysqlDate;
            }
        }

        if ($status !== null && $status !== '') {
            $where .= ' AND a.status = :status';
            $params['status'] = $status;
        }

        $sql = 'SELECT a.*,
                       CONCAT(s.first_name, " ", s.last_name) AS student_name,
                       s.student_id,
                       s.photo,
                       c.name AS class_name,
                       c.grade,
                       c.teacher_name,
                       CONCAT(u.first_name, " ", u.last_name) AS recorded_by_name
                FROM attendance a
                INNER JOIN students s ON a.student_id = s.id
                LEFT JOIN classes c ON s.class_id = c.id
                LEFT JOIN users u ON a.recorded_by = u.id
                ' . $where . '
                ORDER BY a.attendance_date DESC, s.last_name ASC, s.first_name ASC';

        return $this->db->exec($sql, $params);
    }

    public function findByDateAndClass(string $date, int $classId)
    {
        $mysqlDate = \App\Helpers\JalaliDate::parse($date);
        if ($mysqlDate === null) {
            return [];
        }

        $sql = 'SELECT a.*,
                       CONCAT(s.first_name, " ", s.last_name) AS student_name,
                       s.student_id,
                       s.photo
                FROM attendance a
                INNER JOIN students s ON a.student_id = s.id
                WHERE a.attendance_date = :date AND s.class_id = :class_id
                ORDER BY s.last_name ASC, s.first_name ASC';

        return $this->db->exec($sql, ['date' => $mysqlDate, 'class_id' => $classId]);
    }

    public function findOne(int $id)
    {
        $sql = 'SELECT a.*,
                       CONCAT(s.first_name, " ", s.last_name) AS student_name,
                       s.student_id,
                       c.name AS class_name,
                       CONCAT(u.first_name, " ", u.last_name) AS recorded_by_name
                FROM attendance a
                INNER JOIN students s ON a.student_id = s.id
                LEFT JOIN classes c ON s.class_id = c.id
                LEFT JOIN users u ON a.recorded_by = u.id
                WHERE a.id = :id';

        $result = $this->db->exec($sql, ['id' => $id]);
        return $result[0] ?? null;
    }

    public function recordExists(int $studentId, string $date): bool
    {
        $mysqlDate = \App\Helpers\JalaliDate::parse($date);
        if ($mysqlDate === null) {
            return false;
        }

        $sql = 'SELECT COUNT(*) AS c FROM attendance WHERE student_id = :sid AND attendance_date = :date';
        $result = $this->db->exec($sql, ['sid' => $studentId, 'date' => $mysqlDate]);
        return (int)$result[0]['c'] > 0;
    }

    public function create(array $data)
    {
        $mapper = $this->mapper($this->table);
        $mapper->reset();
        $this->assignFields($mapper, $data);
        $mapper->insert();
        return $mapper->get('id');
    }

    public function update(int $id, array $data)
    {
        $mapper = $this->mapper($this->table);
        $mapper->load(['id = ?', $id]);
        $this->assignFields($mapper, $data);
        return $mapper->update();
    }

    public function updateByStudentAndDate(int $studentId, string $date, array $data)
    {
        $mysqlDate = \App\Helpers\JalaliDate::parse($date);
        $mapper = $this->mapper($this->table);
        $mapper->load(['student_id = ? AND attendance_date = ?', $studentId, $mysqlDate]);
        $this->assignFields($mapper, $data);
        return $mapper->update();
    }

    public function delete(int $id)
    {
        $mapper = $this->mapper($this->table);
        $mapper->load(['id = ?', $id]);
        return $mapper->erase();
    }

    public function getStats(?string $classId = null, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $params = [];
        $where = 'WHERE 1=1';

        if ($classId !== null && $classId !== '') {
            $where .= ' AND s.class_id = :class_id';
            $params['class_id'] = (int)$classId;
        }

        if ($dateFrom !== null && $dateFrom !== '') {
            $mysqlFrom = \App\Helpers\JalaliDate::parse($dateFrom);
            if ($mysqlFrom !== null) {
                $where .= ' AND a.attendance_date >= :date_from';
                $params['date_from'] = $mysqlFrom;
            }
        }

        if ($dateTo !== null && $dateTo !== '') {
            $mysqlTo = \App\Helpers\JalaliDate::parse($dateTo);
            if ($mysqlTo !== null) {
                $where .= ' AND a.attendance_date <= :date_to';
                $params['date_to'] = $mysqlTo;
            }
        }

        $sql = 'SELECT a.status, COUNT(*) AS count
                FROM attendance a
                INNER JOIN students s ON a.student_id = s.id
                ' . $where . '
                GROUP BY a.status';

        $results = $this->db->exec($sql, $params);

        $stats = [
            'present' => 0,
            'absent' => 0,
            'late' => 0,
            'excused' => 0,
            'total' => 0,
        ];

        foreach ($results as $row) {
            $stats[$row['status']] = (int)$row['count'];
            $stats['total'] += (int)$row['count'];
        }

        return $stats;
    }

    private function assignFields($mapper, array $data): void
    {
        $mapper->student_id = (int)$data['student_id'];
        $mapper->attendance_date = \App\Helpers\JalaliDate::parse($data['attendance_date']);
        $mapper->status = $data['status'];
        $mapper->period = $data['period'] !== '' ? $data['period'] : null;
        $mapper->notes = $data['notes'] !== '' ? $data['notes'] : null;
        $mapper->recorded_by = (int)$data['recorded_by'];
    }
}
