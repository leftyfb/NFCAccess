<?php
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(-1);

$start=0;
$limit = 20;
$page = (isset($_GET['page'])) ? (int) $_GET['page'] : 0;

$start = $page * $limit;
$nextpage = $page + 1;
$prevpage = $page - 1;

$con=mysqli_connect("localhost","root","<password>","nfc");
if (mysqli_connect_errno())
  {echo "Failed to connect to MySQL: " . mysqli_connect_error();}

$result = mysqli_query($con,"SELECT * FROM EntryLog");
$row_count = mysqli_num_rows($result);
$page_count = (int)ceil($row_count / $limit - 1);

if(isset($_GET['name']) && !empty($_GET['name']))
 {$name = ($_GET['name']);
 $result = mysqli_query($con,"SELECT * FROM EntryLog, AccessCards WHERE EntryLog.CardID = AccessCards.CardID AND AccessCards.Name = '$name'");
 $row_count = mysqli_num_rows($result);
 $page_count = (int)ceil($row_count / $limit - 1);
 $result = mysqli_query($con,"SELECT * FROM EntryLog, AccessCards WHERE EntryLog.CardID = AccessCards.CardID AND AccessCards.Name = '$name' ORDER BY Timestamp DESC LIMIT {$start},{$limit}");}
elseif(isset($_GET['edit']) && !empty($_GET['edit']))
 {
  $edit = ($_GET['edit']);
  $result = mysqli_query($con,"SELECT * from AccessCards where name = '$edit'");
  while($row = mysqli_fetch_array($result)) 
  {
   $CardID = $row['CardID'];
   echo "<a href=" . $_SERVER['SCRIPT_NAME'] . ">Home</a>";
   echo "<form action=\"" . $_SERVER['SCRIPT_NAME'] . "\" method=\"post\">";
   echo "<style type=\"text/css\">";
   echo "tbody tr:nth-child(odd){ background-color:#ccc; }";
   echo "</style>";
   echo "<table width=600px border=0><tr><td>CardID</td><td>Name</td><td>Email</td><td>Tone</td><td></td></tr>";
   echo "<tr>";
   echo "<td><input type=\"text\" name=\"CardID\" value=\"" . $row['CardID'] . "\"></td>";
   echo "<td><input type=\"text\" name=\"Name\" value=\"" . $row['Name'] . "\"></td>";
   echo "<td><input type=\"text\" name=\"email\" value=\"" . $row['email'] . "\"></td>";
   echo "<td><input type=\"text\" name=\"Tone\" value=\"" . $row['Tone'] . "\"></td>";
   echo "<td><input type=\"submit\" value=\"Update\"></td></tr>";
   echo "</table>";
   echo "</form>";
   echo "<br>";
  }
  echo "<style type=\"text/css\">";
  echo "tbody tr:nth-child(odd){ background-color:#ccc; }";
  echo "</style>";
  echo "<table width=600px border=0><tr bgcolor=lightgray><td></td><td></td><td align=center colspan=12>AM</td><td align=center colspan=12>PM</td><td></td>";
  echo "<tr bgcolor=lightgray><td>NodeName</td><td>Location</td><td>12</td>";
   for ($x=1; $x<=11; $x++)
   {
    echo "<td align=center>" . $x . "</td>";
   }
   echo "<td align=center>12</td>";
   for ($x=1; $x<=11; $x++)
   {
    echo "<td align=center>" . $x . "</td>";
   }
   echo "<td></td></tr>";
   $result = mysqli_query($con,"SELECT * FROM AccessNodes, AccessCards WHERE AccessNodes.CardID = AccessCards.CardID AND AccessNodes.CardID = '$CardID'");
   while($row = mysqli_fetch_array($result)) 
   {
    echo "<form action=\"" . $_SERVER['SCRIPT_NAME'] . "\" method=\"post\">";
    echo "<tr>";
    echo "<td>" . $row['NodeName'] . "</td>";
    echo "<td>" . $row['Location'] . "</td>";
    for ($x=0; $x<=23; $x++)
    {
     $hournum = str_pad($x,2,"0",STR_PAD_LEFT);
     $hour = "Hour" . $hournum;
     if($row[$hour] == "1") 
     {echo "<td><input type=\"checkbox\" name=\"" . $hour . "\" value=\"1\" checked></td>";}
     else
     {echo "<td><input type=\"checkbox\" name=\"" . $hour . "\" value=\"0\"></td>";}
   }
    $NodeName = $row['NodeName'];
    $Name = $row['Name'];
    $CardID = $row['CardID'];
    echo "<input type=\"hidden\" name=\"NodeName\" value=\"$NodeName\">";
    echo "<input type=\"hidden\" name=\"Name\" value=\"$Name\">";
    echo "<input type=\"hidden\" name=\"CardID\" value=\"$CardID\">";
    echo "<td><input type=\"submit\" value=\"Update\"></td></tr>";
    echo "</form>";
   }
   echo "</table>";
   exit();
 }
elseif(isset($_POST['CardID']) && isset($_POST['Name']) && isset($_POST['Tone']) && isset($_POST['email']))
 {
 $CardID = ($_POST['CardID']);
 $Name = ($_POST['Name']);
 $Tone = ($_POST['Tone']);
 $email = ($_POST['email']);
 mysqli_query($con,"UPDATE AccessCards SET CardID='$CardID', Name='$Name', Tone='$Tone', email='$email'  WHERE name = '$Name'");
 echo "<a href=" . $_SERVER['SCRIPT_NAME'] . ">Home</a><br>";
 echo "<a>Updated $CardID with Name=$Name Tone=$Tone email=$email<a/><br>";
 echo "<a href=" . $_SERVER['SCRIPT_NAME'] . "?edit=" . urlencode($Name) . ">Return to editing</a>";
 exit();
 }
elseif(isset($_POST['NodeName']))
  {
   $NodeName = $_POST['NodeName'];
   $Name = $_POST['Name'];
   $CardID = $_POST['CardID'];
   echo "<a href=" . $_SERVER['SCRIPT_NAME'] . ">Home</a><br>";
   for ($x=0; $x<=23; $x++)
   {
    $hournum = str_pad($x,2,"0",STR_PAD_LEFT);
    $hour = "Hour" . $hournum;
    if(isset($_POST[$hour]))
     {$houraccess = 1;}
    elseif(empty($_POST[$hour]))
     {$houraccess = 0;}
      mysqli_query($con,"UPDATE AccessNodes SET $hour='$houraccess' WHERE NodeName = '$NodeName' AND CardID = '$CardID'");
    echo "<a>Updated $hour with $houraccess for $Name on $NodeName<a/><br>";
   }
    echo "<a href=" . $_SERVER['SCRIPT_NAME'] . "?edit=" . urlencode($Name) . ">Return to editing</a>";
    exit();
  }
else
  {$result = mysqli_query($con,"SELECT * FROM EntryLog, AccessCards WHERE EntryLog.CardID = AccessCards.CardID ORDER BY Timestamp DESC LIMIT {$start},{$limit}");}

echo "<style type=\"text/css\">";
echo ".center
{
	margin:auto;
	width:600px;
}";

echo "tbody tr:nth-child(odd){ background-color:#ccc; }";
echo "div
{
	width:600px;
	height:575px;
	border:2px solid #000000;
	background-color:F5F5F5;
	background-clip:content-box;
}";
echo "div#box
{
	width:600px;
	height:20px;
	background-color:gray;
	background-clip:content-box;
}";
echo "</style>";
echo "<div id=\"box\" class=center></div>";
echo "<div class=center>";
echo "<center>";
echo "<table width=500px border='0'>
<tr></tr>
<tr><td><a href=" . $_SERVER['SCRIPT_NAME'] . ">Home</a></td>";
echo "</td><td></td></tr></table>";
echo "<table width=500px border='0'>
<tr>
<th>Node Name</th>
<th>Timestamp</th>
<th>Name</th>
<th>Result</th>
</tr>";

while($row = mysqli_fetch_array($result))
  {
  echo "<tr>";
  echo "<td>" . $row['NodeName'] . "</td>";
  echo "<td>" . $row['Timestamp'] . "</td>";
  echo "<td><a href=" . $_SERVER['SCRIPT_NAME'] . "?name=" . urlencode($row['Name']) . ">" . $row['Name'] . "</a><a href=" . $_SERVER['SCRIPT_NAME'] . "?edit=" . urlencode($row['Name']) . "><img align=right src=edit.png></img></a></td>";
  echo "<td align=center>" . $row['Result'] . "</td>";
  echo "</tr>";
  }
if($page == "0")
 {echo "<td>&nbsp</td>"; } 
else 
 if(isset($_GET['name']) && !empty($_GET['name']))
  {echo "<tr><td><a href=" . $_SERVER['SCRIPT_NAME'] . "?page=$prevpage&name=" . urlencode($name) . ">Prev $limit</a></td>";}
 else
  {echo "<tr><td><a href=" . $_SERVER['SCRIPT_NAME'] . "?page=$prevpage>Prev $limit</a></td>";}

echo "<td>&nbsp</td><td>&nbsp</td>";

if($page < $page_count)
 if(isset($_GET['name']) && !empty($_GET['name']))
  {echo "<td align=\"right\"><a href=" . $_SERVER['SCRIPT_NAME'] . "?page=$nextpage&name=" . urlencode($name) . ">Next $limit</a></td></tr>";}
 else
  {echo "<td align=\"right\"><a href=" . $_SERVER['SCRIPT_NAME'] . "?page=$nextpage>Next $limit</a></td></tr>";}
else
 {echo "<td>&nbsp</td>"; } 
echo "</table>";
echo "</div>";
mysqli_close($con);
?>
