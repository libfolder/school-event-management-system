<?php

namespace App\Models;

class User extends BaseModel
{
    public function getMapper()
    {
        return $this->mapper('users');
    }
}
