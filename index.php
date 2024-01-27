<?php

require 'config.php';
require 'Medoo.php';

use Medoo\Medoo;

// Initialize
$db = new Medoo($config['db']);
if ($db === NULL) { die(); }

$title = $config['title'];

$timeZoneSet = date_default_timezone_set($config['timezone']);
if ($timeZoneSet === false) {
	echo "Failed to set timezone";
}







$today = date('Y-m-d');
$toDate = isset($_POST['toDate']) ? strtotime($_POST['toDate']) : false;
$fromDate = isset($_POST['fromDate']) ? strtotime($_POST['fromDate']) : false;
if ($fromDate) {
  $fromdt = date('Y-m-d', $fromDate);
} else {
   $fromdt = $today;
}

if ($toDate) {
  $todt = date('Y-m-d', $toDate);
} else {
   $todt = $today;
}



 if (isset($_POST['statSel'])){
	 $statName=$_POST['statSel'];
	 $statSelection=strtolower($statName);
 } else
 {
	 $statSelection="shiny";
	 $statName="Shiny";
 }
 
 
if (isset($_POST['languageSel'])){
	 $languageSelection=$_POST['languageSel'];
	 require 'pokedex_'.strtolower($languageSelection).'.php';

 } else
 {
	 $languageSelection="DE";
	 require 'pokedex_de.php';
 }

$statQuery = "SELECT date, pokemon_id, SUM(count) as count
    FROM pokemon_".$statSelection."_stats
    WHERE date BETWEEN :from AND :to"
    . ($statSelection !== "shiny" ? " AND Area = 'Map'" : "") .
    " GROUP BY pokemon_id
    HAVING SUM(count) > 0";

$stats = $db->query($statQuery,
    [ ":from" => $fromdt, ":to" => $todt ])
    ->fetchAll();

$totalStats = $db->query("SELECT date, pokemon_id, SUM(count) as count
    FROM pokemon_iv_stats
    WHERE date BETWEEN :from AND :to AND Area = 'Map'
    GROUP BY pokemon_id",
    [ ":from" => $fromdt, ":to" => $todt ])
    ->fetchAll();

	
	$minDate = $db->min("pokemon_iv_stats","date");


$totalSumStat = getTotalCount($stats, "NULL");
$totalSumPokemon = getTotalCount($totalStats, "NULL");
$totalStatRate = $totalSumStat != 0 ? round($totalSumPokemon / $totalSumStat) : 0;

$html = '
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta http-equiv="x-ua-compatible" content="ie=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
	<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
	<script src="https://www.kryogenix.org/code/browser/sorttable/sorttable.js"></script>
	<link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Lato">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">

  <title>'.$statName.' '.$title.'</title>

  <style>

  	.icon {
  		width: 60px;
			height: 60px;
  	}
	
	.container {
	  display: flex;
	  justify-content: space-between;
	}
input[type="date"] {
    max-height: 25px;
}
	.fullshinystats
	{
	 width: 20%;
	  text-align: right;
	}
	
	.statSel
	{
	 width: 20%;
	  text-align: left;
	}
	.data_period
	{
	  width: 80%;
	  margin: 0 auto;
	}

	#header {
		font-weight: bold;
		font-size: 25px;
		text-align: center;
		margin: 10px 0 0 0;
	}

	#event, #data_period {
		font-size: 15px;
		text-align: center;
		margin: 10px;
	}

	#event_name {
		font-weight: bold;
	}

	.table > thead > tr > th {
		 vertical-align: middle;
	}

    .table > tbody > tr > td {
      vertical-align: middle;
    }

	#footer {
		font-size: 13px;
		text-align: center;
		margin: 10px;
	}
	
	img {
	  display: block;
	  max-height:48px;
	  max-width:48px;
	  width: auto;
	  height: auto;
	}
  </style>
</head>
<body>
	<div id="header">
	   '.$statName.' '.$title.'
	   <br>
	</div>
	<br>
<div class="container">
	<div id="statSel">
	<br>
	<form name="statSelection" action="" method="post">
    <select name="statSel" onchange="this.form.submit()">
		<option value="Shiny" '.($statSelection == "shiny" ? "selected" : "").'>Shiny Stats</option>
                    <option value="Hundo" '.($statSelection == "hundo" ? "selected" : "").'>Hundo Stats</option>
                    <option value="Nundo" '.($statSelection == "nundo" ? "selected" : "").'>Nundo Stats</option>
    </select>	
	<select name="languageSel" onchange="this.form.submit()">
					<option value="DE" '.($languageSelection == "DE" ? "selected" : "").'>DE</option>
                    <option value="EN" '.($languageSelection == "EN" ? "selected" : "").'>EN</option>
    </select><br>
</div><div>
		<br>
		<input type="date"  name="fromDate" min="'.$minDate.'" max="'.$today.'" value="'.$fromdt.'" onchange="this.form.submit()">
        <input type="date"  name="toDate" min="'.$minDate.'" max="'.$today.'" value="'.$todt.'" onchange="this.form.submit()">
		</div>
    </form>
	
	<div id="fullshinystats">
	<table>		
		<tr>			
			<td>Total</td><td align="right">'.number_format($totalSumPokemon).'</td>
			</tr>
			<tr>
			<td>'.$statName.'</td><td align="right">'.number_format($totalSumStat).'</td>
			</tr>
			<tr>
			<td>Rate</td><td align="right">1/'.$totalStatRate.'</td>
			</tr>
		</tr>		
	</table>
<br>	
	</div>
</div>
	<div id="shiny_table">
		<table class="table sortable table-striped table-hover table-sm">
		    <thead class="thead-dark">
		        <tr>
			        <th class="sorttable_nosort" scope="col"> </th>
					<th scope="col">ID</th>
		            <th scope="col">Pokemon</th>
		            <th scope="col">'.$statName.' Rate</th>
					<th scope="col">'.$statName.' Rate in %</th>
					<th scope="col">Total</th>
		            <th scope="col">Total '.$statName.'</th>
					<th scope="col">Rarity</th>	
		        </tr>
		    </thead>
		    <tbody id="table_body">';
			
for ($i = 0; $i < count($stats); $i++) {
	$row = $stats[$i];
	$pokemonId = $row['pokemon_id'];
	$name = $pokedex[$pokemonId];
	$sta = $row['count'];
	$total = getTotalCount($totalStats, $pokemonId);
	$rate = round($total / $sta);
	$pokemonImageUrl = sprintf($config['images'], $pokemonId);
    $rarityStat = $totalSumPokemon/$sta;
	
	

	$html .= '<tr>';
	$html .= '<td><img src="' . $pokemonImageUrl . '" max-height="48" max-width="48"/></td>';
	$html .= '<td>' . number_format($pokemonId) . '</td>';
	$html .= '<td>' . $name . '</td>';
	$html .= '<td sorttable_customkey='.$rate.'>1/' . $rate . '</td>';
	$html .= '<td sorttable_customkey='.number_format((1/$rate)*100,4).'>' . number_format((1/$rate)*100,4) . '%</td>';
    $html .= '<td>' . number_format($total) .'</td>';
	$html .= '<td>' . number_format($sta) .'</td>';
    $html .= '<td sorttable_customkey='.number_format($rarityStat).'>1/' . number_format($rarityStat) .'</td>';
	$html .= '</tr>';
}
$html .= '</tbody>
		</table>
	</div>
	<div id="footer"></div>
</body>
</html>
';

echo $html;

function getTotalCount($pokemon, $pokemonId) {
	
	if($pokemonId === "NULL")
	{
		$sum = 0;
		for ($i = 0; $i < count($pokemon); $i++) {
			$row = $pokemon[$i];
			$sum = $sum + $row['count'];
		}
		return $sum;
	}
	else
	{
	
		for ($i = 0; $i < count($pokemon); $i++) {
			$row = $pokemon[$i];
			if ($row['pokemon_id'] === $pokemonId) {
				return $row['count'];
			}
		}
	}
	return 0;
}

?>
