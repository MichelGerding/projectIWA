<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\Measurement;
use App\Models\Station;
use App\Models\Subscription;
use App\Models\SubscriptionType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\UnauthorizedException;

class OutputController extends Controller
{
    public function getData(Request $request) {
        // check if there is a api key

        if (!$request->hasHeader("Authorization")) {
            abort(403);
        };

        // check if there is a subscription with that token
        $subscription = Subscription::query()
            ->where('apikey', '=', $request->header("Authorization"))
            ->first();

        if ($subscription == null) {
            abort(403);
        }

        // check the type of account and handle its type
        $sType = SubscriptionType::query()
            ->find($subscription->subscription_type)["type"];

        return match ($sType) {
            "subscription_periodic" => $this->getFromDisk($subscription),
            "subscription_live" => $this->getFromDatabase($subscription),
            "contract" => $this->getContracted($subscription),
            default => dd($sType)
        };

    }

    function getFromDisk($subscription): string {
        // return the file
        $s = Storage::disk('weatherData')->get($subscription->apikey. ".json");

        if ($s == null) {
            return '{"measurements": []}';
        }

        return $s;
    }

    function getFromDatabase($subscription): string {
        $measurement = Subscription::query()
            ->join("subscription_station", 'subscription.id', '=', 'subscription_station.subscription')
            ->join("measurement", 'subscription_station.station', '=', 'measurement.stn')
            ->where("subscription.id", '=' , $subscription->id)
            ->limit(1)
            ->get([
                "measurement.stn",
                "measurement.date",
                "measurement.time",
                "measurement.temp",
                "measurement.dewp",
                "measurement.stp",
                "measurement.slp",
                "measurement.visib",
                "measurement.wdsp",
                "measurement.prcp",
                "measurement.sndp",
                "measurement.frshtt",
                "measurement.cldc",
                "measurement.wnddir"
            ]);

        return json_encode([
            "measurements" => $measurement
        ]);
    }

    function getContracted($subscription): string {

        // get the contract info
        $contract = Contract::query()
            ->where("subscription", '=', $subscription->id)
            ->first();

        $filters = json_decode($contract->station_selection_json, true)["filters"];

        $where = [];
        $what = [];

        foreach ($filters as $filter) {

            // get the where
            if (in_array($filter["type"], [
                "country",
                "region",
                "coords"]) ) {
                $where = $filter;
            }

            if ($filter["type"] == "measurement") {
                $what = $filter;
            }
        }

        $stations = [];
        if ($where["type"] == "country") {
            // find the stations
            $stationsIds = DB::table("nearestlocation")
                ->select("station_name")
                ->where("country_code", "=", $where["value"])
                ->get();
        }  else if ($where["type"] == "region") {
            $stationsIds = DB::table("nearestlocation")
                ->select("station_name")
                ->where("administrative_region1", "=", $where["value"])
                ->where("administrative_region2", "=", $where["value"], 'or')
                ->get();
        } else if($where["type"] == "coords") {
            // distance is in meters

            $coordsS = explode(',', $where["value"]);

            $coords = [
                floatval($coordsS[0]),
                floatval($coordsS[1])
            ];

            $coordDiff = $where["range"] / 111_195;
            $q = DB::table("nearestlocation")
                ->select("station_name")
                ->whereBetween("latitude", [$coords[0] - $coordDiff, $coords[0] + $coordDiff])
                ->whereBetween("longitude", [$coords[1] - $coordDiff, $coords[1] + $coordDiff]);

            $stationsIds = $q->get();
        }

        // get the measuremets we want

        $mTypes = $what["value"];
        $mTypes[] = "date";
        $mTypes[] = "time";


        $measurements = [];
        foreach ($stationsIds as $stn) {
            $measurements[$stn->station_name] = Measurement::query()
                ->where("stn", '=', $stn->station_name)
                ->orderByDesc('date')
                ->orderByDesc('time')
                ->limit(1)
                ->get($mTypes);
        }

        return json_encode($measurements);
    }
}
