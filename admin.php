<?php

// This file is part of the devShort project under the MIT License. Visit https://github.com/flokX/devShort for more information.

$config_path = implode(DIRECTORY_SEPARATOR, array(__DIR__, "data", "config.json"));
$config_content = json_decode(file_get_contents($config_path), true);
$stats_path = implode(DIRECTORY_SEPARATOR, array(__DIR__, "data", "stats.json"));
$stats_content = json_decode(file_get_contents($stats_path), true);

// Check if authentication is valid
session_start();
if (!isset($_SESSION["user_authenticated"])) {
    header("Location: admin-auth.php?login");
    exit;
}

// Deliver stats.json content for the program (make AJAX calls and charts reloading possible)
if (isset($_GET["get_data"])) {
    header("Content-Type: application/json");
    $content = array("shortlinks" => $config_content["shortlinks"], "stats" => $stats_content);
    echo json_encode($content);
    exit;
}

// API functions to delete and add the shortlinks via the admin panel
if (isset($_GET["delete"]) || isset($_GET["add"])) {
    $data = json_decode(file_get_contents("php://input"), true);
    if (isset($_GET["delete"])) {
        unset($config_content["shortlinks"][$data["name"]]);
        unset($stats_content[$data["name"]]);
    } else if (isset($_GET["add"])) {
        $filtered = array("name" => strtolower(filter_var($data["name"], FILTER_SANITIZE_STRING)),
                          "url" => filter_var($data["url"], FILTER_SANITIZE_URL));
        if (!filter_var($filtered["url"], FILTER_VALIDATE_URL)) {
            echo "{\"status\": \"unvalid-url\"}";
            exit;
        }
        if (array_key_exists($filtered["name"], $config_content["shortlinks"])) {
            echo "{\"status\": \"url-already-exists\"}";
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

// Generate custom buttons for the footer
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
    <link href="<?php echo $config_content["settings"]["favicon"]; ?>" rel="icon">
    <title>Admin panel | <?php echo $config_content["settings"]["name"]; ?></title>
    <link href="assets/vendor/bootstrap/bootstrap.min.css" rel="stylesheet">
</head>

<body class="d-flex flex-column h-100">

    <main class="flex-shrink-0">
        <div class="container">
            <h1 class="mt-5 text-center"><?php echo $config_content["settings"]["name"]; ?></h1>
            <h4 class="mb-4 text-center">admin panel</h4>
            <div class="row" id="app">
                <div class="col-md-4 col-lg-3">
                    <div class="card d-none d-md-block mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Tools</h5>
                            <a class="card-link" href="#reload" v-on:click="loadData">Reload charts</a>
                            <a class="card-link" href="admin-auth.php?logout">Logout</a>
                        </div>
                    </div>
                    <div class="card mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Add shortlink</h5>
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
                                <input class="form-control" type="text" v-model="search">
                            </form>
                        </div>
                    </div>
                    <div class="card d-none d-md-block mb-3">
                        <div class="card-body">
                            <p class="mb-0" id="version-1">powered by <a href="https://github.com/flokX/devShort">devShort</a></p>
                        </div>
                    </div>
                    <div class="card d-md-none mb-3">
                        <div class="card-body text-center">
                            <a class="card-link" href="#reload" v-on:click="loadData">Reload charts</a>
                            <a class="card-link" href="admin-auth.php?logout">Logout</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-8 col-lg-9">
                    <div class="d-flex justify-content-center" v-if="!loaded">
                        <div class="spinner-border text-primary my-4" role="status">
                            <span class="sr-only">Loading...</span>
                        </div>
                    </div>
                    <chart v-for="(stats, name) in dataObject.stats" v-bind:key="name" :style="displayStyle(name)" v-bind:name="name" v-bind:stats="stats" v-else></chart>
                </div>
            </div>
            <p class="text-center d-md-none mt-1 mb-5" id="version-2">powered by <a href="https://github.com/flokX/devShort">devShort</a></p>
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

    <template id="chart-template">
        <div class="card mb-3">
            <div class="card-body">
                <div class="row">
                    <div class="col-lg-6 d-flex align-items-center">
                        <h3 class="card-title mb-0">{{ name }}</h3>
                    </div>
                    <div class="col-lg-6 d-flex align-items-center">
                        <span>{{ accessCount.sevenDays }} <small class="text-muted">Accesses last 7 days</small> | {{ accessCount.total }} <small class="text-muted">Accesses in total</small></span>
                    </div>
                </div>
                <hr>
                <div class="overflow-auto" :id="chartId"></div>
                <hr>
                <p class="text-center text-muted mb-0" v-if="this.name === 'index'">Index is an internal entry. It counts the number of front page accesses.</p>
                <p class="text-center text-muted mb-0" v-else-if="this.name === '404-request'">404-request is an internal entry. It counts the number of accesses to non-existent shortlinks.</p>
                <div class="row" v-else>
                    <div class="col-lg-9">
                        <label class="sr-only" :for="'destination-' + this.identifier">URL (destination)</label>
                        <div class="input-group">
                            <input class="form-control" :id="'destination-' + this.identifier" type="url" :value="shortlinkUrl" readonly>
                            <div class="input-group-append">
                                <div class="input-group-text">
                                    <a :href="shortlinkUrl" target="_blank" rel="noopener">open</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 mt-2 mt-lg-0 text-center">
                        <button type="button" class="btn btn-outline-danger" v-on:click="remove">Delete shortlink</button>
                    </div>
                </div>
            </div>
        </div>
    </template>

    <script src="assets/vendor/frappe-charts/frappe-charts.min.iife.js"></script>
    <script src="assets/vendor/vue/vue.min.js"></script>
    <script src="assets/main.js"></script>

</body>

</html>
