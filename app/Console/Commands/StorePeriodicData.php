<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class StorePeriodicData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:store-periodic-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'save the periodic data to disk';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $subs = Subscription::query()
            ->join("subscription_type", 'subscription.subscription_type', '=', 'subscription_type.id')
            ->where("subscription_type.type", 'like', '%periodic')
            ->get(["subscription.*"]);

        // get the data for each subscription and svae to disk
        foreach ($subs as $sub) {
            // seconds*minutes*hours*days
            $lookBackSeconds = 60*60*12;

            $currentDate = date('Y-m-d', time());
            $lastWeekDate = date('Y-m-d', time() - $lookBackSeconds);

//            $q = $sub->join("subscription_station", "subscription.id", '=', 'subscription_station.subscription')
//                ->join('measurement', 'subscription_station.station', '=', 'measurement.stn')
//                ->whereBetween("timestamp(measurement.date, measurement.time)", ["DATE_ADD(now(), inteval -12 hour)","now()"])
////                ->get(["measurement.*"])
///
///
            ;
//            var_dump($q->toSql());
//            $q->get(["measurement.*"]);
            $measurements = DB::select("
select m.* from subscription_station ss
    inner join measurement m on ss.station = m.stn
where
    ss.subscription = ". $sub["id"]." and
    timestamp(m.date, m.time) between DATE_ADD(now(), interval -12 hour) and now();
");


            // transform the data

            // filter out null valls

            $a = json_decode(json_encode($measurements), true);
            for($i = 0; $i < count($a); $i++) {
                foreach ($a[$i] as $k => $v) {
                    if ($v == null) {
                        unset($a[$i][$k]);
                    }
                }
            }

            // insert data into array
            $diskData = [
                "measurements" => $a
            ];



            Storage::disk('weatherData')->put($sub["apikey"] . ".json", json_encode($diskData));
        }

    }
}
