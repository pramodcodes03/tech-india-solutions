<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    protected $fillable = ['name', 'state', 'is_active'];

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
