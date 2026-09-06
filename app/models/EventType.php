<?php

namespace App\Models;

class EventType extends BaseModel
{
    protected $table = 'event_types';

    public function all()
    {
        $sql = 'SELECT * FROM event_types ORDER BY is_positive DESC, default_score DESC';
        return $this->db->exec($sql);
    }

    public function find(int $id)
    {
        $mapper = $this->mapper($this->table);
        $mapper->load(['id = ?', $id]);
        return $mapper;
    }

    public function getPositive()
    {
        $mapper = $this->mapper($this->table);
        return $mapper->select('*', ['is_positive = ?', 1], 'ORDER BY default_score DESC');
    }

    public function getNegative()
    {
        $mapper = $this->mapper($this->table);
        return $mapper->select('*', ['is_positive = ?', 0], 'ORDER BY default_score ASC');
    }

    public function create(array $data)
    {
        $mapper = $this->mapper($this->table);
        $mapper->reset();
        $mapper->name = $data['name'];
        $mapper->description = $data['description'];
        $mapper->default_score = $data['default_score'];
        $mapper->is_positive = $data['is_positive'];
        $mapper->insert();
        return $mapper->get('id');
    }

    public function update(int $id, array $data)
    {
        $mapper = $this->mapper($this->table);
        $mapper->load(['id = ?', $id]);
        $mapper->name = $data['name'];
        $mapper->description = $data['description'];
        $mapper->default_score = $data['default_score'];
        $mapper->is_positive = $data['is_positive'];
        return $mapper->update();
    }

    public function delete(int $id)
    {
        $mapper = $this->mapper($this->table);
        $mapper->load(['id = ?', $id]);
        return $mapper->erase();
    }
}
