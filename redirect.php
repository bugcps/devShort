<?php

// All relevant changes can be made in the data file. Please read the docs: https://github.com/flokX/devShort/wiki

$short = strtolower(htmlspecialchars($_GET["short"]));

$return_404 = array("favicon.ico", "assets/vendor/bootstrap/bootstrap.min.css.map", "assets/vendor/frappe-charts/frappe-charts.min.iife.js.map");
if (in_array($short, $return_404)) {
    header("HTTP/1.1 404 Not Found");
    exit;
}

$config_path = implode(DIRECTORY_SEPARATOR, array(__DIR__, "data", "config.json"));
$config_content = json_decode(file_get_contents($config_path), true);
$stats_path = implode(DIRECTORY_SEPARATOR, array(__DIR__, "data", "stats.json"));
$stats_content = json_decode(file_get_contents($stats_path), true);

// Count the access to the given $name
function count_access($name) {
    global $stats_path, $stats_content;

    $stats_content[$name][date("Y-m-d")] += 1;
    file_put_contents($stats_path, json_encode($stats_content, JSON_PRETTY_PRINT));
}

if (array_key_exists($short, $config_content["shortlinks"])) {
    header("Location: " . $config_content["shortlinks"][$short], $http_response_code=303);
    count_access($short);
    exit;
} else if ($short === "") {
    header("Location: index.php", $http_response_code=301);
    exit;
} else {
    header("HTTP/1.1 404 Not Found");
    count_access("404-request");

    // Generate custom buttons for the footer
    $links_string = "";
    if ($config_content["settings"]["custom_links"]) {
        foreach ($config_content["settings"]["custom_links"] as $name => $url) {
            $links_string = $links_string . "<a href=\"$url\" class=\"badge badge-secondary\">$name</a> ";
        }
        $links_string = substr($links_string, 0, -1);
    }
}

?>

<!doctype html>
<html class="h-100" lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="robots" content="noindex, nofollow">
    <meta name="author" content="<?php echo $config_content["settings"]["author"]; ?> and the devShort team">
    <link href="<?php echo $config_content["settings"]["favicon"]; ?>" rel="icon">
    <title>404 | <?php echo $config_content["settings"]["name"]; ?></title>
    <link href="assets/vendor/bootstrap/bootstrap.min.css" rel="stylesheet">
</head>

<body class="d-flex flex-column h-100">

    <main class="flex-shrink-0">
        <div class="container">
            <nav class="mt-3" aria-label="breadcrumb">
                <ol class="breadcrumb shadow-sm">
                    <li class="breadcrumb-item"><a href="<?php echo $config_content["settings"]["home_link"]; ?>">Home</a></li>
                    <li class="breadcrumb-item"><?php echo $config_content["settings"]["name"]; ?></li>
                    <li class="breadcrumb-item active" aria-current="page">404</li>
                </ol>
            </nav>
            <h1 class="mt-5">404 | Shortlink Not Found.</h1>
            <p class="lead">The requested shortlink <i><?php echo $short; ?></i> was not found on this server. It was either deleted, expired, misspelled or eaten by a monster.</p>
        </div>
    </main>

    <footer class="footer mt-auto py-3 bg-light">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <span class="text-muted">&copy; 2020 <?php $config_content["settings"]["author"]; ?> and <a href="https://github.com/flokX/devShort">devShort</a></span>
                <?php if ($links_string) { echo "<span class=\"text-muted\">$links_string</span>"; } ?>
            </div>
        </div>
    </footer>

</body>

</html>
