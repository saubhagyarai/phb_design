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
        $this->tempDir = sys_get_temp_dir() . '/test_dir';  // Temporary directory for testing

        // Ensure the directory exists for cleanup during tests
        if (!is_dir($this->tempDir)) {
            mkdir($this->tempDir);
        }
    }

    // CSVデータ読み取り関数のテスト
    public function test_data_read_CSV()
    {
        // 正しい値でCSVコンテンツを挿入
        $csvData = "用法名称,p1,p2,緑 p3,p4,,線幅,線種,印字値,LED色\n";
        $csvData .= "おきた時,0,0,0,0,普通,実線,0005\n";

        file_put_contents($this->tempFile, $csvData);

        // CSVファイルを読み込む
        $result = $this->fileHandler->readCsv($this->tempFile);

        // 期待される出力を検証
        $this->assertArrayHasKey('0005', $result);
        $this->assertEquals('おきた時', $result['0005']['title']);  // ここで期待される値を修正
        $this->assertEquals('0005', $result['0005']['print_value']);
    }

    // エクスポートされたCSVデータのテスト
    public function test_data_of_export_CSV()
    {
        // エクスポートするデータを準備
        $data = [
            ["1001", "1", "起床時薬", "0600", "0600", "8201", "9999999999"],
            ["1002", "1", "朝食前", "0645", "0645", "8202", "9999999999"]
        ];

        // CSVをエクスポート
        $this->fileHandler->exportCSV($data, $this->tempFile);

        // ファイルコンテンツを読み込み、末尾の改行を削除
        $fileContent = rtrim(file_get_contents($this->tempFile), "\n");
        $expectedContent = "1001,1,起床時薬,0600,0600,8201,9999999999\n1002,1,朝食前,0645,0645,8202,9999999999";

        $this->assertSame($expectedContent, $fileContent, 'CSVファイルの内容は期待される内容と一致する必要があります');
    }

    public function test_1234data_of_export_CSV()
    {
        // エクスポートするデータを準備
        $data = [
            ["1001", "1", "起床時薬", "0600", "0600", "8201", "9999999999"],
            ["1002", "1", "朝食前", "0645", "0645", "8202", "9999999999"]
        ];

        // 一時ファイルの作成
        $this->tempFile = tempnam(sys_get_temp_dir(), 'csv_');

        // CSVをエクスポート
        $this->fileHandler->exportCSV($data, $this->tempFile);

        // ファイルコンテンツを読み込み、末尾の改行を削除
        $fileContent = rtrim(file_get_contents($this->tempFile), "\n");
        $expectedContent = "1001,1,起床時薬,0600,0600,8201,9999999999\n1002,1,朝食前,0645,0645,8202,9999999999";

        // アサーション
        $this->assertSame(rtrim($expectedContent, "\n"), rtrim($fileContent, "\n"), 'CSVファイルの内容は期待される内容と一致する必要があります');

        // 一時ファイルを削除
        unlink($this->tempFile);
    }

    // エクスポートされたファイルデータのテスト
    public function test_data_of_export_text()
    {
        // エクスポートするデータを準備
        $data = [
            "INSERT INTO m_facility_timings (origin_type, facility_id, pharmacy_id, input_type, code, name, input_code, report_group_id, created, updated) VALUES (2, 101, 100, 1, '0600', '起床時薬', '8201', 10, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP);",
            "INSERT INTO m_facility_timings (origin_type, facility_id, pharmacy_id, input_type, code, name, input_code, report_group_id, created, updated) VALUES (2, 101, 100, 1, '0645', '朝食前', '8202', 21, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP);"
        ];

        // exportTextFileメソッドを呼び出す
        $this->fileHandler->exportTextFile($data, $this->tempFile);

        // ファイルの内容を読み取る
        $fileContent = file_get_contents($this->tempFile);

        // 期待される内容を定義
        $expectedContent = "INSERT INTO m_facility_timings (origin_type, facility_id, pharmacy_id, input_type, code, name, input_code, report_group_id, created, updated) VALUES (2, 101, 100, 1, '0600', '起床時薬', '8201', 10, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP);" . PHP_EOL .
            "INSERT INTO m_facility_timings (origin_type, facility_id, pharmacy_id, input_type, code, name, input_code, report_group_id, created, updated) VALUES (2, 101, 100, 1, '0645', '朝食前', '8202', 21, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP);" . PHP_EOL;

        // ファイルの内容が期待される内容と一致することを確認
        $this->assertEquals($expectedContent, $fileContent, 'テキストファイルの内容は期待される内容と一致する必要があります');
    }

    // ディレクトリ削除機能のテスト
    public function test_delete_directory()
    {
        // ファイルを含む一時ディレクトリを作成
        $dirPath = sys_get_temp_dir() . '/test_dir';
        mkdir($dirPath);
        file_put_contents($dirPath . '/T_UNT.csv', 'Content 1');
        file_put_contents($dirPath . '/log.txt', 'Content 2');

        // 削除前にディレクトリが存在することを確認
        $this->assertTrue(is_dir($dirPath));

        // ディレクトリを削除
        $this->fileHandler->deleteDirectory($dirPath);

        // ディレクトリが存在しないことを確認
        $this->assertFalse(is_dir($dirPath));
    }

    protected function tearDown(): void
    {
        // Clean up: Delete the temporary file and temporary directory
        if (file_exists($this->tempFile)) {
            unlink($this->tempFile);
        }
        if (is_dir($this->tempDir)) {
            // Safely remove the directory if it exists
            rmdir($this->tempDir);
        }
    }
}
