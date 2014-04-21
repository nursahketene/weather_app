<?php

try{
    $db = new PDO("mysql:hostname=localhost;dbname=weather;port=8889", "root", "root");
} catch (Exception $e){
	echo "OOPPPS! Could not connect to db";
	exit;
}


if(isset($_POST['zipcode']) && is_numeric($_POST['zipcode'])){
    $zipcode = $_POST['zipcode'];


$result = file_get_contents('http://weather.yahooapis.com/forecastrss?w=' . $zipcode . '&u=c');
$xml = simplexml_load_string($result);


 
//echo htmlspecialchars($result, ENT_QUOTES, 'UTF-8');
 
$xml->registerXPathNamespace('yweather', 'http://xml.weather.yahoo.com/ns/rss/1.0');
$location = $xml->channel->xpath('yweather:location');

$cur_temp = $xml->channel->item->xpath('yweather:condition')[0]['temp'];
$cur_city = $location[0]['city'];
$sql_insert = 'INSERT INTO records (post_code, temperature, city_name) VALUE (:post_code, :temperature, :city_name)';
$q = $db->prepare($sql_insert);
$q->execute(array(':post_code'=>$zipcode,
				  ':temperature'=>$cur_temp,
				  ':city_name'=>$cur_city));

if(!empty($location)){
    foreach($xml->channel->item as $item){
        $current = $item->xpath('yweather:condition');
        $forecast = $item->xpath('yweather:forecast');
        $current = $current[0];
        $output = <<<END
            <h1 style="margin-bottom: 0">Weather for {$location[0]['city']}, {$location[0]['region']}</h1>
            <small>{$current['date']}</small>
            <h2>Current Conditions</h2>
            <p>
            <span style="font-size:72px; font-weight:bold;">{$current['temp']}&deg;C</span>
            <br/>
            <img src="http://l.yimg.com/a/i/us/we/52/{$current['code']}.gif" style="vertical-align: middle;"/>&nbsp;
            {$current['text']}
            </p>
            <h2>Forecast</h2>
            {$forecast[0]['day']} - {$forecast[0]['text']}. High: {$forecast[0]['high']} Low: {$forecast[0]['low']}
            <br/>
            {$forecast[1]['day']} - {$forecast[1]['text']}. High: {$forecast[1]['high']} Low: {$forecast[1]['low']}
            </p>
END;
    }
}else{
    $output = '<h1>No results found, please try a different zip code.</h1>';
}
}
?>
<html>
<head>
<!-- Latest compiled and minified CSS -->
<link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Weather</title>
<style>
body {
    font-family: Arial, Helvetica, sans-serif;
    font-size: 12px;
}
.container{
	margin-top: 2em;
}
label {
    font-weight: bold;
}
</style>
</head>
<body>
<div class= "container">
<form method="POST" action="" role="form" class="form-inline">
	<div class = "form-group">
		<input type="text" name="zipcode" value="" placeholder="WOEID" class= "form-control" />
	</div>
	<input class="btn btn-success btn-sm" type="submit" name="submit" value="Lookup Weather" />
</form>
<?php echo $output; ?>
<?php 
	$result = $db->query("SELECT * FROM records ORDER BY id DESC");

	$weathers = $result->fetchAll(PDO::FETCH_ASSOC); ?>

<table class = "table table-striped">
	<thead>
		<tr>
			<th>
				City Name
			</th>
			<th>
				Temperature
			</th>
			<th>
				WOEID
			</th>
			<th>
				Search Time
			</th>
		</tr>
	</thead>
	<tbody>
	<?php
	foreach ($weathers as $weather)
	{
		$previous_result_output .= <<<END
			<tr>
				<td>
					{$weather['city_name']}
				</td>
				<td>
					{$weather['temperature']}
				</td>
				<td>
					{$weather['post_code']}
				</td>
				<td>
					{$weather['created_at']}
				</td>
			</tr>
END;
	}
	echo $previous_result_output;
	?>

	</tbody>
</table>
</div>
</body>
</html>