<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

use PHPUnit\Framework\TestCase;
use App\Controllers\CSVController;
use App\FileHandlerClass;
use App\DataProcessorClass;


class CSVControllerTest extends TestCase
{
    protected $csvController;
    protected $fileHandlerMock;
    protected $dataProcessorMock;

    public function setUp(): void
    {
        // Create mocks for the dependencies
        $this->fileHandlerMock = $this->createMock(FileHandlerClass::class);
        $this->dataProcessorMock = $this->createMock(DataProcessorClass::class);

        // Instantiate the CSVController with the mocked dependencies
        $this->csvController = new CSVController($this->fileHandlerMock, $this->dataProcessorMock);
    }

    // ファイルタイプが csv でない場合のエラーメッセージ
    public function test_invoke_with_invalid_fileType()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_FILES['csv_file'] = ['tmp_name' => 'test.csv', 'type' => 'text/pdf'];
        $_POST['phar_id'] = '123';

        ob_start();
        $this->csvController->__invoke();
        $output = ob_get_clean();

        $this->assertStringContainsString('無効なファイル タイプです。CSV ファイルのみが許可されます。', $output);
    }
}
