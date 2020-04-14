<?php


namespace Framework\Module\Planner;


use Framework\Core\AbstractClasses\DatabaseModel;
use DateTime;
use DateInterval;
use PDO;
use PHPHtmlParser\Dom;

class PlannerModel extends DatabaseModel
{
    const DEFAULT_DATE = '1900-01-01 00:00:00';
    function getHoliday($country, $year, $updated)
    {
        $result = [];
        $this->updateHolidayInfoIfNecessary($country, $year);
        $lastUpdated = $this->lastUpdate($country, $year);
        if ($updated != $lastUpdated) {
            $select = $this->factory->newSelect();
            $select->cols(['*'])
                ->from('nl_holiday')
                ->where('country = :country')
                ->where('year = :year')
                ->bindValue('country', $country)
                ->bindValue('year', $year);

            $stmt = $this->pdo->prepare($select->getStatement());
            $stmt->execute($select->getBindValues());

            while ($ret = $stmt->fetch(PDO::FETCH_ASSOC)) {
                array_push($result, $ret);
            }
        }
        return $result;
    }

    private function lastUpdate($country, $year) {
        $select = $this->factory->newSelect();
        $select->cols(['max(updated) as latestUpdatedDate'])
            ->from('nl_holiday')
            ->where('country = :country')
            ->where('year = :year')
            ->bindValue('country', $country)
            ->bindValue('year', $year)
        ;
        $stmt = $this->pdo->prepare($select->getStatement());
        $stmt->execute($select->getBindValues());
        $ret = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($ret == null) {
            return self::DEFAULT_DATE;
        } else {
            return $ret['latestUpdatedDate'];
        }
    }

    private function updateHolidayInfoIfNecessary($country, $year) {
        $needUpdate = false;
        $lastUpdate = DateTime::createFromFormat("Y-m-d", $this->lastUpdate($country, $year));
        $now = new DateTime("now");

        if ($lastUpdate == self::DEFAULT_DATE) {
            $needUpdate = true;
        } elseif ($lastUpdate < $now->sub(new DateInterval('P1D'))) {
            $needUpdate = true;
        }

        if ($needUpdate) {
            $data = $this->getHolidayInformationFromTimeAndDate($country, $year);
            $this->deleteAllHoliday($country, $year);
            foreach ($data as $holiday) {
                $values = [];
                $values["country"] = $country;
                $values["year"] = $year;
                $values["date"] = Datetime::createFromFormat("Y m월 d일 H:i:s", sprintf("%s %s 00:00:00", $year, $holiday[0]))
                    ->format("Y-m-d");
                $values["name"] = $holiday[2];
                $values["type"] = $holiday[3];
                $this->insertHoliday($values);
            }
        }
    }

    private function getCountryName($alpha2code) {
        return "south-korea";
    }

    private function getHolidayInformationFromTimeAndDate($country, $year) {
        $data = [];
        $countryName = $this->getCountryName($country);
        $url = sprintf("https://www.timeanddate.com/holidays/%s/%d", $countryName, $year);
        $dom = new Dom;
        $dom->loadFromUrl($url);
        $records = $dom->find("#holidays-table")
            ->find("tbody")
            ->getChildren();
        foreach ($records as $item) {
            $fields = $item->getChildren();
            if (count($fields) > 3) {
                array_push($data, [
                    $fields[0]->text,
                    $fields[1]->text,
                    $fields[2]->find("a")->text,
                    $fields[3]->text
                ]);
            }
        }
        return $data;
    }

    private function deleteAllHoliday($country, $year) {
        $delete = $this->factory->newDelete();
        $delete->from("NL_Holiday")
            ->where('country = :country')
            ->where('year = :year')
            ->bindValue('country', $country)
            ->bindValue('year', $year);
        $stmt = $this->pdo->prepare($delete->getStatement());
        $stmt->execute($delete->getBindValues());
    }

    private function insertHoliday($values) {
        $this->logger->info("New holiday inserted. ".json_encode($values, JSON_UNESCAPED_UNICODE));
        $insert = $this->factory->newInsert();
        $insert->into("nl_holiday")
            ->cols(array_keys($values))
            ->set("updated", "NOW()")
            ->bindValues($values);
        $stmt = $this->pdo->prepare($insert->getStatement());
        $stmt->execute($insert->getBindValues());
    }
}