<?php

// runs on commandline to crawl the 

require_once('config.php');

switch ($argv[1]) {
	case 'wikipedia':
		$domain = 'en.wikipedia.org';
		$db_table = 'wikipedia';
		break;
	case 'wikispecies':
		$domain = 'species.wikimedia.org';
		$db_table = 'wikispecies';
		break;	
	default:
		echo "You must pass the name of the wiki to harvest 'wikipedia' or 'wikispecies'\n";
		exit;
}

#$api_query = "https://!DOMAIN!/w/api.php?action=query&titles=!BINOMIAL!&format=jsonfm&prop=info|templates|langlinks|revisions|categories|contributors|links|linkshere|pageimages|pageviews|redirects&redirects";
$api_query = "https://!DOMAIN!/w/api.php?action=parse&page=!BINOMIAL!&format=json&maxlag=5&redirects";
$api_query = str_replace('!DOMAIN!', $domain, $api_query);

$count = 0;
while($next = next_binomial($mysqli, $db_table)){
	$start = microtime(true);
	echo "{$next['id']} - $count {$next['binomial']}\n";
	
	$query_uri = str_replace('!BINOMIAL!',urlencode($next['binomial']), $api_query);
	
	$raw = call_wiki($query_uri);
	$data = json_decode($raw);
	
	
	
	// is the page there?
	if(isset($data->error) && $data->error->code = 'missingtitle'){
		$db_ok = save_as_missing($mysqli, $db_table, $next['id'], $raw);
	}else{
		$db_ok = save($mysqli, $db_table, $next['id'], $raw, $data);
	}
	
	if(!$db_ok){
		echo "Database fail for {$next['id']}\n\n\n$raw";
		exit;
	}
	
	// print_r($data);
	
	$count++;
//	if ($count > 100) break;

	echo 'Total time: ' . ($start - microtime(true)) . "\n";
}

function save($mysqli, $db_table, $binomial_id, $raw, $data){
	
	$stmt = $mysqli->prepare("INSERT INTO $db_table (
		binomial_id,
		raw,
		`exists`,
		redirected_to,
		page_id,
		page_length,
		templates_count,
		autotaxobox,
		langlinks_count,
		categories_count,
		links_count,
		external_links_count,
		images_count,
		sections_count
	) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
	
	echo $mysqli->error;

	$exists = 1;
	$page_id = $data->parse->pageid;
	$redirect = isset($data->parse->redirects) && count($data->parse->redirects) ? $data->parse->title : null;
	
	$prop = "*";
	$text_length = strlen($data->parse->text->$prop);
	$template_count = count($data->parse->templates);
	$has_taxobox = strpos($raw, 'Module:Autotaxobox') ? 1 : 0;
	
	$stmt->bind_param("isisiiiiiiiiii",
		$binomial_id,
		$raw,
		$exists,
		$redirect, 
		$page_id,
		$text_length,
		$template_count,
		$has_taxobox,
		count($data->parse->langlinks),
		count($data->parse->categories),
		count($data->parse->links),
		count($data->parse->externallinks),
		count($data->parse->images),
		count($data->parse->sections)
	);
	
	
	$success = $stmt->execute();
	echo $stmt->error;
	
	return $success;
	
}

function save_as_missing($mysqli, $db_table, $binomial_id, $raw){

	$exists = 0;
	$stmt = $mysqli->prepare("INSERT INTO $db_table (binomial_id, raw, `exists`) VALUES (?, ?, ?)");
	echo $mysqli->error;
	$stmt->bind_param("isi", $binomial_id, $raw, $exists);
	
	$success = $stmt->execute();
	echo $stmt->error;
	
	return $success;

}

function call_wiki($query_uri){
	
	// create curl resource
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $query_uri);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch,CURLOPT_USERAGENT,'User-Agent: Script to compare species datasets. Looking for a way to do this without crawling! r.hyam@rbge.org.uk');
	//$start = microtime(true);
	$output = curl_exec($ch);
	//echo 'Wiki Call: ' . ($start - microtime(true)) . "\n";
	curl_close($ch);
	
	return $output;
	
}

function next_binomial($mysqli, $db_table){
	//$start = microtime(true);
	$sql = "SELECT b.id, b.binomial
		from binomials as b left join $db_table as w on b.id = w.binomial_id
		where w.id is null
		limit 1";
	$result = $mysqli->query($sql);
	if($result->num_rows == 0) return false;
	
	//echo 'Next Binomial: ' . ($start - microtime(true)) . "\n";
	return $result->fetch_assoc();

}

?>