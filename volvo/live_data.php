<html>
<head>
<meta http-equiv="refresh" content="5;url=http://cinfdata.fysik.dtu.dk/volvo/live_data.php"> 
</head>
<body>

<?php
$fp = stream_socket_client("udp://130.225.87.86:9999", $errno, $errstr);
if (!$fp) {
  echo "ERROR: $errno - $errstr<br />\n";
}

  if(isset($_POST['setpoint'])){
    $setpoint_value = $_POST['setpoint'];
    fwrite($fp, "set_setpoint " . $setpoint_value);
    fread($fp, 26);
  }
?>

<h1>Temperature:
<?php
  fwrite($fp, "read_temperature");
  echo fread($fp, 26);
?>
 C
</h1>

<h1>Pressure:
<?php
  fwrite($fp, "read_pressure");
  echo fread($fp, 26);
?>
 mBar
</h1>

<h1>Setpoint
<?php
    echo("<form method='post'><input type='number' step='1' size='4' name='setpoint' value='");
    fwrite($fp, "read_setpoint");
    echo fread($fp, 26);
    echo("'>C <input type='submit' value='update'>");
    echo("<br>");
?>
</h1>


<h1>Sample Current:
<?php
  fwrite($fp, "read_samplecurrent");
  $val = fread($fp, 26);
echo ((float)$val)*1;
  
?>
 nA
</h1>

<?php
  fclose($fp);
?>
</body>