<?php

// All relevant changes can be made in the data file. Please read the docs: https://github.com/flokX/devShort/wiki

$config_path = __DIR__ . DIRECTORY_SEPARATOR . "config.json";
$config_content = json_decode(file_get_contents($config_path), true);
$stats_path = __DIR__ . DIRECTORY_SEPARATOR . "stats.json";
$stats_content = json_decode(file_get_contents($stats_path), true);

// Filter the names that the admin interface doesn't break
function filter_name($nameRaw) {
    $name = filter_var($nameRaw, FILTER_SANITIZE_STRING);
    $name = str_replace(" ", "-", $name);
    $name = preg_replace("/[^A-Za-z0-9-_]/", "", $name);
    return $name;
}

// API functions to delete and add the shortlinks via the admin panel
if (isset($_GET["delete"]) || isset($_GET["add"])) {
    $data = json_decode(file_get_contents("php://input"), true);
    if (isset($_GET["delete"])) {
        unset($config_content["shortlinks"][$data["name"]]);
        unset($stats_content[$data["name"]]);
    } else if (isset($_GET["add"])) {
        $filtered = array("name" => filter_name($data["name"]),
                          "url" => filter_var($data["url"], FILTER_SANITIZE_URL));
        if (!filter_var($filtered["url"], FILTER_VALIDATE_URL)) {
            echo "{\"status\": \"unvalid-url\"}";
            exit;
        }
        $config_content["shortlinks"][$filtered["name"]] = $filtered["url"];
        $stats_content[$filtered["name"]] = array();
    }
    file_put_contents($config_path, json_encode($config_content, JSON_PRETTY_PRINT));
    file_put_contents($stats_path, json_encode($stats_content, JSON_PRETTY_PRINT));
    header("Content-Type: application/json");
    echo "{\"status\": \"successful\"}";
    exit;
}

// Generator for page customization
$links_string = "";
if ($config_content["settings"]["custom_links"]) {
    foreach ($config_content["settings"]["custom_links"] as $name => $url) {
        $links_string = $links_string . "<a href=\"$url\" class=\"badge badge-secondary\">$name</a> ";
    }
    $links_string = substr($links_string, 0, -1);
}

?>

<!doctype html>
<html class="h-100" lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="robots" content="noindex, nofollow">
    <meta name="author" content="<?php echo $config_content["settings"]["author"]; ?> and the devShort team">
    <link href="../<?php echo $config_content["settings"]["favicon"]; ?>" rel="icon">
    <title>Admin panel | <?php echo $config_content["settings"]["name"]; ?></title>
    <link href="../assets/vendor/bootstrap/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/main.css" rel="stylesheet">
</head>

<body class="d-flex flex-column h-100">

    <main class="flex-shrink-0">
        <div class="container">
            <h1 class="mt-5 text-center"><?php echo $config_content["settings"]["name"]; ?></h1>
            <h4 class="mb-4 text-center">admin panel</h4>
            <div class="row">
                <div class="col-md-4 col-lg-3">
                    <div class="card d-none d-md-block mb-3">
                        <div class="card-body">
                            <a class="card-link" id="refresh" href="#refresh">Refresh charts</a>
                        </div>
                    </div>
                    <div class="card mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Add shortlink <small class="d-md-none"><a class="card-link" id="refresh" href="#refresh">Refresh charts</a></small></h5>
                            <form id="add-form">
                                <div class="form-group">
                                    <label for="name">Name</label>
                                    <input class="form-control mb-2 mb-sm-0 mr-sm-2" id="name" type="text" placeholder="Link1" required>
                                </div>
                                <div class="form-group">
                                    <label for="url">URL (destination)</label>
                                    <input class="form-control mb-2 mb-sm-0 mr-sm-2" id="url" type="url" placeholder="https://example.com" required>
                                </div>
                                <button class="btn btn-primary" type="submit">Add</button>
                                <div id="status"></div>
                            </form>
                        </div>
                    </div>
                    <div class="card mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Search</h5>
                            <form>
                                <input class="form-control" id="search-bar" type="text">
                            </form>
                        </div>
                    </div>
                    <div class="card d-none d-md-block">
                        <div class="card-body">
                            <p class="mb-0">powered by <a href="https://github.com/flokX/devShort">devShort</a> v2.4.0</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-8 col-lg-9">
                    <div class="d-flex justify-content-center">
                        <div class="spinner-border text-primary mt-4" id="spinner" role="status">
                            <span class="sr-only">Loading...</span>
                        </div>
                    </div>
                    <div id="charts"></div>
                </div>
            </div>
            <p class="text-center d-md-none mt-1 mb-5">powered by <a href="https://github.com/flokX/devShort">devShort</a> v2.4.0</p>
        </div>
    </main>

    <footer class="footer mt-auto py-3 bg-light">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <span class="text-muted">&copy; <?php echo date("Y") . " " . $config_content["settings"]["author"]; ?> and <a href="https://github.com/flokX/devShort">devShort</a></span>
                <?php if ($links_string) { echo "<span class=\"text-muted\">$links_string</span>"; } ?>
            </div>
        </div>
    </footer>

    <script src="../assets/vendor/frappe-charts/frappe-charts.min.iife.js"></script>
    <script src="main.js"></script>

</body>

</html>
