<?php
namespace Framework\Module\Planner;

use Framework\Core\AbstractClasses\Controller;
use Respect\Validation\Exceptions\NestedValidationException;
use Respect\Validation\Validator as v;

class PlannerController extends Controller
{
    /**
     *
     */
    function getHolidayAction() {
        // get variables
        $country = $this->getParamValue(['country', [$this->route_params, $_GET], 'KR']);
        $year = $this->getParamValue(['year', [$this->route_params, $_GET], date("Y")]);
        $updated = $this->getParamValue(['updated', [$this->route_params, $_GET], date("Y-m-d H:i:s")]);

        // validation
        try {
            v::countryCode()->assert($country);
            v::alnum()->between(1900, 2100)->assert($year);
            v::dateTime("Y-m-d H:i:s")->assert($updated);
        } catch(NestedValidationException $exception) {
            echo json_encode(["Status" => "Error", "ErrorMessage" => $exception->getFullMessage()]);
        }

        // get data from model
        $model = new PlannerModel();
        $result = $model->getHoliday($country, $year, $updated);

        // return
        echo
            json_encode([
            "Status" => "Success",
            "Condition" => [
                "Country" => $country,
                "Year" => $year
            ],
            "Data" => $result]);
    }
}