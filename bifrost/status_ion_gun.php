<?php
include("../common_functions.php");
$db = std_db();

// Get current accvoltage 
$query = "SELECT unix_timestamp(time), accvoltage FROM ion_gun_data_bifrost order by time desc limit 1";
$result  = mysql_query($query,$db);  
$row = mysql_fetch_array($result);
$accvoltage_time = $row[0];
$current_accvoltage = $row[1];

// Get current emissioncurrent 
$query = "SELECT unix_timestamp(time), emissioncurrent FROM ion_gun_data_bifrost order by time desc limit 1";
$result  = mysql_query($query,$db);  
$row = mysql_fetch_array($result);
$emissioncurrent_time = $row[0];
$current_emissioncurrent = $row[1];

// Get sputtercurrent
$query = "SELECT unix_timestamp(time), energycurrent FROM ion_gun_data_bifrost order by time desc limit 1";
$result  = mysql_query($query,$db);  
$row = mysql_fetch_array($result);
$energycurrent_time = $row[0];
$current_energycurrent = $row[1];

// Get current pressure
$query = "SELECT unix_timestamp(time), pressure FROM pressure_bifrost order by time desc limit 1";
$result  = mysql_query($query,$db);  
$row = mysql_fetch_array($result);
$pressure_time = $row[0];
$current_pressure = $row[1];
?>

<?php echo new_html_header()?>

<div class="smallgraph">
<h2>Acceleration Voltage history</h2>
	<a href="read_dateplot.php?type=acceleration_voltage"><img src="plot.php?type=acceleration_voltage&small_plot=1"></a>
	<?php echo round($current_accvoltage,1)?> V recorded @ <?php echo date("D M j H:i Y",$accvoltage_time)?>
</div>

<div class="smallgraph">
<h2>Emission current history</h2>
	<a href="read_dateplot.php?type=emission_current"><img src="plot.php?type=emission_current&small_plot=1"></a>
	<?php echo round($current_emissioncurrent,1)?> mA recorded @ <?php echo date("D M j H:i Y",$emissioncurrent_time)?>
</div>

<div class="smallgraph">
<h2>Sputter current history</h2>
	<a href="read_dateplot.php?type=sputter_current"><img src="plot.php?type=sputter_current&small_plot=1"></a>
	<?php echo round($current_energycurrent,1)?> uA recorded @ <?php echo date("D M j H:i Y",$energycurrent_time)?>
</div>

<div class="smallgraph">
<h2>Pressure history</h2>
	<a href="read_dateplot.php?type=pressure"><img src="plot.php?type=pressure&small_plot=1"></a>
	<?php echo science_number($current_pressure)?> Torr recorded @ <?php echo date("D M j H:i Y",$pressure_time)?>
</div>

<?php echo new_html_footer()?>
</body>
</html>
