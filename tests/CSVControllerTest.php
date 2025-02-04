<?php

use PHPUnit\Framework\TestCase;
use App\Controllers\CSVController;
use App\FileHandlerClass;
use App\DataProcessorClass;

require_once 'bootstrap.php';

class CSVControllerTest extends TestCase
{
    protected $csvController;

    public function setUp(): void
    {
        $this->csvController = new CSVController();
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

    public function test_invoke_with_valid_fileType()
    {
        // サーバーとファイルデータの準備
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_FILES['csv_file'] = ['tmp_name' => 'test.csv', 'type' => 'text/csv'];
        $_POST['phar_id'] = '123';

        // FileHandlerClass のモック作成
        $fileHandlerMock = $this->getMockBuilder(FileHandlerClass::class)
            ->onlyMethods(['readCsv', 'exportCSV', 'exportTextFile'])
            ->getMock();

        $fileHandlerMock->method('readCsv')
            ->willReturn(['Dummy Value']);

        $fileHandlerMock->method('exportCSV')
            ->willReturn(['Dummy Value']);

        $fileHandlerMock->method('exportTextFile')
            ->willReturnOnConsecutiveCalls(
                'Dummy Value1',
                'Dummy Value2',
                'Dummy Value3'
            );

        // DataProcessorClass のモック作成
        $dataProcessorMock = $this->getMockBuilder(DataProcessorClass::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'createTUntData',
                'createTimingFacilityData',
                'createTimingFacilityDetailData',
                'unmatchedUserData'
            ])
            ->getMock();

        $dataProcessorMock->method('createTUntData')
            ->willReturn(['Dummy Value']);

        $dataProcessorMock->method('createTimingFacilityData')
            ->willReturn(['Dummy Value']);

        $dataProcessorMock->method('createTimingFacilityDetailData')
            ->willReturn(['Dummy Value']);

        $dataProcessorMock->method('unmatchedUserData')
            ->willReturn(['Dummy Value']);

        // 出力をキャプチャ
        ob_start();
        $this->csvController->__invoke();
        $output = ob_get_clean();

        // 結果の検証
        $this->assertStringContainsString('CSV file generated successfully!', $output);
    }
}
