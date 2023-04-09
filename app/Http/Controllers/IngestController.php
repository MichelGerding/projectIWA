<?php

namespace App\Http\Controllers;

use App\Models\MeasurementError;
use Illuminate\Http\Request;
use App\Models\Measurement;
use MathPHP\NumericalAnalysis\Interpolation\Interpolation;
use MathPHP\NumericalAnalysis\Interpolation\NevillesMethod;
use MathPHP\NumericalAnalysis\Interpolation\NewtonPolynomialForward;
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


            $errors = [];
            $this->validateData($measurement, $prevMeasurements, $errors);

            $inserted = $this->insertData($measurement);

            $this->insertErrors($errors, $inserted['id']);
        }
    }


    public function insertErrors(array $errors, int $measurementId) {
        foreach ($errors as $error) {
            $error->measurement = $measurementId;
            $error->save();
        }

    }

    public function insertData(array &$data) {
        $measurement = new Measurement();

        foreach ($data as $key => $value) {
            $measurement->$key = $value;
        }

        $measurement->save();
        return $measurement;
    }



    public function validateData(array &$data, $prevMeasurement, &$errors) {
        // if the data is "None" then replace it with null
        foreach ($data as $key => $value) {
            if (in_array($key, ["STN", "DATE", "TIME", "FRSHTT"])) {
                continue;
            }

            $extrapolated = $this->extrapolateMeasurements($prevMeasurement, $data, strtolower($key));
            if ($extrapolated == "None") {
                $extrapolated = null;
            }

            if ($value === "None") {
                // validate data
                $data[$key] = $extrapolated;

                $me = new MeasurementError();
                $me["measurement"] = null;
                $me["error_type"] = 1;
                $me["measurement_type"] = $key;
                $me["value"] = null;
                $errors[] = $me;

                continue;
            }

            $margin = abs($extrapolated * 0.2);

            if ($key == "TEMP" && (abs($value - $extrapolated) > $margin)) {
                error_log("out of range, val: $value, extrapolated: $extrapolated, diff: "  . abs($value - $extrapolated) .", margin: $margin");

                $data[$key] = $extrapolated;

                $me = new MeasurementError();
                $me["measurement"] = null;
                $me["error_type"] = 2;
                $me["measurement_type"] = $key;
                $me["value"] = $value;
                $errors[] = $me;
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
        if (count($points) < 3) {
            return $newPoint[$key] ?? $newPoint[strtoupper($key)];
        }



        return NevillesMethod::interpolate(measurementJsonToUnixTimestamp($newPoint) - $firstPointTime, $points);
//        $P = NewtonPolynomialForward::interpolate($points);
//        return $P(measurementJsonToUnixTimestamp($newPoint) - $firstPointTime);
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

function frenchCurveExtrapolation($data, $x_value) {
    $n = count($data);
    $sum_x = $sum_y = $sum_xx = $sum_xy = 0;

    // Calculate the sums of x, y, x^2, and xy
    for ($i = 0; $i < $n; $i++) {
        $sum_x += $data[$i][0];
        $sum_y += $data[$i][1];
        $sum_xx += $data[$i][0] * $data[$i][0];
        $sum_xy += $data[$i][0] * $data[$i][1];
    }
    echo "<pre>";
    var_dump($data);
    // Calculate the slope and intercept of the line of best fit
    $slope = ($n * $sum_xy - $sum_x * $sum_y) / ($n * $sum_xx - $sum_x * $sum_x);
    $intercept = ($sum_y - $slope * $sum_x) / $n;

    // Extrapolate the y-value for the given x-value
    $y_value = $slope * $x_value + $intercept;

    return $y_value;
}


//use MathPHP\NumericalAnalysis\Spline;

//function cubic_spline_extrapolation($x, $y, $x_new) {
//    // Calculate the cubic spline coefficients
//    $spline = Spline::fromArrays($x, $y);
//
//    // Extrapolate the y-values for the new x-values
//    $y_new = [];
//    foreach ($x_new as $xn) {
//        $y_new[] = $spline->interpolate($xn);
//    }
//
//    return $y_new;
//}
