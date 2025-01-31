<?php
//
namespace App\Controllers;

use App\DataProcessorClass;
use App\DebuggerClass;
use App\FileHandlerClass;
use Exception;

class CSVController extends DebuggerClass
{
    public function __invoke()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file']) && !is_null($_POST['phar_id'])) {
            try {
                $file = $_FILES['csv_file'];
                $pharId = $_POST['phar_id']; // 入力値を取得する

                // ファイルタイプを検証 (CSV)
                if (!in_array($file['type'], ['text/csv'])) {
                    throw new Exception("無効なファイル タイプです。CSV ファイルのみが許可されます。");
                }

                // ユーザータイミングデータを取得するp
                $fileHandler = new FileHandlerClass();
                $csvData = $fileHandler->readCsv($_FILES['csv_file']['tmp_name']);

                $dataProcessor = new DataProcessorClass($csvData, $pharId);
                // T_UNTファイルを作成する
                $untProcessedData = $dataProcessor->createTUntData();
                $fileHandler->exportCSV($untProcessedData, "storage/{$pharId}/T_UNT.csv");

                // d_timing_facility ファイルを作成する
                $timingFacilityProcessedData = $dataProcessor->createTimingFacilityData();
                $fileHandler->exportTextFile($timingFacilityProcessedData, "storage/{$pharId}/d_timing_facility.txt");

                // d_timing_facility ファイルを作成する
                $timingFacilityDetailProcessedData = $dataProcessor->createTimingFacilityDetailData();
                $fileHandler->exportTextFile($timingFacilityDetailProcessedData, "storage/{$pharId}/d_timing_facility_Detail.txt");

                // 一致しないタイミングデータファイルをログに記録する
                $unmatchedUserData = $dataProcessor->unmatchedUserData();
                $fileHandler->exportTextFile($unmatchedUserData, "storage/{$pharId}/log.txt");

                echo "CSV file generated successfully!";
            } catch (Exception $e) {
                echo "Error: " . $e->getMessage();
            }
        }
    }
}
