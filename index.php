<?php

require_once 'vendor/autoload.php'; // Include the autoloader

use App\Controllers\CSVController;

$csvController = new CSVController();

$csvController();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>　服薬時期マスタ作成</title>
</head>

<body>
    <h1>T_UNT.csv、d_facility_timings、d_facility_timings_Detailsの生成</h1>
    <form action="index.php" method="POST" enctype="multipart/form-data">
        <div>
            <label for="csvFile">ユーザータイミングCSVファイルを選択:</label>
            <input type="file" id="csv_file" name="csv_file" accept=".csv" required>
        </div>
        <br>
        <div>
            <label for="numberInput">薬局ID:</label>
            <input type="number" id="phar_id" name="phar_id" min="1" required>
        </div>

        <br>
        <div>
            <button type="submit">Run</button>
        </div>
    </form>

</body>

</html>