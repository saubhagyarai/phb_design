<?php

namespace App;

require_once __DIR__ . '/../config/conf.php'; // Load the file

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

    // マスタータイミングファイルを読み込む
    private function loadMasterTimingData()
    {
        $this->masterData = $this->readMasterTimingCsv('config/master_timing_data.csv');
    }

    // config/master_timing_data.csvファイルからマスタータイミングデータを読み取る
    public function readMasterTimingCsv($filePath)
    {
        $data = [];
        $firstRow = true; // 最初の行を追跡するフラグ

        if (($handle = fopen($filePath, 'r')) !== FALSE) {
            while (($row = fgetcsv($handle, 1000, ',', '"', '\\')) !== FALSE) {
                // 最初の行を破棄する
                if ($firstRow) {
                    $firstRow = false;
                    continue; // ループの次の反復にスキップ
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

    // T UNTデータを生成する
    public function createTUntData()
    {
        $comparedData = $this->compareUserDataWithMasterData();

        $wrtDataMaster = [];
        $wrtDataUser = [];

        // マスターデータを処理する
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

        // ユーザーデータを処理する
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

        // マスターデータとユーザーデータを結合する
        return array_merge($wrtDataMaster, $wrtDataUser);
    }

    // タイミング施設データを生成する
    public function createTimingFacilityData()
    {
        $facId = 101;
        $defaultPharId = 100;

        // デフォルト薬局と動的薬局のクエリを格納する配列を初期化
        $defaultPharQueries = [];
        $dynamicPharQueries = [];

        // マスターデータをループして変数を設定し、クエリを構築
        foreach ($this->masterData as $value) {
            $code = $value["code"];
            $name = $value["title"];
            $input_code = $value["print_code"];
            $groupId = $value["print_group"];

            // デフォルト薬局用のSQLクエリを構築
            $defaultPharQueries[] = "INSERT INTO m_facility_timings (origin_type, facility_id, pharmacy_id, input_type, code, name, input_code, report_group_id, created, updated) VALUES (2, $facId, $defaultPharId, 1, '$code', '$name', '$input_code', $groupId, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP);";

            // 特定の薬局用のSQLクエリを構築
            $pharId = $this->pharId;
            $dynamicPharQueries[] = "INSERT INTO m_facility_timings (origin_type, facility_id, pharmacy_id, input_type, code, name, input_code, report_group_id, created, updated) VALUES (2, $facId, $pharId, 1, '$code', '$name', '$input_code', $groupId, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP);";
        }

        // 両方のクエリを結合
        return array_merge($defaultPharQueries, $dynamicPharQueries);
    }

    // タイミング施設詳細データを生成する
    public function createTimingFacilityDetailData()
    {
        $comparedData = $this->compareUserDataWithMasterData();

        $facId = 101;
        $pharId = $this->pharId;
        $code = "";
        $name = "";
        $input_code = "";
        $groupId = "";

        // SQLクエリを格納する配列を初期化
        $sqlQueries = [];

        // マスターデータをループして変数を設定し、クエリを構築
        foreach ($comparedData as $value) {
            $code = $value["user"]["print_value"];
            $name = $value["user"]["title"];
            $input_code = $value["master"]["print_code"];
            $groupId = $value["master"]["print_group"];

            // SQLクエリを構築し、配列に格納
            $sqlQueries[] = "INSERT INTO m_facility_timings_Details (origin_type, facility_id, pharmacy_id, input_type, code, name, input_code, report_group_id, created, updated) VALUES (2, $facId, $pharId, 1, '$code', '$name', '$input_code', $groupId, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP);";
        }

        return $sqlQueries;
    }

    // ユーザータイミングデータとマスタータイミングデータを比較し、一致するデータに従ってマッピングする
    public function compareUserDataWithMasterData()
    {
        $comparedData = [];
        // Loop the UserTimingData
        foreach ($this->inputCSV as $key => $row) {
            $enUserTitle = mb_convert_encoding($row["title"], "UTF-8", "SJIS");
            // Compare user time with Master time
            foreach ($this->masterData as $master) {
                $enMasterTitle = mb_convert_encoding($master["title"], "UTF-8", "SJIS");
                // ユーザー時間とマスターコードを比較
                if ($enUserTitle == $enMasterTitle) {
                    // データを新しい配列にマージ
                    $comparedData[$key] = [
                        "user" => $row,
                        "master" => [
                            "title" => $master['title'],
                            "code" => $master['code'],
                            "print_code" => $master['print_code'],
                            "print_group" => $master['print_group'],
                        ],
                    ];
                    continue;
                }

                if (isset(ADJUSTED_TIMING[$enMasterTitle])) {
                    foreach (ADJUSTED_TIMING[$enMasterTitle] as $value) {
                        // ユーザー時間とマスターコードを比較
                        if ($enUserTitle == $value) {
                            // データを新しい配列にマージ
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
            }
        }
        return $comparedData;
    }
}
