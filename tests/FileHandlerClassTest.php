<?php

use PHPUnit\Framework\TestCase;
use App\FileHandlerClass;

class FileHandlerClassTest extends TestCase
{
    protected $fileHandler;
    protected $tempFile;

    protected function setUp(): void
    {
        $this->fileHandler = new FileHandlerClass();
        $this->tempFile = tempnam(sys_get_temp_dir(), 'test_csv');
    }

    // test for csv data read function
    public function test_data_read_CSV()
    {
        // Properly insert the CSV content with correct values
        $csvData = "用法名称,p1,p2,緑 p3,p4,,線幅,線種,印字値,LED色\n";
        $csvData .= "おきた時,0,0,0,0,普通,実線,0005\n";

        file_put_contents($this->tempFile, $csvData);

        // Read the CSV file
        $result = $this->fileHandler->readCsv($this->tempFile);

        // Assert the expected output
        $this->assertArrayHasKey('0005', $result);
        $this->assertEquals('おきた時', $result['0005']['title']);  // Correct the expected value here
        $this->assertEquals('0005', $result['0005']['print_value']);

        // Clean up
        unlink($this->tempFile);
    }

    // test exported csv data
    public function test_data_of_export_CSV()
    {
        // Prepare data to export
        $data = [
            ["1001", "1", "起床時薬", "0600", "0600", "8201", "9999999999"],
            ["1002", "1", "朝食前", "0645", "0645", "8202", "9999999999"]
        ];

        // Export the CSV
        $this->fileHandler->exportCSV($data, $this->tempFile);

        // Read the file content and remove the trailing newline
        $fileContent = rtrim(file_get_contents($this->tempFile), "\n");
        $expectedContent = "1001,1,起床時薬,0600,0600,8201,9999999999\n1002,1,朝食前,0645,0645,8202,9999999999";

        $this->assertSame($expectedContent, $fileContent, 'The CSV file content should match the expected content');
    }

    // Test exported file data
    public function test_data_of_export_text()
    {
        // Prepare data to export
        $data = [
            "INSERT INTO m_facility_timings (origin_type, facility_id, pharmacy_id, input_type, code, name, input_code, report_group_id, created, updated) VALUES (2, 101, 100, 1, '0600', '起床時薬', '8201', 10, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP);",
            "INSERT INTO m_facility_timings (origin_type, facility_id, pharmacy_id, input_type, code, name, input_code, report_group_id, created, updated) VALUES (2, 101, 100, 1, '0645', '朝食前', '8202', 21, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP);"
        ];

        // Call the exportTextFile method
        $this->fileHandler->exportTextFile($data, $this->tempFile);

        // Read the content of the file
        $fileContent = file_get_contents($this->tempFile);

        // Define the expected content
        $expectedContent = "INSERT INTO m_facility_timings (origin_type, facility_id, pharmacy_id, input_type, code, name, input_code, report_group_id, created, updated) VALUES (2, 101, 100, 1, '0600', '起床時薬', '8201', 10, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP);" . PHP_EOL .
            "INSERT INTO m_facility_timings (origin_type, facility_id, pharmacy_id, input_type, code, name, input_code, report_group_id, created, updated) VALUES (2, 101, 100, 1, '0645', '朝食前', '8202', 21, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP);" . PHP_EOL;

        // Assert that the file content matches the expected content
        $this->assertEquals($expectedContent, $fileContent, 'The content of the text file should match the expected content');
    }

    // Test the delete directory function
    public function test_delete_directory()
    {
        // Create a temporary directory with files
        $dirPath = sys_get_temp_dir() . '/test_dir';
        mkdir($dirPath);
        file_put_contents($dirPath . '/T_UNT.csv', 'Content 1');
        file_put_contents($dirPath . '/log.txt', 'Content 2');

        // Ensure the directory exists before deletion
        $this->assertTrue(is_dir($dirPath));

        // Delete the directory
        $this->fileHandler->deleteDirectory($dirPath);

        // Assert the directory no longer exists
        $this->assertFalse(is_dir($dirPath));
    }

    protected function tearDown(): void
    {
        // Clean up: Delete the temporary file
        unlink($this->tempFile);
    }
}
