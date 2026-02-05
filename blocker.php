<?php
 
$user = 'user';
$website = 'website.com.br';
$log = "/var/log/virtualmin/".$website."_access_log";
$limit = 1200;
$window = 600; // 10 min
$blocked = [];
$htaccess = "/home/".$user."/public_html/.htaccess";

$lines = file($log);
$now = time();

foreach ($lines as $line) {
    if (preg_match('/^(\S+) .* \[(.*?)\]/', $line, $m)) {
    	$ip = $m[1];
    	$dateStr = $m[2]; // "21/Aug/2025:18:04:26 -0400"

    	$date = DateTime::createFromFormat("d/M/Y:H:i:s O", $dateStr);
    	if ($date === false) {
    	}

    	$timestamp = $date->getTimestamp();
    	if ($now - $timestamp <= $window) {
        	$blocked[$ip] = ($blocked[$ip] ?? 0) + 1;
    	}
	}
}

$rules = "";

foreach ($blocked as $ip => $count) {
    if ($count > $limit) {
        $rules .= "Deny from $ip\n";
    }
}


// Lê todo o conteúdo
$content = file_get_contents($htaccess);

// Remove tudo depois de "# Auto bloqueio"
if (strpos($content, '# Auto bloqueio') !== false) {
    $content = substr($content, 0, strpos($content, '# Auto bloqueio'));
}

// Salva de volta só o que ficou
file_put_contents($htaccess, rtrim($content)."\n");

if ($rules) {
    file_put_contents($htaccess, "\n# Auto bloqueio\n".$rules, FILE_APPEND);
	echo $rules;
}
