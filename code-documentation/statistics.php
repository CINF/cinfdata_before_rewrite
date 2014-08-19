<?php
include("graphsettings.php");
include("../common_functions_v2.php");
echo(html_header());
$db = std_db();

function file_element($file){
  $bad_names = Array("..", ".", "dygraph", "dygraph_old", "graphsettings.xml", "dygraphs", "xyplot.php.BAK");
  if (in_array($file, $bad_names)){
    return 0;
  }
  $bad_endings = Array("~", "#", "pyc");
  foreach ($bad_endings as $ending){
    if (substr($file, -strlen($ending)) === $ending){
      return 0;
    }
  }
  $types = Array("py"=>"python", "php"=>"php");
  $split = explode(".", $file);
  $type = $types[$split[1]];
  $lines = count(file("../sym-files2/{$file}")) - 1;
  echo("<tr>\n");
  echo "<td><a href=\"../code-documentation/code.php?dir=sym-files2&file=$file&type=$type\">$file</a></td><td>$lines</td>\n";
  echo("</tr>\n");
  return $lines;
}

function byteFormat($bytes, $unit = "", $decimals = 2) {
  # Borrowed from http://www.if-not-true-then-false.com/2009/format-bytes-with-php-b-kb-mb-gb-tb-pb-eb-zb-yb-converter/
  $units = array('B' => 0, 'KB' => 1, 'MB' => 2, 'GB' => 3, 'TB' => 4, 
		 'PB' => 5, 'EB' => 6, 'ZB' => 7, 'YB' => 8);
 
  $value = 0;
  if ($bytes > 0) {
    // Generate automatic prefix by bytes 
    // If wrong prefix given
    if (!array_key_exists($unit, $units)) {
      $pow = floor(log($bytes)/log(1024));
      $unit = array_search($pow, $units);
    }
 
    // Calculate byte value by prefix
    $value = ($bytes/pow(1024,floor($units[$unit])));
  }
 
  // If decimals is not numeric or decimals is less than 0 
  // then set default value
  if (!is_numeric($decimals) || $decimals < 0) {
    $decimals = 2;
  }
 
  // Format output
  return sprintf('%.' . $decimals . 'f '.$unit, $value);
}

?>
<div class=\"statistics\">
<p>This page contains various statistics for the database and for the
data presentation webpage code</p>

<h1>Database statistics</h1>

<?php
  $max_index = pow(2, 32);

# Create table with table stats
echo("" . 
     "<table border=\"1\">\n" .
     "  <tr><th>Name</th><th>Table full</th><th style=\"width:120px\">Size</th><th>Name</th><th>Table full</th><th>Size</th></tr>\n");

# General variables
$sum_size = 0;
$max_size = 0;
$max_size_name = "";
$max_percent = 0;
$max_percent_name = "";
# Get tables status
$query = "show table status";
$result  = mysql_query($query, $db);
$to_output = Array();
# Loop over the tables and calculate size and find max etc.
while ($row = mysql_fetch_array($result)){
  $row_sum = $row["Data_length"] + $row["Index_length"];
  $sum_size += $row_sum;
  $fraction_of_index = (float)$row['Auto_increment'] / (float)$max_index * 100.0;
  if($fraction_of_index > $max_percent){
    $max_percent = $fraction_of_index;
    $max_percent_name = $row['Name'];
  }
  if($row_sum > $max_size){
    $max_size = $row_sum;
    $max_size_name = $row['Name'];
  }
  $to_output[] = Array("name" => $row['Name'],
		       "full" => number_format($fraction_of_index, 2, '.', ','),
		       "size" => byteFormat($row_sum));
}

# Output double arrays
$floor_half = (int) (sizeof($to_output)/2);
$ceil_half = ceil(sizeof($to_output)/2);
for ($x=0; $x < $floor_half; $x++) {
  $table_line = $to_output[$x];
  echo("  <tr>\n");
  foreach(Array($to_output[$x], $to_output[$x + $ceil_half]) as $table_line){
    $name = $table_line['name'];
    if($table_line['name'] == $max_percent_name){
      $full = "<b>{$table_line['full']}%</b>";
      $name = "<b>{$table_line['name']}</b>";
    }else{
      $full = "{$table_line['full']}%";
    }
    if($table_line['name'] == $max_size_name){
      $size = "<b>{$table_line['size']}</b>";
      $name = "<b>{$table_line['name']}</b>";
    }else{
      $size = "{$table_line['size']}";
    }

    echo("    <td>$name</td>");
    echo("    <td>$full</td>");
    echo("    <td>$size</td>");
  }
  echo("  </tr>\n");
}

# Output trailing line with odd number of lines
if ($floor_half != $ceil_half){
  echo("  <tr>\n");
  $table_line = $to_output[$floor_half];
  if($table_line['name'] == $max_percent_name){
    $full = "<b>{$table_line['full']}%</b>";
  }else{
    $full = "{$table_line['full']}%";
  }
  if($table_line['name'] == $max_size_name){
    $size = "<b>{$table_line['size']}</b>";
  }else{
    $size = "{$table_line['size']}";
  }
  echo("    <td>{$table_line['name']}</td>");
  echo("    <td>$full</td>");
  echo("    <td>$size</td>");
  echo("    <td></td>");
  echo("    <td></td>");
  echo("    <td></td>");
  echo("  </tr>\n");
}

# Output the table bottom with summary information
  echo("  <tr>\n");
  echo("    <td colspan=4 ><b>Max</b></td>");
  echo("    <td><b>" . number_format($max_percent, 2, '.', ',') . "%</b></td>");
  echo("    <td><b>" . byteFormat($max_size, 2, '.', ',') . "</b></td>");
  echo("  </tr>\n");

  echo("  <tr>\n");
  echo("    <td colspan=4 ><b>Total</b></td>");
  echo("    <td></td>");
  echo("    <td><b>" . byteFormat($sum_size) . "</b></td>");
  echo("  </tr>\n");

echo("</table>\n");

?>

<h1>Code statistics</h1>

<p>The data presentations webpage consist of the following files. Click the files to view the code.</p>


<table border="1" cellpadding="3">
<tr><th align="left">File</th><th align="left">Number of lines</th></tr>
<?php

if ($handle = opendir("../sym-files2")) {
    /* This is the correct way to loop over the directory. */
  $files = Array();
  while (false !== ($entry = readdir($handle))) {
    array_push($files, $entry);
  }
  closedir($handle);
  sort($files);
  $total_lines = 0;
  foreach($files as $file){
    $total_lines += file_element($file);
  }
  echo("<tr>\n<td><b>Total</b></td><td><b>$total_lines</b></td>\n</tr>\n");
}

?>
</table>

</div>
<?php echo(html_footer());?>
