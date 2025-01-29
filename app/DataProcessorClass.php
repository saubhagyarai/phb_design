<?php

namespace App;


class DataProcessorClass extends DebuggerClass
{

    private $masterData;
    private $inputCSV;
    private $pharId;

    public function __construct($inputCSV, $pharId)
    {
        $this->inputCSV = $inputCSV;
        $this->pharId = $pharId;
        $this->loadMasterTimingData();
    }

    // Loads master timing file
    private function loadMasterTimingData()
    {
        $this->masterData = $this->readMasterTimingCsv('config/master_timing_data.csv');
    }

    // Reads Master Timing data from config/master_timing_data.csv file
    public function readMasterTimingCsv($filePath)
    {
        $data = [];
        $firstRow = true; // Flag to track the first row

        if (($handle = fopen($filePath, 'r')) !== FALSE) {
            while (($row = fgetcsv($handle, 1000, ',', '"', '\\')) !== FALSE) {
                // Discard the first row
                if ($firstRow) {
                    $firstRow = false;
                    continue; // Skip to the next iteration of the loop
                }
                $data[] = [
                    "title" => $row[0],
                    "code" => $row[1],
                    "start_time" => $row[2],
                    "end_time" => $row[3],
                    "print_code" => $row[4],
                    "print_group" => $row[5],
                ];
            }
            fclose($handle);
        }

        return $data;
    }

    // Generate T UNT data
    public function createTUntData()
    {
        $comparedData = $this->compareUserDataWithMasterData();

        $wrtDataMaster = [];
        $wrtDataUser = [];

        // Process master data
        foreach ([1 => 1001, 2 => 2001] as $type => $snStart) {
            $sn = $snStart;
            $pharId = 9999999999;
            foreach ($this->masterData as $value) {
                $wrtDataMaster[] = [
                    $sn,
                    $type,
                    $value["title"],
                    $value["code"],
                    $value["code"],
                    $value["print_code"],
                    $pharId
                ];
                $sn++;
            }
        }

        // Process user data
        foreach ([1 => 3001, 2 => 4001] as $type => $snStart) {
            $sn = $snStart;
            $pharId = $this->pharId;
            foreach ($comparedData as $value) {
                $wrtDataUser[] = [
                    $sn,
                    $type,
                    $value["user"]["title"],
                    $value["user"]["print_value"],
                    $value["master"]["code"],
                    $value["master"]["print_code"],
                    $pharId
                ];
                $sn++;
            }
        }

        // Merge master and user data
        return array_merge($wrtDataMaster, $wrtDataUser);
    }

    // Generates Timing Facility Date
    public function createTimingFacilityData()
    {
        $facId = 101;
        $defaultPharId = 100;

        // Initialize arrays to store queries for default and dynamic pharmacy
        $defaultPharQueries = [];
        $dynamicPharQueries = [];

        // Loop through master data to populate variables and build queries
        foreach ($this->masterData as $value) {
            $code = $value["code"];
            $name = $value["title"];
            $input_code = $value["print_code"];
            $groupId = $value["print_group"];

            // Construct the SQL query for default pharmacy
            $defaultPharQueries[] = "INSERT INTO m_facility_timings (origin_type, facility_id, pharmacy_id, input_type, code, name, input_code, report_group_id, created, updated)
                                     VALUES (2, $facId, $defaultPharId, 1, '$code', '$name', '$input_code', $groupId, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP);";

            // Construct the SQL query for specific pharmacy
            $pharId = $this->pharId;
            $dynamicPharQueries[] = "INSERT INTO m_facility_timings (origin_type, facility_id, pharmacy_id, input_type, code, name, input_code, report_group_id, created, updated)
                                     VALUES (2, $facId, $pharId, 1, '$code', '$name', '$input_code', $groupId, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP);";
        }

        // Merge both queries
        return array_merge($defaultPharQueries, $dynamicPharQueries);
    }

    // Generates Timing Facility Detail Data
    public function createTimingFacilityDetailData()
    {
        $comparedData = $this->compareUserDataWithMasterData();

        $facId = 101;
        $pharId = $this->pharId;
        $code = "";
        $name = "";
        $input_code = "";
        $groupId = "";

        // Initialize an array to store SQL queries
        $sqlQueries = [];

        // Loop through master data to populate variables and build queries
        foreach ($comparedData as $value) {
            $code = $value["user"]["print_value"];
            $name = $value["user"]["title"];
            $input_code = $value["master"]["print_code"];
            $groupId = $value["master"]["print_group"];

            // Construct the SQL query and store it in the array
            $sqlQueries[] = "INSERT INTO m_facility_timings_Details (origin_type, facility_id, pharmacy_id, input_type, code, name, input_code, report_group_id, created, updated)
                     VALUES (2, $facId, $pharId, 1, '$code', '$name', '$input_code', $groupId, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP);";
        }

        return $sqlQueries;
    }

    // Compares User Timing Data with Master Timing Data and Maps according to their matching data
    public function compareUserDataWithMasterData()
    {
        $comparedData = [];
        foreach ($this->inputCSV as $key => $row) {
            $userTime = $row['user_time'];
            foreach ($this->masterData as $master) {
                // Compare user time with master code
                // If user time and master time is similar
                if ($userTime === $master['code']) {
                    // Merge data into a new array
                    $comparedData[$key] = [
                        "user" => $row,
                        "master" => [
                            "title" => $master['title'],
                            "code" => $master['code'],
                            "print_code" => $master['print_code'],
                            "print_group" => $master['print_group'],
                        ],
                    ];
                }
            }
        }

        return $comparedData;
    }
}
