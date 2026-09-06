<?php

namespace App\Models;

class Action extends BaseModel
{
    protected $table = 'actions';

    public function all()
    {
        $sql = 'SELECT a.*, CONCAT(s.first_name, " ", s.last_name) AS student_name, et.name AS event_type_name,
                       u.name AS created_by_name
                FROM actions a
                LEFT JOIN students s ON a.student_id = s.id
                LEFT JOIN events e ON a.event_id = e.id
                LEFT JOIN event_types et ON e.event_type_id = et.id
                LEFT JOIN users u ON a.created_by = u.id
                ORDER BY a.created_at DESC';
        return $this->db->exec($sql);
    }

    public function find(int $id)
    {
        $sql = 'SELECT a.*, CONCAT(s.first_name, " ", s.last_name) AS student_name, s.student_id,
                       u.name AS created_by_name
                FROM actions a
                LEFT JOIN students s ON a.student_id = s.id
                LEFT JOIN users u ON a.created_by = u.id
                WHERE a.id = :id';
        $result = $this->db->exec($sql, ['id' => $id]);
        return $result[0] ?? null;
    }

    public function getOpenRecords()
    {
        $sql = 'SELECT a.*, CONCAT(s.first_name, " ", s.last_name) AS student_name, e.score, et.is_positive
                FROM actions a
                LEFT JOIN students s ON a.student_id = s.id
                LEFT JOIN events e ON a.event_id = e.id
                LEFT JOIN event_types et ON e.event_type_id = et.id
                WHERE a.status = "open"
                  AND a.action_type IN ("parent_meeting", "mentor_referral")
                ORDER BY a.created_at ASC';
        return $this->db->exec($sql);
    }

    /**
     * Students that currently have an open parent-meeting or
     * mentor-referral action (shown in the home sidebar).
     */
    public function getStudentsWithFollowUp()
    {
        $sql = 'SELECT s.id AS student_id_pk, s.student_id,
                       CONCAT(s.first_name, " ", s.last_name) AS student_name,
                       c.name AS class_name,
                       GROUP_CONCAT(a.action_type SEPARATOR ",") AS action_types,
                       COUNT(a.id) AS action_count,
                       MAX(a.created_at) AS latest_action_at
                FROM actions a
                INNER JOIN students s ON a.student_id = s.id
                LEFT JOIN classes c ON s.class_id = c.id
                WHERE a.status = "open"
                  AND a.action_type IN ("parent_meeting", "mentor_referral")
                GROUP BY s.id, s.student_id, s.first_name, s.last_name, c.name
                ORDER BY latest_action_at DESC';
        return $this->db->exec($sql);
    }

    public function getByStudent(int $studentId)
    {
        $sql = 'SELECT a.*, e.score, et.is_positive
                FROM actions a
                LEFT JOIN events e ON a.event_id = e.id
                LEFT JOIN event_types et ON e.event_type_id = et.id
                WHERE a.student_id = :id
                ORDER BY a.created_at DESC';
        return $this->db->exec($sql, ['id' => $studentId]);
    }

    public function create(array $data)
    {
        $mapper = $this->mapper($this->table);
        $mapper->reset();
        $mapper->event_id = $data['event_id'] ?? null;
        $mapper->student_id = $data['student_id'];
        $mapper->action_type = $data['action_type'];
        $mapper->title = $data['title'];
        $mapper->description = $data['description'];
        $mapper->is_automatic = $data['is_automatic'] ?? 0;
        $mapper->score_threshold_min = $data['score_threshold_min'] ?? null;
        $mapper->score_threshold_max = $data['score_threshold_max'] ?? null;
        $mapper->status = $data['status'] ?? 'open';
        $mapper->result = $data['result'] ?? null;
        $mapper->action_date = $data['action_date'] ?? null;
        $mapper->created_by = $data['created_by'];
        $mapper->insert();
        return $mapper->get('id');
    }

    public function update(int $id, array $data)
    {
        $mapper = $this->mapper($this->table);
        $mapper->load(['id = ?', $id]);
        $mapper->event_id = $data['event_id'] ?? null;
        $mapper->student_id = $data['student_id'];
        $mapper->action_type = $data['action_type'];
        $mapper->title = $data['title'];
        $mapper->description = $data['description'];
        $mapper->is_automatic = $data['is_automatic'] ?? 0;
        $mapper->score_threshold_min = $data['score_threshold_min'] ?? null;
        $mapper->score_threshold_max = $data['score_threshold_max'] ?? null;
        $mapper->status = $data['status'] ?? 'open';
        $mapper->result = $data['result'] ?? null;
        $mapper->action_date = $data['action_date'] ?? null;
        $mapper->completed_date = $data['completed_date'] ?? null;
        return $mapper->update();
    }

    public function complete(int $id, string $result = null)
    {
        $mapper = $this->mapper($this->table);
        $mapper->load(['id = ?', $id]);
        $mapper->status = 'completed';
        $mapper->result = $result;
        $mapper->completed_date = date('Y-m-d');
        return $mapper->update();
    }

    public function delete(int $id)
    {
        $mapper = $this->mapper($this->table);
        $mapper->load(['id = ?', $id]);
        return $mapper->erase();
    }

    public function getForClass(int $classId, int $limit = 10)
    {
        $sql = 'SELECT a.*, CONCAT(s.first_name, " ", s.last_name) AS student_name
                FROM actions a
                INNER JOIN students s ON a.student_id = s.id
                WHERE s.class_id = :class_id
                ORDER BY a.status ASC, a.created_at DESC
                LIMIT :limit';
        return $this->db->exec($sql, ['class_id' => $classId, 'limit' => $limit]);
    }
}
