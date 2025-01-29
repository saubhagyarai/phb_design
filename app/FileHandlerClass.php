<?php

namespace App;

class FileHandlerClass
{

    // Reads the csv data of user timing
    public function readCsv($filePath)
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
                // Combine checks for empty row and "?" symbol
                if (!empty($row) && $row[1] !== '?') {
                    $formattedValue = str_pad($row[1], 4, '0', STR_PAD_LEFT); // Add leading zeros
                    $formattedValue1 = str_pad($row[8], 4, '0', STR_PAD_LEFT); // Add leading zeros

                    $data[$formattedValue1] = [
                        "title" => $row[0],
                        "user_time" => $formattedValue,
                        "print_value" => $formattedValue1
                    ];
                }
            }
            fclose($handle);
        }
        return $data;
    }

    // Exports CSV file
    public function exportCSV($data, $filePath)
    {
        $this->writeCsv($filePath, $data);
    }

    // Exports txt file
    public function exportTextFile($data, $filePath)
    {
        $this->writeFile($filePath, $data);
    }

    // Writes data in csv file
    public function writeCsv($filePath, $data)
    {
        // Get the directory path from the file path
        $dirPath = dirname($filePath);

        // Check if directory exists and delete it before recreating
        if (is_dir($dirPath)) {
            // Recursively delete the directory and its contents
            $this->deleteDirectory($dirPath);
        }

        // Create the directory again
        if (!mkdir($dirPath, 0775, true) && !is_dir($dirPath)) {
            throw new \Exception("Failed to create directory: $dirPath. Check permissions.");
        }

        // Open the file for writing (this returns a resource)
        $file = fopen($filePath, 'w');

        // Check if file was successfully opened
        if ($file === false) {
            throw new \Exception("Failed to open file: $filePath");
        }

        // Write CSV data
        foreach ($data as $row) {
            fputcsv($file, $row, ',', '"', '\\'); // Explicitly pass the $escape parameter
        }

        // Close the file
        fclose($file);
    }

    // Writes data in txt file
    public function writeFile($filePath, $data)
    {
        // Open the file for writing (this returns a resource)
        $file = fopen($filePath, 'w');

        // Check if file was successfully opened
        if ($file === false) {
            throw new \Exception("Failed to open file: $filePath");
        }

        // Write each SQL statement to the file
        foreach ($data as $statement) {
            fwrite($file, $statement . PHP_EOL);
        }
        // Close the file
        fclose($file);
    }

    // delete the directory and its contents
    private function deleteDirectory($dirPath)
    {
        // Ensure directory is not empty before deleting
        foreach (glob($dirPath . '/*') as $file) {
            if (is_dir($file)) {
                $this->deleteDirectory($file); // Recursive call to delete subdirectories
            } else {
                unlink($file); // Delete file
            }
        }
        rmdir($dirPath); // Remove the empty directory
    }
}
