<?php

namespace Database\Seeders;

use App\Http\Controllers\IngestController;
use App\Models\MeasurementError;
use Illuminate\Database\Seeder;

class MeasurementSeeder extends Seeder
{
    /**
     * generate measurements without using the error validation
     * of the ingress controller but still generate errors.
     * 
     * we bypass the error handeling to prevent useless values
     */
    public function run(): void
    {
        // get a instance of the controller so we can use its functions
        $iController = new IngestController();

    


        $stations = [];


        $ct = time();

        // generate 432 datapoints per station. one per 10 minutes
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

        // save the measurements and errors to the database
        foreach ($stations as $measurement) {

            // check if there are any errors in the data.
            $errors = [];
            if ($measurement["debug"][2] !== null) {
                // invalid data
                $me = new MeasurementError();
                $me["measurement"] = null;
                $me["error_type"] = 2;
                $me["measurement_type"] = "TEMP";
                $me["value"] = $measurement["debug"][2];
                $errors[] = $me;
            } else if ($measurement["debug"][1]) {
            // missing data
            $me = new MeasurementError();
                $me["measurement"] = null;
                $me["error_type"] = 1;
                $me["measurement_type"] = "TEMP";
                $me["value"] = null;
                $errors[] = $me;
            }

            // remove the debug value from the data
            unset($measurement["debug"]);

            // insert the measurements and errors.
            $inserted = $iController->insertData($measurement);
            $iController->insertErrors($errors, $inserted['id']);
        }

    }

    /**
     * generate data based on a sine wave.
     * also generates missing and invalid measurements 
     */
    function generateData ($timeInSec) {

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
}
