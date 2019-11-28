<?php
error_reporting(E_ALL);
$allKeys = array();
$multiNewArrKeVa = array();
$path = __DIR__ . DIRECTORY_SEPARATOR . "test.txt";

##### ПЕРЕВІРЯЄМО, ЧИ ФАЙЛ ІСНУЄ #####
if(file_exists($path)) {

    ##### ЗАПИСУЄМО ФАЙЛ В СТРОКУ #####
    $string = file_get_contents($path);

    ##### СТРОКА, ЯКА БУДЕ ЗАМІНЕНА НА ІНШЕ ЗНАЧЕННЯ #####
    $regex = "/PAMI\\\Message\\\Event\\\[a-zA-Z]*Event Object\n\\(/";

    ##### ЗАМІНЮЄМО ВИЗНАЧЕНУ ВИЩЕ СТРОКУ НА ІНШЕ ЗНАЧЕННЯ #####
    $replacement = "!!!";
    $result = preg_replace($regex, $replacement, $string);

    ##### РОЗДІЛЯЄМО СТРОКУ НА ОКРЕМІ МАСИВИ ПО ПЕВНОМУ ДЕЛІМЕТРУ #####
    $delimiter = ")\n!!!";
    $multidimensionalArray = explode($delimiter, $result);

    ##### ВИРІЗАЄМО ЗІ ЗНАЧЕНЬ ОТРИМАНИХ МАСИВІВ НЕПОТРІБНІ СИМВОЛИ #####
    foreach($multidimensionalArray as $index => $value) {
        $pattern = "/!!!(\n)/";
        $replacement = "";
        $strRow = preg_replace($pattern, $replacement, $value);

        $pattern2 = "/\\[rawContent:protected\\] => /";
        $replacement2 = "";
        $newStrRow = preg_replace($pattern2, $replacement2, $strRow);

        ##### РОЗРІЗАЄМО СТРОКИ В КОЖНОМУ МАСИВІ НА ОКРЕМІ МАСИВИ #####
        $tmpArray = explode("[", $newStrRow);

        ##### ДЛЯ ПОДАЛЬШОЇ ОБРОБКИ БЕРЕМО ЛИШЕ ЗНАЧЕННЯ ПЕРШОГО ЕЛЕМЕНТА КОЖНОГО МАСИВА #####
        $strKeyVal = $tmpArray[0];
        $arV = array();
        $arK = array();
        $result = explode("\n", $strKeyVal);
        foreach ($result as $res) {
            if (!empty($res)) {
                $res = explode(": ", $res);
                if (!empty($res[1])){
                    array_push($arK, $res[0]);
                    array_push($arV, $res[1]);
                }
            }
        }

        foreach ($arK as $val){
            $pat = "/\\-/";
            $nK = preg_replace($pat, "", $arK);
            $pat = "/\s/";
            $nKe = preg_replace($pat, "", $nK);
        }
        array_push($allKeys, $nKe);
        $newArrKeVa = array_combine($nKe, $arV);

        array_push($multiNewArrKeVa, $newArrKeVa);
        echo "<pre>";
        #print_r($newArrKeVa);
        echo "</pre>";
    }

} else {

    echo "something wrong";
}


##### ПЕРЕБИРАЄМО  МАСИВИ З УСІМА КЛЮЧАМИ ТА ЗАПИСУЄМО КЛЮЧІ В ОДИН ГЛОБАЛЬНИЙ МАСИВ #####
$arrayKeys = array();
foreach ($allKeys as $allKeysRow){
    foreach ($allKeysRow as $allKeysVal){
        if(!empty($allKeysVal)) {
            array_push($arrayKeys, $allKeysVal);
        }
    }
}

##### ВИБИРАЄМО З ГЛОБАЛЬНОГО МАСИВУ $arrayKeys УНІКАЛЬНІ ЗНАЧЕННЯ ТА ЗАПИСУЄМО ЇХ У МАСИВ $arrayKeysUnique #####
$arrayKeysUnique = array();
$arrayKeysUnique = array_unique($arrayKeys);


##### МІНЯЄМО МІСЦЯМИ КЛЮЧІ ТА ЗНАЧЕННЯ В МАСИВІ $arrayKeysUnique, ТАКИМ ЧИНОМ ОТРИМУЄМО АСОЦІАТИВНИЙ МАСИВ #####
/*$arrayKeysUniqueK = array_flip($arrayKeysUnique);
echo "<pre>";
print_r($arrayKeysUniqueK);
echo "</pre>";*/

##### СТВОРЮЄМО АСОЦІАТИВНИЙ МАСИВ $arrayKeysUniqueA, В ЯКОМУ КЛЮЧІ ДОРІВНЮЮТЬ ЗНАЧЕННЯМ #####
$arrayKeysUniqueA = array();
$arrayKeysUniqueA = array_combine($arrayKeysUnique, $arrayKeysUnique);


##### НА ОСНОВІ НАЗВ КЛЮЧІВ З АСОЦІАТИВНОГО МАСИВУ СТВОРЮЄМО ЗМІННІ $variable #####
$variables = array();
foreach($arrayKeysUniqueA as $var){
    $variable = "$".(lcfirst($var));
}

########################################################################################################################

$file = "data.csv";
$openfile = fopen($file, "w") or die("Unable to open file!");
fclose($openfile);
file_put_contents($file, "");

##### ПЕРЕБИРАЄМО МАСИВ $multiNewArrKeVa З УСІМА ЗНАЧЕННЯМИ ТА ВИБИРАЄМО МАСИВИ $partMultiNewArrKeVa, ЯКІ МІСТЯТЬ ЗНАЧЕННЯ КОНКРЕТНОЇ ВИБОРКИ #####
foreach($multiNewArrKeVa as $numMultiNewArrKeVa => $partMultiNewArrKeVa)
{
    $data = "";
    $q = 0;
    foreach($arrayKeysUniqueA as $valukey => $valuvalue)
    {
        $q++;
        $dataChunk = "";
        foreach($partMultiNewArrKeVa as $keyPartMultiNewArrKeVa => $valuePartMultiNewArrKeVa)
        {
            if($valukey == $keyPartMultiNewArrKeVa)
            {
                $dataChunk = $valuePartMultiNewArrKeVa;
                break;
            }
        }

        $data .= "\"" . $dataChunk . "\"";

        $size = count($arrayKeysUniqueA);

        if($q != $size)
        {
            //echo "q=" . $q;
            $data .= ",";
        }
    }
    $data .= "\n";
    echo $data . "\n\n";
    echo "<br>";
    echo "<br>";

    ##### ЗАПИСУЄМО ЗМІСТ У ФАЙЛ, ВИКОРИСТОВУЄМО ФЛАГ FILE_APPEND (ЩОБ ДОПИСАТИ У КІНЕЦЬ ФАЙЛУ) ТА ФЛАГ LOCK_EX (ДЛЯ УСУНЕННЯ ОДНОЧАСНОГО ЗАПИСУ У ФАЙЛ ІНШИМ КОРИСТУВАЧЕМ) #####
    file_put_contents($file, $data, FILE_APPEND | LOCK_EX);
}

########################################################################################################################

require_once("./database/DB_connection.php");
require_once("./database/db_config.php");

$csvFile = __DIR__ . DIRECTORY_SEPARATOR . "data.csv";

$handle = @fopen($csvFile, "r");
if ($handle) {
    while (($buffer = fgets($handle, 4096)) !== false) {
        #echo "DATA FROM CSV-FILE" . $buffer;

        ##### ПІДКЛЮЧАЄМОСЬ ДО БАЗИ ДАНИХ ТА РОБИМО ЗАПИСИ ДАНИХ З ВИБОРКИ #####
        try {
            $conn = DB_connection(DB_DSN, DB_USER, DB_PASS);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $sql = "INSERT INTO events (Event, Privilege, Channel, Unique_id, CallerIDNum, CallerIDName, ConnectedLineNum, ConnectedLineName, AccountCode, Cause, Causetxt, Initiator, SubEvent, UniqueID, DialStatus, ChannelStateDesc, Context, ChannelId, CallId, SessionId, CallerID, CalleeID, RealCalleeID, Direction, DeviceState, Callback, Destination, DestUniqueID, Dialstring, ChannelState, CIDCallingPres)
    VALUES ($buffer)";
            $conn->exec($sql);
            echo "New record created successfully";
        }
        catch(PDOException $e)
        {
            echo $sql . "<br>" . $e->getMessage();
        }
        $conn = null;
    }
    if (!feof($handle)) {
        echo "ERROR: something wrong\n";
    }
    fclose($handle);
}
##### ПЕРЕЙМЕНОВУЄМО ФАЙЛ ДЛЯ ЗБЕРЕЖЕННЯ #####
rename($file,"data_" . date("Y.m.d-Hi") . ".csv");

##### ПЕРЕЗАПИСУЄМО ФАЙЛ ДЛЯ ПОДАЛЬШОЇ РОБОТИ #####
file_put_contents($file, "");