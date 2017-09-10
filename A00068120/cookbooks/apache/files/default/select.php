<?php
$con = mysql_connect("192.168.131.103","icesi","12345");
if (!$con)
  {
  die('Could not connect: ' . mysql_error());
  }

mysql_select_db("database1", $con);

$result = mysql_query("SELECT * FROM example");

echo "<table>";
echo "<tr>";
echo "<th> Name </th>";
echo "<th> Age </th>";
echo "</tr>";

while($row = mysql_fetch_array($result))
  {
    echo "<tr>";
    echo "<td>";
    echo $row['name'];
    echo "</td>";
    echo "<td>";
    echo $row['age'];
    echo "</td>";
    echo "</tr>";
  }

mysql_close($con);
?>
