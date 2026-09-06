<?php

namespace App\Models;

class SchoolClass extends BaseModel
{
    protected $table = 'classes';

    public function all()
    {
        $sql = 'SELECT c.*, COUNT(s.id) AS student_count
                FROM classes c
                LEFT JOIN students s ON s.class_id = c.id
                GROUP BY c.id, c.name, c.grade, c.section, c.created_at
                ORDER BY c.grade ASC, c.section ASC';
        return $this->db->exec($sql);
    }

    public function find(int $id)
    {
        $mapper = $this->mapper($this->table);
        $mapper->load(['id = ?', $id]);
        return $mapper;
    }

    public function create(array $data)
    {
        $mapper = $this->mapper($this->table);
        $mapper->reset();
        $mapper->name = $data['name'];
        $mapper->grade = $data['grade'];
        $mapper->section = $data['section'];
        $mapper->insert();
        return $mapper->get('id');
    }

    public function update(int $id, array $data)
    {
        $mapper = $this->mapper($this->table);
        $mapper->load(['id = ?', $id]);
        $mapper->name = $data['name'];
        $mapper->grade = $data['grade'];
        $mapper->section = $data['section'];
        return $mapper->update();
    }

    public function delete(int $id)
    {
        $mapper = $this->mapper($this->table);
        $mapper->load(['id = ?', $id]);
        return $mapper->erase();
    }

    public function getStudentCount(int $classId)
    {
        $sql = 'SELECT COUNT(*) AS cnt FROM students WHERE class_id = :id';
        $result = $this->db->exec($sql, ['id' => $classId]);
        return (int)($result[0]['cnt'] ?? 0);
    }

    public function findWithStats(int $id)
    {
        $sql = 'SELECT c.*, COUNT(s.id) AS student_count
                FROM classes c
                LEFT JOIN students s ON s.class_id = c.id
                WHERE c.id = :id
                GROUP BY c.id, c.name, c.grade, c.section, c.created_at';
        $result = $this->db->exec($sql, ['id' => $id]);
        return $result[0] ?? null;
    }

    public function students(int $classId)
    {
        $sql = 'SELECT s.*, c.name AS class_name,
                       CONCAT(s.first_name, " ", s.last_name) AS name,
                       COALESCE(t.total_score, 0) AS total_score
                FROM students s
                LEFT JOIN classes c ON s.class_id = c.id
                LEFT JOIN (
                    SELECT student_id, SUM(score) AS total_score
                    FROM events
                    GROUP BY student_id
                ) t ON t.student_id = s.id
                WHERE s.class_id = :id
                ORDER BY s.last_name ASC, s.first_name ASC';
        return $this->db->exec($sql, ['id' => $classId]);
    }
}
