<?

include ("config.inc.php");
include ("addRemote.php");

//создание файла для записи статистики
if(!file_exists($url))
{
 $fd = fopen($url,"a");
 $data = "";
 for($i = 0; $i<=count($answer)-1;$i++)
 {
   $data.="0::";
 }
 $data.="0";
 fwrite($fd, $data);
 fclose($fd);
}

//вывод формы голосования
if ($_POST['do']=='') {
    echo $question . "<br/>";
    echo "<form method = 'post' action = ''>";
    echo "<input name = 'answer' type = 'radio' value = '0'>" . $answer[0] . "<br/>";
    for ($i = 1; $i <= count($answer) - 1; $i++) {
        echo "<input name = 'answer' type = 'radio' value = '$i'>" . $answer[$i] . "<br/>";
    }
    echo "<input name = 'do' type = 'submit' value = 'ответить'>";
    echo "</form>";
}

$db = mysqli_connect("localhost","mysql","mysql", "voteDB");

$MIN_TIME=15;
$voteOK = 0; // По умолчанию нельзя.
$ip = get_ip();
//обработка голоса и обновление результата в файле
    if ($_POST['do'] != '') {

        //проверка на то, выбран ли ответ
        for ($i = 0; $i <= count($answer) - 1; $i++)
        {
            if ($_POST['answer']!='') $check = 1;
        }
        if (!$check)
        {
            exit ("Вы ввели не всю информацию, вернитесь назад и заполните все поля!");
        }

        if ($db) {
            $searchIP = "select ip,ipDate from ipAdd where (ip='$ip')";
            $check = mysqli_query($db, $searchIP);
            $result = mysqli_fetch_all($check, MYSQLI_ASSOC);
            //Если ip светится первый раз - добавим в бд и разрешим голосование
            if ($result[0]["ip"] == "") {
                $voteOK = 1;
                $insIP = "INSERT INTO ipAdd (ip , ipDate) VALUES ('$ip' , NOW())";
                mysqli_query($db, $insIP);
                //Иначе проверим давно ли голосовал
            } else {
                $time = $result[0]["ipDate"];
                $timeInt = strtotime($time);
                if (time() - $timeInt > $MIN_TIME) {
                    $voteOK = 1;
                    $ipIsUp = $result[0]["ip"];
                    $upIP = "UPDATE ipAdd SET ipDate = NOW() WHERE ip = '$ipIsUp'";
                    mysqli_query($db, $upIP);
                }
            }
        }
        if ($voteOK == 1) {
            $wfile = file($url);
            $wdata = split("::", $wfile[0]);
            $sum = count($wdata) - 1;
            $wdata[$sum]++;
            $wdata[$_POST['answer']]++;
            $rf = fopen($url, "w");
            $rdata = implode("::", $wdata);
            fwrite($rf, $rdata);
            fclose($rf);

            //вывод результатов
            echo '<h1>Ваш голос учтён</h1>' . '<br/>';
            $res = file($url);
            $votes = split("::", $res[0]);
            for ($i = 0; $i <= count($answer) - 1; $i++) {
                echo "<strong>" . $answer[$i] . "</strong><br/>";
                echo '<div style = "height:7px; width:' . round(($votes[$i] * 100 / $votes[count($answer)]) * 3) . 'px;
    background:#ff0000;"></div>';
                echo "[" . $votes[$i] . "]<br/>";
            }
            echo "Всего приняло участие в опросе :" . $votes[count($answer)] . "<br/>";
        }
    else {
        echo '<h1>Вы уже принимали участие в голосование. Ваш голос не учтен.</h1>' . '<br/>';

        $res = file($url);
        $votes = split("::", $res[0]);

        for ($i = 0; $i <= count($answer) - 1; $i++) {
            echo "<strong>" . $answer[$i] . "</strong><br/>";
            echo '<div style = "height:7px; width:' . round(($votes[$i] * 100 / $votes[count($answer)]) * 3) . 'px;
    background:#ff0000;"></div>';
            echo "[" . $votes[$i] . "]<br/>";
        }
        echo "Всего приняло участие в опросе :" . $votes[count($answer)] . "<br/>";
    }
    }

?>