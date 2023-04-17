<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contract extends Model
{

    public $table = "contract";
    public $timestamps = false;



    // example json data
    /**
{
    "modifiers": [
        {
            "type": "country",
            "value": "NL"
        },
        {
            "type": "region",
            "value": "France métropolitaine"
        },
        {
            "type": "coords",
            "value": "48.856614,2.3522219",
            "range": 100
        },
        {
            "type": "elevation",
            "value": 100,
            "range": 100
        },
        {
            "type": "measurement",
            "value": [
                "temp", "dewp", "visib", "winddir", "wdsp"
            ]
        }
    ]
}
     */


}
