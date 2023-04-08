<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Measurement;
use Illuminate\Support\Facades\DB;
use MathPHP\NumericalAnalysis\Interpolation\Interpolation;
use MathPHP\NumericalAnalysis\Interpolation\LagrangePolynomial;

class IngestController extends Controller
{
    private static $measurementFields = [
        "STN", "DATE", "TIME", "TEMP", "DEWP", "STP", "SLP", "VISIB", "WDSP", "PRCP", "SNDP", "FRSHTT", "CLDC", "WNDDIR"
    ];

    public function ingestData(Request $request) {
        $body = $request->getContent();
        $stations = json_decode($body, true)["WEATHERDATA"];


        // validate and insert all the data
        foreach ($stations as $measurement) {

            if ($measurement["STN"] === null) {
                throw new \Exception("No stations provided");
            }


            $prevMeasurements = Measurement::where('stn', "=", "" . $measurement["STN"])
                ->orderByDesc('date')
                ->orderByDesc('time')
                ->limit(30)
                ->get();


            $this->validateData($measurement, $prevMeasurements);

            $this->insertData($measurement);
        }
    }


    public function insertData(array &$data) {
        $measurement = new Measurement();

        foreach ($data as $key => $value) {
            $measurement->$key = $value;
        }

        $measurement->save();
    }



    public function validateData(array &$data, $prevMeasurement) {
        // if the data is "None" then replace it withnull
        foreach ($data as $key => $value) {
            if (in_array($key, ["STN", "DATE", "TIME", "FRSHTT"])) {
                continue;
            }

            $extrapolated = $this->extrapolateMeasurements($prevMeasurement, $data, strtolower($key));
            $extrapolatedMargin = $extrapolated * 0.2;


            if ($key === "temp" and $value === "None") {
                // validate data
                $data[$key] = 0;
                continue;
            }

            if (abs($value - $extrapolated) > $extrapolatedMargin) {
                error_log("invalid");

                $data[$key] = $extrapolated;
            }
        }
    }

    private function extrapolateMeasurements(&$measurements, $newPoint, $key) {
        $points = [];

        $firstPointTime = 0;

        $revMeasurements = $measurements->reverse();


        foreach ($revMeasurements as $measurement) {
            // get the current hour, minute, day, month, and year
            $pointTime = measurementJsonToUnixTimestamp($measurement);
            if ($firstPointTime == 0) {
                $firstPointTime = $pointTime;
            }

            $points[] = [$pointTime - $firstPointTime, $measurement[$key]];

        }

        if (count($points) === 0) {
            echo "no prev data<br>";
            return $newPoint[$key] ?? $newPoint[strtoupper($key)];
        }


//        echo $key;
        $p = lagrange_interpolation($points);
//        $p = LagrangePolynomial::interpolate($points);
        $d = $p(measurementJsonToUnixTimestamp($newPoint) - $firstPointTime);
//        echo $d . " " . measurementJsonToUnixTimestamp($newPoint) - $firstPointTime . "\n";
//        dd($points, $d, measurementJsonToUnixTimestamp($newPoint) - $firstPointTime);


        return $d;


    }


}

function measurementJsonToUnixTimestamp($in): bool|int {
    $measurement = $in;
    if (is_a($in, "Illuminate\\Database\\Eloquent\\Model")) {
        $measurement = $in->attributesToArray();
    }

    $time = $measurement['TIME'] ?? $measurement['time'];
    $date = $measurement['DATE'] ?? $measurement['date'];

    [$hour, $min, $sec] = explode(':', $time);
    [$year, $month, $day] = explode('-', $date);

    return mktime($hour, $min, $sec, $month, $day, $year);
}

function lagrange_interpolation($points) {
    $n = count($points);
    $L = function($k, $x) use ($points, $n) {
        $result = 1;
        for ($i = 0; $i < $n; ++$i) {
            if ($i != $k) {
                $result *= ($x - $points[$i][0]) / ($points[$k][0] - $points[$i][0]);
            }
        }
        return $result;
    };
    $P = function($x) use ($points, $n, $L) {
        $result = 0;
        for ($k = 0; $k < $n; ++$k) {
            $result += $points[$k][1] * $L($k, $x);
        }
        return $result;
    };
    return $P;
}
