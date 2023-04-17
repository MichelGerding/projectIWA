<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\HasApiTokens;

class Subscription extends Model
{
    use HasFactory;

    protected $table = "subscription";

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'subscription_type',
        'apikey',
    ];

    public function stations(): Collection|array
    {
        return Subscription::query()
            ->join("subscription_station", 'subscription.id', '=', 'subscription_station.subscription')
            ->join("station", 'station.name', '=', 'subscription_station.station')
            ->where("subscription.id", '=', $this->id)
            ->get(["station.*"]);
    }
}
