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
                $pharId = $_POST['phar_id']; // Get the input value

                // Validate file type (CSV)
                if (!in_array($file['type'], ['text/csv'])) {
                    throw new Exception("Invalid file type. Only CSV files are allowed.");
                }

                // Get User timing data
                $fileHandler = new FileHandlerClass();
                $csvData = $fileHandler->readCsv($_FILES['csv_file']['tmp_name']);

                $dataProcessor = new DataProcessorClass($csvData, $pharId);
                // Create T_UNT file
                $untProcessedData = $dataProcessor->createTUntData();
                $fileHandler->exportCSV($untProcessedData, "storage/{$pharId}/T_UNT.csv");

                // Create d_timing_facility file
                $timingFacilityProcessedData = $dataProcessor->createTimingFacilityData();
                $fileHandler->exportTextFile($timingFacilityProcessedData, "storage/{$pharId}/d_timing_facility.csv");

                // Create d_timing_facility_Detail file
                $timingFacilityDetailProcessedData = $dataProcessor->createTimingFacilityDetailData();
                $fileHandler->exportTextFile($timingFacilityDetailProcessedData, "storage/{$pharId}/d_timing_facility_Detail.csv");

                echo "CSV file generated successfully!";
            } catch (Exception $e) {
                echo "Error: " . $e->getMessage();
            }
        }
    }
}
