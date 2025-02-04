<?php

use PHPUnit\Framework\TestCase;
use App\FileHandlerClass;

require_once 'bootstrap.php';

class FileHandlerClassTest extends TestCase
{
    protected $fileHandler;
    protected $tempFile;
    protected $tempDir;

    protected function setUp(): void
    {
        $this->fileHandler = new FileHandlerClass();
        $this->tempFile = tempnam(sys_get_temp_dir(), 'test_csv');
        $this->tempDir = sys_get_temp_dir() . '/test_dir';

        // Ensure the directory exists for cleanup during tests
        if (!is_dir($this->tempDir)) {
            mkdir($this->tempDir);
        }
    }

    // Suppress specific warnings related to file handling
    public function test_data_read_CSV()
    {
        $csvData = "用法名称,p1,p2,緑 p3,p4,,線幅,線種,印字値,LED色\n";
        $csvData .= "おきた時,0,0,0,0,普通,実線,0005\n";

        file_put_contents($this->tempFile, $csvData);

        // Suppress warnings when reading CSV
        $result = @file_get_contents($this->tempFile);

        if ($result !== false) {
            $result = $this->fileHandler->readCsv($this->tempFile);
        }

        $this->assertArrayHasKey('0005', $result);
        $this->assertEquals('おきた時', $result['0005']['title']);
        $this->assertEquals('0005', $result['0005']['print_value']);

        // Clean up
        if (is_writable($this->tempFile)) {
            unlink($this->tempFile);
        }
    }

    public function test_data_of_export_CSV()
    {
        // Prepare data to export
        $data = [
            ["1001", "1", "起床時薬", "0600", "0600", "8201", "9999999999"],
            ["1002", "1", "朝食前", "0645", "0645", "8202", "9999999999"]
        ];

        // Suppress any warnings during the CSV export process
        $this->fileHandler->exportCSV($data, $this->tempFile);

        $fileContent = @file_get_contents($this->tempFile);
        $fileContent = rtrim($fileContent, "\n");

        $expectedContent = "1001,1,起床時薬,0600,0600,8201,9999999999\n1002,1,朝食前,0645,0645,8202,9999999999";
        $this->assertSame(rtrim($expectedContent, "\n"), rtrim($fileContent, "\n"));
    }

    public function test_data_of_export_text()
    {
        $data = [
            "INSERT INTO m_facility_timings (origin_type, facility_id, pharmacy_id, input_type, code, name, input_code, report_group_id, created, updated) VALUES (2, 101, 100, 1, '0600', '起床時薬', '8201', 10, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP);",
            "INSERT INTO m_facility_timings (origin_type, facility_id, pharmacy_id, input_type, code, name, input_code, report_group_id, created, updated) VALUES (2, 101, 100, 1, '0645', '朝食前', '8202', 21, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP);"
        ];

        $this->fileHandler->exportTextFile($data, $this->tempFile);

        // Suppress warnings when reading the content
        $fileContent = @file_get_contents($this->tempFile);

        $expectedContent = "INSERT INTO m_facility_timings (origin_type, facility_id, pharmacy_id, input_type, code, name, input_code, report_group_id, created, updated) VALUES (2, 101, 100, 1, '0600', '起床時薬', '8201', 10, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP);" . PHP_EOL .
            "INSERT INTO m_facility_timings (origin_type, facility_id, pharmacy_id, input_type, code, name, input_code, report_group_id, created, updated) VALUES (2, 101, 100, 1, '0645', '朝食前', '8202', 21, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP);" . PHP_EOL;

        $this->assertEquals($expectedContent, $fileContent, 'The content of the text file should match the expected content');
    }

    public function test_delete_directory()
    {
        // Create a temporary directory with files
        $dirPath = $this->tempDir;
        file_put_contents($dirPath . '/T_UNT.csv', 'Content 1');
        file_put_contents($dirPath . '/log.txt', 'Content 2');

        // Ensure the directory exists before deletion
        $this->assertTrue(is_dir($dirPath));

        // Suppress any warnings related to deletion
        @rmdir($dirPath);

        // Assert the directory no longer exists
        $this->assertFalse(is_dir($dirPath));
    }

    protected function tearDown(): void
    {
        // Clean up: Delete the temporary file and temporary directory
        if (is_writable($this->tempFile) && file_exists($this->tempFile)) {
            unlink($this->tempFile);
        }
        if (is_dir($this->tempDir)) {
            // Safely remove the directory if it exists
            @rmdir($this->tempDir);
        }
    }
}
