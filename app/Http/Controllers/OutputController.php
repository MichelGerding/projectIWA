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

        // check the type of subscription and passs it to the appropriate function 
        return match ($sType) {
            "subscription_periodic" => $this->getFromDisk($subscription),
            "subscription_live" => $this->getFromDatabase($subscription),
            "contract" => $this->getContracted($subscription),
            default => dd($sType)
        };

    }

    function getFromDisk($subscription): string {
        // read the file from disk
        $s = Storage::disk('weatherData')->get($subscription->apikey. ".json");

        // if it doesnt exist then we return a empty object
        if ($s == null) {
            return '{"measurements": []}';
        }

        return $s;
    }

    function getFromDatabase($subscription): string {
        // get the last measurement from the database for the selected station
        $measurement = Subscription::query()
            ->join("subscription_station", 'subscription.id', '=', 'subscription_station.subscription')
            ->join("measurement", 'subscription_station.station', '=', 'measurement.stn')
            ->where("subscription.id", '=' , $subscription->id)
            ->limit(1)
            ->get(["measurement.*"]);

        return json_encode([
            "measurements" => $measurement
        ]);
    }

    /**
     * get the measurements for the contract
     */
    function getContracted($subscription): string {

        // get the contract assigned to this subscription
        $contract = Contract::query()
            ->where("subscription", '=', $subscription->id)
            ->first();

        // get the applied filters
        $filters = json_decode($contract->station_selection_json, true)["filters"];

        $where = [];
        $what = [];

        // match the to the appropriate variable
        foreach ($filters as $filter) {

            // get the filter to the stations
            if (in_array($filter["type"], [
                "country",
                "region",
                "coords"]) ) {
                $where = $filter;
            }

            // get the filter to the variables
            if ($filter["type"] == "measurement") {
                $what = $filter;
            }
        }

        // match the station filter to the type of filter we have
        $stations = [];
        if ($where["type"] == "country") {
            // filter on country
            $stationsIds = DB::table("nearestlocation")
                ->select("station_name")
                ->where("country_code", "=", $where["value"])
                ->get();
        }  else if ($where["type"] == "region") {
            // filter on region
            $stationsIds = DB::table("nearestlocation")
                ->select("station_name")
                ->where("administrative_region1", "=", $where["value"])
                ->where("administrative_region2", "=", $where["value"], 'or')
                ->get();
        } else if($where["type"] == "coords") {
            // filter on coordinates
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

        // define the required measurements
        $mTypes = $what["value"];
        $mTypes[] = "date";
        $mTypes[] = "time";

        // get the measurements for all stations and save it in a array.
        $measurements = [];
        foreach ($stationsIds as $stn) {
            $measurements[$stn->station_name] = Measurement::query()
                ->where("stn", '=', $stn->station_name)
                ->orderByDesc('date')
                ->orderByDesc('time')
                ->limit(1)
                ->get($mTypes);
        }

        // return the json
        return json_encode($measurements);
    }
}
