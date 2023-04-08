<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// Schema::create('meseaurement', function (Blueprint $table) {
//     $table->id();
//     $table->string('station', 10);

//     $table->date('date');
//     $table->time('time');

//     // measurements
//     $table->float('temp', 5, 2);
//     $table->float('dewp');
//     $table->float('stp');
//     $table->float('slp');
//     $table->float('visib');
//     $table->float('wdsp');
//     $table->float('prcp');
//     $table->float('sndp');
//     $table->float('frshtt');
//     $table->float('cldc');
//     $table->float('wnddir');


//     // foreign keys
//     $table->foreign('station')->references('name')->on('station');

//     // indexes
//     $table->index(['station', 'date', 'time']);
//     $table->index(['station', 'date']);
//     $table->index(['date', 'time']);
// });

class Measurement extends Model
{
    use HasFactory;

    protected $table = 'measurement';

    protected $fillable = [
        'stn',
        'date',
        'time',
        'temp',
        'dewp',
        'stp',
        'slp',
        'visib',
        'wdsp',
        'prcp',
        'sndp',
        'frshtt',
        'cldc',
        'wnddir',
    ];

    public $timestamps = false;

    public function setData($key, $value)
    {
        $this->$key = $value;
    }
}
