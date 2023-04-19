<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Station extends Model
{
    public $table = "station";

    protected $fillable = [
        'name',
        'longitude',
        'latitude',
        'elevation',
    ];

    public $timestamps = false;

}
