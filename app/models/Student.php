<?php

namespace App\Models;

class Student extends BaseModel
{
    protected $table = 'students';

    public function all()
    {
        $sql = 'SELECT s.*, c.name AS class_name,
                       CONCAT(s.first_name, " ", s.last_name) AS name
                FROM students s
                LEFT JOIN classes c ON s.class_id = c.id
                ORDER BY s.last_name ASC, s.first_name ASC';
        return $this->db->exec($sql);
    }

    public function allWithClass(?string $search = null)
    {
        $params = [];
        $where = '';

        if ($search !== null && $search !== '') {
            $where = 'WHERE s.name LIKE :search OR s.student_id LIKE :search OR c.name LIKE :search';
            $params['search'] = '%' . $search . '%';
        }

        $sql = 'SELECT s.*, c.name AS class_name,
                       COALESCE(SUM(e.score), 0) AS total_score
                FROM (
                    SELECT id, student_id,
                           CONCAT(first_name, " ", last_name) AS name,
                           first_name, last_name, melli_code, father_name, mother_name,
                           grade, mother_phone, father_phone, address, photo, class_id, created_at
                    FROM students
                ) s
                LEFT JOIN classes c ON s.class_id = c.id
                LEFT JOIN events e ON s.id = e.student_id
                ' . $where . '
                GROUP BY s.id, s.student_id, s.name, s.class_id, s.created_at, c.name
                ORDER BY s.name ASC';
        return $this->db->exec($sql, $params);
    }

    public function search(string $term)
    {
        return $this->allWithClass($term);
    }

    public function find(int $id)
    {
        $sql = 'SELECT s.*, c.name AS class_name,
                       CONCAT(s.first_name, " ", s.last_name) AS name
                FROM students s
                LEFT JOIN classes c ON s.class_id = c.id
                WHERE s.id = :id';
        $result = $this->db->exec($sql, ['id' => $id]);
        return $result[0] ?? null;
    }

    public function studentIdExists(string $studentId, int $ignoreId = 0): bool
    {
        $sql = 'SELECT COUNT(*) AS c FROM students WHERE student_id = :sid AND id != :ignore';
        $result = $this->db->exec($sql, ['sid' => $studentId, 'ignore' => $ignoreId]);
        return (int)$result[0]['c'] > 0;
    }

    public function melliCodeExists(string $melliCode, int $ignoreId = 0): bool
    {
        if ($melliCode === '') {
            return false;
        }
        $sql = 'SELECT COUNT(*) AS c FROM students WHERE melli_code = :code AND id != :ignore';
        $result = $this->db->exec($sql, ['code' => $melliCode, 'ignore' => $ignoreId]);
        return (int)$result[0]['c'] > 0;
    }

    public function getClasses()
    {
        $mapper = $this->mapper('classes');
        return $mapper->select('*', null, 'ORDER BY grade ASC, section ASC');
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

    public function delete(int $id)
    {
        $mapper = $this->mapper($this->table);
        $mapper->load(['id = ?', $id]);
        return $mapper->erase();
    }

    private function assignFields($mapper, array $data): void
    {
        $mapper->student_id = $data['student_id'];
        $mapper->first_name = $data['first_name'];
        $mapper->last_name = $data['last_name'];
        $mapper->melli_code = $data['melli_code'] !== '' ? $data['melli_code'] : null;
        $mapper->father_name = $data['father_name'] !== '' ? $data['father_name'] : null;
        $mapper->mother_name = $data['mother_name'] !== '' ? $data['mother_name'] : null;
        $mapper->grade = $data['grade'] !== '' ? $data['grade'] : null;
        $mapper->mother_phone = $data['mother_phone'] !== '' ? $data['mother_phone'] : null;
        $mapper->father_phone = $data['father_phone'] !== '' ? $data['father_phone'] : null;
        $mapper->address = $data['address'] !== '' ? $data['address'] : null;
        $mapper->photo = $data['photo'] !== '' ? $data['photo'] : null;
        $mapper->class_id = $data['class_id'];
    }

    public function getScore(int $studentId)
    {
        $sql = 'SELECT COALESCE(SUM(score), 0) as total_score FROM events WHERE student_id = :id';
        $result = $this->db->exec($sql, ['id' => $studentId]);
        return $result[0]['total_score'];
    }

    public function getAbsenceCount(int $studentId)
    {
        $sql = 'SELECT COUNT(*) as absence_count
                FROM events e
                INNER JOIN event_types et ON e.event_type_id = et.id
                WHERE e.student_id = :id AND et.name = "غیبت روزانه"';
        $result = $this->db->exec($sql, ['id' => $studentId]);
        return $result[0]['absence_count'];
    }
}
