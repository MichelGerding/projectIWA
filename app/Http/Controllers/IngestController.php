<?php

namespace App\Http\Controllers;

use App\Models\MeasurementError;
use Illuminate\Database\Eloquent\Collection;
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


            $prevMeasurements = Measurement::query()
                ->where('stn', "=", "" . $measurement["STN"])
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

            // extrapolate the measurement
            $extrapolated = $this->extrapolateMeasurements($prevMeasurement, $data, strtolower($key));
            if ($extrapolated == "None") {
                $extrapolated = null;
            }

            // if there is no data we replace it with the extrapolated value.
            // we also create a new error.
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

            // calculate the 20% margin we allow for the measurement to be valid.
            $margin = abs($extrapolated * 0.2);

            // if the type of measurement is a temperature we check if it is outside the margin.
            // if it is outside we also create a error
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

    /**
     * get the extrapolated value based on the previous measurements
     */
    private function extrapolateMeasurements(&$measurements, $newPoint, $key) {
        $revMeasurements = $measurements->reverse();

        $points = [];
        $firstPointTime = 0;
        foreach ($revMeasurements as $measurement) {
            // get the current hour, minute, day, month, and year
            $pointTime = measurementJsonToUnixTimestamp($measurement);
            if ($firstPointTime == 0) {
                $firstPointTime = $pointTime;
            }

            // store the value in a array so we can use it to extrapolate
            // the first measurement we take will be at time 0 so we subtract the time of the first one.
            $points[] = [$pointTime - $firstPointTime, $measurement[$key]];

        }
        // if there are less then 20 data points the extrapolation wont be precise. 
        // in that case we take the last value we have in the database
        if (count($points) < 20) {
            return $newPoint[$key] ?? $newPoint[strtoupper($key)];
        }

        // extrapolate the measurements.
        return frenchCurveExtrapolation($points, measurementJsonToUnixTimestamp($newPoint)- $firstPointTime);
    }


}

/**
 * get a timestamp from a measurement
 */
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

/**
 * function to calculate the vlue based on french curve extrapolation
 */
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

    // Calculate the slope and intercept of the line of best fit
    $slope = ($n * $sum_xy - $sum_x * $sum_y) / ($n * $sum_xx - $sum_x * $sum_x);
    $intercept = ($sum_y - $slope * $sum_x) / $n;

    // Extrapolate the y-value for the given x-value
    $y_value = $slope * $x_value + $intercept;

    return $y_value;
}

