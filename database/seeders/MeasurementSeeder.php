<?php

namespace Database\Seeders;

use App\Http\Controllers\IngestController;
use App\Models\MeasurementError;
use Illuminate\Database\Seeder;

class MeasurementSeeder extends Seeder
{
    /**
     * generate measurements without using the error validation
     * of the ingress controller but still generate errors
     */
    public function run(): void
    {
        // run for 2 stations
        $iController = new IngestController();

        $datagen = function ($timeInSec) {

            $missing = false;
            $incorrect = null;
            $a = 2 * pi() * $timeInSec;
            $t = 10 * cos($a / (24*60));

            if (rand(0, 100) == 0) {
                $incorrect = $t * 1.35856;
            } else if (rand(0, 100) == 0) {
                $missing=true;
            }

            return [$t, $missing, $incorrect];
        };


        $stations = [];


        $ct = time();

        // generate 267840 datapoints per station
        for($i=0; $i < 24*60 * 3; $i += 10) {
            $temp = $datagen($i);

            $currentT = $ct + ($i * 60);
            $date = date('Y-m-d', $currentT);
            $time = date('H:i:s', $currentT);
            $stations[] = [
                "STN" => '100020',
                "DATE" => $date,
                "TIME" => $time,
                "TEMP" => $temp[0],
                "debug" => $temp
            ];
            $stations[] = [
                "STN" => '724019',
                "DATE" => $date,
                "TIME" => $time,
                "TEMP" => $temp[0] + 17.68,
                "debug" => $temp
            ];
        }

        foreach ($stations as $measurement) {

            $errors = [];
            if ($measurement["debug"][2] !== null) {
                $me = new MeasurementError();
                $me["measurement"] = null;
                $me["error_type"] = 2;
                $me["measurement_type"] = "TEMP";
                $me["value"] = $measurement["debug"][2];
                $errors[] = $me;
            } else if ($measurement["debug"][1]) {
                $me = new MeasurementError();
                $me["measurement"] = null;
                $me["error_type"] = 1;
                $me["measurement_type"] = "TEMP";
                $me["value"] = null;
                $errors[] = $me;
            }

            unset($measurement["debug"]);
            $inserted = $iController->insertData($measurement);

            $iController->insertErrors($errors, $inserted['id']);
        }

    }
}
