<?php

namespace App\Models;

use DB\SQL\Mapper;

class BaseModel
{
    protected $f3;
    protected $db;

    public function __construct()
    {
        $this->f3 = \Base::instance();
        $this->db = $this->f3->get('DB');
    }

    protected function mapper(string $table): Mapper
    {
        return new Mapper($this->db, $table);
    }
}
