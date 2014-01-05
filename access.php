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
 $result = mysqli_query($con,"SELECT NodeName,Timestamp, Name FROM EntryLog, AccessCards WHERE EntryLog.CardID = AccessCards.CardID AND AccessCards.Name = '$name'");
 $row_count = mysqli_num_rows($result);
 $page_count = (int)ceil($row_count / $limit - 1);
 $result = mysqli_query($con,"SELECT NodeName,Timestamp, Name FROM EntryLog, AccessCards WHERE EntryLog.CardID = AccessCards.CardID AND AccessCards.Name = '$name' ORDER BY Timestamp DESC LIMIT {$start},{$limit}");}
elseif(isset($_GET['edit']) && !empty($_GET['edit']))
 {
  $edit = ($_GET['edit']);
  $result = mysqli_query($con,"SELECT * from AccessCards where name = '$edit'");
  while($row = mysqli_fetch_array($result)) 
  {
   $CardID = $row['CardID'];
   echo "<a href=" . $_SERVER['SCRIPT_NAME'] . ">Home</a>";
   echo "<form action=\"log.php\" method=\"post\">";
   echo "Card ID: <input type=\"text\" name=\"CardID\" value=\"" . $row['CardID'] . "\">";
   echo "Name: <input type=\"text\" name=\"Name\" value=\"" . $row['Name'] . "\">";
   echo "Email: <input type=\"text\" name=\"email\" value=\"" . $row['email'] . "\">";
   echo "Tone: <input type=\"text\" name=\"Tone\" value=\"" . $row['Tone'] . "\">";
   echo "<input type=\"submit\" value=\"Update\">";
   echo "</form>";
   echo "<br>";
  }
  echo "<table border=1><tr><td>NodeName</td><td>Location</td><td>12AM</td>";
   for ($x=1; $x<=11; $x++)
   {
    echo "<td>" . $x . "AM</td>";
   }
   echo "<td>12PM</td>";
   for ($x=1; $x<=11; $x++)
   {
    echo "<td>" . $x . "PM</td>";
   }
   echo "</tr>";
   $result = mysqli_query($con,"SELECT * from AccessNodes WHERE CardID = '$CardID'");
   while($row = mysqli_fetch_array($result)) 
   {
    echo "<form action=\"log.php\" method=\"post\">";
    echo "<tr>";
    echo "<td>" . $row['NodeName'] . "</td>";
    echo "<td>" . $row['Location'] . "</td>";
    for ($x=0; $x<=23; $x++)
   {
   echo "<td><input type=\"checkbox\" name=\"Hour" . str_pad($x,2,"0",STR_PAD_LEFT) . "\" value=\"" . $row['Hour00'] . "\"></td>";
   }
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

else
  {$result = mysqli_query($con,"SELECT NodeName,Timestamp, Name FROM EntryLog, AccessCards WHERE EntryLog.CardID = AccessCards.CardID ORDER BY Timestamp DESC LIMIT {$start},{$limit}");}

echo "<style type=\"text/css\">";
echo "tbody tr:nth-child(odd){ background-color:#ccc; }";
echo "</style>";
echo "<a href=" . $_SERVER['SCRIPT_NAME'] . ">Home</a>";
echo "<table border='0'>
<tr>
<th>Node Name</th>
<th>Timestamp</th>
<th>Name</th>
</tr>";

while($row = mysqli_fetch_array($result))
  {
  echo "<tr>";
  echo "<td>" . $row['NodeName'] . "</td>";
  echo "<td>" . $row['Timestamp'] . "</td>";
  echo "<td><a href=" . $_SERVER['SCRIPT_NAME'] . "?name=" . urlencode($row['Name']) . ">" . $row['Name'] . "</a><a href=" . $_SERVER['SCRIPT_NAME'] . "?edit=" . urlencode($row['Name']) . "><img align=right src=edit.png></img></a></td>";
  echo "</tr>";
  }
if($page == "0")
 {echo "<td>&nbsp</td>"; } 
else 
 if(isset($_GET['name']) && !empty($_GET['name']))
  {echo "<tr><td><a href=" . $_SERVER['SCRIPT_NAME'] . "?page=$prevpage&name=" . urlencode($name) . ">Prev $limit</a></td>";}
 else
  {echo "<tr><td><a href=" . $_SERVER['SCRIPT_NAME'] . "?page=$prevpage>Prev $limit</a></td>";}

echo "<td>&nbsp</td>";

if($page < $page_count)
 if(isset($_GET['name']) && !empty($_GET['name']))
  {echo "<td><a href=" . $_SERVER['SCRIPT_NAME'] . "?page=$nextpage&name=" . urlencode($name) . ">Next $limit</a></td></tr>";}
 else
  {echo "<td><a href=" . $_SERVER['SCRIPT_NAME'] . "?page=$nextpage>Next $limit</a></td></tr>";}
else
 {echo "<td>&nbsp</td>"; } 
echo "</table>";
mysqli_close($con);
?>
