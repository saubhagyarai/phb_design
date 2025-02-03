<?php

namespace App;

class FileHandlerClass extends DebuggerClass
{

    // ユーザーのタイミングデータのCSVを読み取る
    public function readCsv($filePath)
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
                // 空の行と「?」記号のチェックを結合
                if (!empty($row) && $row[1] !== '?') {
                    $formattedValue1 = str_pad($row[7], 4, '0', STR_PAD_LEFT); // 先頭にゼロを追加

                    $data[$formattedValue1] = [
                        "title" => $row[0],
                        "print_value" => $formattedValue1
                    ];
                }
            }
            fclose($handle);
        }
        return $data;
    }

    // CSVファイルをエクスポートする
    public function exportCSV($data, $filePath)
    {
        $this->writeCsv($filePath, $data);
    }

    // テキストファイルをエクスポートする
    public function exportTextFile($data, $filePath)
    {
        $this->writeFile($filePath, $data);
    }

    // CSVファイルにデータを書き込む
    public function writeCsv($filePath, $data)
    {
        // ファイルパスからディレクトリパスを取得
        $dirPath = dirname($filePath);

        // ディレクトリが存在するか確認し、再作成前に削除
        if (is_dir($dirPath)) {
            // ディレクトリとその内容を再帰的に削除
            $this->deleteDirectory($dirPath);
        }

        // ディレクトリを再度作成
        if (!mkdir($dirPath, 0775, true) && !is_dir($dirPath)) {
            throw new \Exception("ディレクトリの作成に失敗しました: {$dirPath}。権限を確認してください。");
        }

        // ファイルを書き込み用に開く（リソースを返す）
        $file = fopen($filePath, 'w');

        // ファイルが正常に開かれたか確認
        if ($file === false) {
            throw new \Exception("ファイルのオープンに失敗しました: $filePath");
        }

        // CSVデータを書き込む
        foreach ($data as $row) {
            fputcsv($file, $row, ',', '"', '\\'); // $escapeパラメータを明示的に渡す
        }

        // ファイルを閉じる
        fclose($file);
    }

    // テキストファイルにデータを書き込む
    public function writeFile($filePath, $data)
    {
        // ファイルを書き込み用に開く（リソースを返す）
        $file = fopen($filePath, 'w');

        // ファイルが正常に開かれたか確認
        if ($file === false) {
            throw new \Exception("ファイルのオープンに失敗しました: $filePath");
        }

        // 各SQLステートメントをファイルに書き込む
        foreach ($data as $statement) {
            fwrite($file, $statement . PHP_EOL);
        }
        // ファイルを閉じる
        fclose($file);
    }

    // ディレクトリとその内容を削除する
    public function deleteDirectory($dirPath)
    {
        // 削除前にディレクトリが空でないことを確認
        foreach (glob($dirPath . '/*') as $file) {
            if (is_dir($file)) {
                $this->deleteDirectory($file); // サブディレクトリを削除するための再帰呼び出し
            } else {
                unlink($file); // ファイルを削除
            }
        }
        rmdir($dirPath); // 空のディレクトリを削除
    }
}
