<?php

use PHPUnit\Framework\TestCase;
use App\DataProcessorClass;
use App\FileHandlerClass;

class DataProcessorClassTest extends TestCase
{
    private $inputCsvData;
    private $tmpFile;
    private $dataProcessor;
    private $fileHandler;

    protected function setUp(): void
    {
        // ユーザーのタイミングデータCSV用の一時ファイルを作成
        $this->tmpFile = tempnam(sys_get_temp_dir(), 'user_timing_data.csv');

        // テスト用のサンプルCSVデータ
        $sampleCsvData =
            "用法名称, p1, p2,緑 p3, p4,線幅,線種,印字値,LED色\n" .
            "おきた時,0,0,0,0,普通,実線,5,指定なし\n" .
            "寝る前,0,0,1,0,普通,実線,6,指定なし\n" .
            "１４時,,,,,,,7,指定なし\n" .
            "朝,0,1,0,0,普通,実線,15,指定なし\n" . // マスタデータと一致しない
            "医師の指示通り,0,0,0,0,普通,実線,78,指定なし"; // マスタデータと一致しない

        // サンプルデータを一時ファイルに書き込む
        file_put_contents($this->tmpFile, $sampleCsvData);

        $this->fileHandler = new FileHandlerClass();
        $this->inputCsvData = $this->fileHandler->readCsv($this->tmpFile);
        $this->dataProcessor = new DataProcessorClass(mb_convert_encoding($this->inputCsvData, "SJIS", "UTF-8"), '123');
    }

    // マスタタイミングデータをCSVファイルから読み取る
    public function test_read_master_timing_csv()
    {
        // マスタタイミングデータCSV用の一時ファイルを作成
        $sampleCsvData = tempnam(sys_get_temp_dir(), 'sample_data.csv');

        $sampleMasterTimingData = "服薬時期,コード,開始時間,終了時間,印字コード,印字Grp\n" .
            "起床時薬,0600,0500,0700,8201,10\n" .
            "朝食前,0645,0600,0900,8202,21\n";

        // サンプルデータを一時ファイルに書き込む
        file_put_contents($sampleCsvData, $sampleMasterTimingData);
        // readMasterTimingCsvがCSVを正しく読み取ることをテスト
        $result = $this->dataProcessor->readMasterTimingCsv($sampleCsvData);

        // 結果に期待されるデータが含まれていることを確認
        $this->assertCount(2, $result); // サンプルCSVには2行のデータがある
        $this->assertEquals('起床時薬', $result[0]['title']);
        $this->assertEquals('0600', $result[0]['code']);
        $this->assertEquals('0700', $result[0]['end_time']);
        $this->assertEquals('8201', $result[0]['print_code']);
        $this->assertEquals('10', $result[0]['print_group']);
        $this->assertEquals('朝食前', $result[1]['title']);
        $this->assertEquals('0645', $result[1]['code']);
        $this->assertEquals('0900', $result[1]['end_time']);
        $this->assertEquals('8202', $result[1]['print_code']);
        $this->assertEquals('21', $result[1]['print_group']);
    }

    // T UNT CSVデータを生成するテスト
    public function test_create_TUnt_data()
    {
        // T UNTデータを作成するメソッドを呼び出す
        $result = $this->dataProcessor->createTUntData();

        $arrayToCheck = [3001, 1, 'おきた時', '0005', '0600', '8201', 123];
        $arrayToCheck1 = [4003, 2, '１４時', '0007', '4014', '8014', 123];
        $arrayToCheck2 = [1001, 1, '起床時薬', '0600', '0600', '8201', 9999999999];

        $this->assertTrue(in_array($arrayToCheck, mb_convert_encoding($result, "UTF-8", "SJIS"), true), '配列がリストに見つかりません。');
        $this->assertTrue(in_array($arrayToCheck1, mb_convert_encoding($result, "UTF-8", "SJIS"), true), '配列がリストに見つかりません。');
        $this->assertTrue(in_array($arrayToCheck2, mb_convert_encoding($result, "UTF-8", "SJIS"), true), '配列がリストに見つかりません。');
    }

    // m_facility_timingsデータを生成するテスト
    public function test_create_timing_facility_data()
    {
        // テスト対象のメソッドを呼び出す
        $result = $this->dataProcessor->createTimingFacilityData();

        // 期待されるSQLクエリ
        $expectedQueries = [
            "INSERT INTO m_facility_timings (origin_type, facility_id, pharmacy_id, input_type, code, name, input_code, report_group_id, created, updated) VALUES (2, 101, 100, 1, '0600', '起床時薬', '8201', 10, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP);",
            "INSERT INTO m_facility_timings (origin_type, facility_id, pharmacy_id, input_type, code, name, input_code, report_group_id, created, updated) VALUES (2, 101, 100, 1, '0645', '朝食前', '8202', 21, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP);",
            "INSERT INTO m_facility_timings (origin_type, facility_id, pharmacy_id, input_type, code, name, input_code, report_group_id, created, updated) VALUES (2, 101, 123, 1, '0600', '起床時薬', '8201', 10, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP);",
            "INSERT INTO m_facility_timings (origin_type, facility_id, pharmacy_id, input_type, code, name, input_code, report_group_id, created, updated) VALUES (2, 101, 123, 1, '0645', '朝食前', '8202', 21, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP);"
        ];

        // 必要に応じて結果をUTF-8に変換
        $result = array_map(function ($query) {
            return mb_convert_encoding($query, "UTF-8", "SJIS");
        }, $result);

        // 各期待されるクエリが結果に含まれているか確認
        foreach ($expectedQueries as $expectedQuery) {
            $this->assertTrue(in_array($expectedQuery, $result, true), '期待されるクエリが結果に見つかりません。');
        }
    }


    // m_facility_timings_Detailsデータを生成するテスト
    public function test_create_timing_facility_detail_data()
    {
        // テスト対象のメソッドを呼び出す
        $result = $this->dataProcessor->createTimingFacilityDetailData();

        // 期待されるSQLクエリ
        $expectedQueries = [
            "INSERT INTO m_facility_timings_Details (origin_type, facility_id, pharmacy_id, input_type, code, name, input_code, report_group_id, created, updated) VALUES (2, 101, 123, 1, '0005', 'おきた時', '8201', 10, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP);",
            "INSERT INTO m_facility_timings_Details (origin_type, facility_id, pharmacy_id, input_type, code, name, input_code, report_group_id, created, updated) VALUES (2, 101, 123, 1, '0006', '寝る前', '8220', 50, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP);"
        ];

        // 必要に応じて結果をUTF-8に変換
        $result = array_map(function ($query) {
            return mb_convert_encoding($query, "UTF-8", "SJIS");
        }, $result);

        // 各期待されるクエリが結果に含まれているか確認
        foreach ($expectedQueries as $expectedQuery) {
            $this->assertTrue(in_array($expectedQuery, $result, true), '期待されるクエリが結果に見つかりません。');
        }
    }

    // ユーザーのタイミング入力からマスタデータと一致しないデータをテスト
    public function test_unmatched_user_data()
    {
        // テスト対象のメソッドを呼び出す
        $result = $this->dataProcessor->unmatchedUserData();

        // 期待される値
        $expectedValue1 = "朝";
        $expectedValue2 = "医師の指示通り";

        // 配列に期待される値が含まれていることを確認
        $this->assertContains($expectedValue1, $result, '期待される値「朝」が結果の配列に見つかりません。');
        $this->assertContains($expectedValue2, $result, '期待される値「医師の指示通り」が結果の配列に見つかりません。');
    }

    protected function tearDown(): void
    {
        // 後片付け
        unlink($this->tmpFile);
    }
}
