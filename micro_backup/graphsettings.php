<?php
  /*
    Copyright (C) 2012 Robert Jensen, Thomas Andersen and Kenneth Nielsen
    
    The CINF Data Presentation Website is free software: you can
    redistribute it and/or modify it under the terms of the GNU
    General Public License as published by the Free Software
    Foundation, either version 3 of the License, or
    (at your option) any later version.
    
    The CINF Data Presentation Website is distributed in the hope
    that it will be useful, but WITHOUT ANY WARRANTY; without even
    the implied warranty of MERCHANTABILITY or FITNESS FOR A
    PARTICULAR PURPOSE.  See the GNU General Public License for more
    details.
    
    You should have received a copy of the GNU General Public License
    along with The CINF Data Presentation Website.  If not, see
    <http://www.gnu.org/licenses/>.
  */

ini_set('display_errors', 1); 
ini_set('log_errors', 1); 
ini_set('error_log', dirname(__FILE__) . '/error_log.txt'); 
error_reporting(E_ALL);

function xml_tree_to_assiciative_arrays($xml){
  $ret = Array();
  foreach ($xml->children() as $child){
    #echo($child->getName() . ":" . (string)$child->count() . "<br>");
    if ($child->count() > 0){
      #echo("yes<br>");
      $ret[$child->getName()] = xml_tree_to_assiciative_arrays($child);
	}
    else{
      #echo((string) $child . "<br>");
      $ret[$child->getName()] = (string) $child;
	}
  }
  
  return $ret;
}

function plot_settings($type,$params=""){
  # Write the type to the associative settings array and hence initiate it
  $gs = fopen('graphsettings.xml', 'r');
  $gs = fread($gs, filesize('graphsettings.xml'));
  $gs_xml = new SimpleXMLElement($gs);

  # Put the global settings in $settings
  $settings = xml_tree_to_assiciative_arrays($gs_xml->global_settings);
  
  # Put the graph specific settings in $settings
  foreach ($gs_xml->graph as $g) {
    if ($g['type'] == $type) {
      $specific_settings = xml_tree_to_assiciative_arrays($g);
      $settings = array_replace($settings, $specific_settings);
    }
  }
  
  
  
  if (gettype($params) == 'array'){
    foreach($settings as $s_key => $s_value){
      foreach($params as $p_key => $p_value){
	$settings[$s_key] = str_replace('{'.$p_key.'}', $p_value, $settings[$s_key]);
      }
    }
  }
  
  return $settings;
  }

?>
