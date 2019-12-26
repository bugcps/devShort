<?php

// All relevant changes can be made in the data file. Please read the docs: https://github.com/flokX/devShort/wiki

session_start();
$incorrect_password = false;

$config_path = implode(DIRECTORY_SEPARATOR, array(__DIR__, "data", "config.json"));
$config_content = json_decode(file_get_contents($config_path), true);
$stats_path = implode(DIRECTORY_SEPARATOR, array(__DIR__, "data", "stats.json"));
$stats_content = json_decode(file_get_contents($stats_path), true);

// If no password is in the config.json file, redirect to wiki page
if (!$config_content["admin_password"]) {
    header("Location: https://github.com/flokX/devShort/wiki/Installation#installation");
    exit;
}

// First run: Hash password if it's in the config.json as clear text
$admin_password = $config_content["admin_password"];
if (password_get_info($admin_password)["algo"] === 0) {
    $hash = password_hash($admin_password, PASSWORD_DEFAULT);
} else {
    $hash = $admin_password;
}
$config_content["admin_password"] = $hash;
file_put_contents($config_path, json_encode($config_content, JSON_PRETTY_PRINT));

// Logout user in session if mode is logout
if (isset($_GET["logout"])) {
    unset($_SESSION["user_authenticated"]);
    header("Location: index.php");
    exit;
}

// Login user in session if mode is login and post data is available
if (isset($_GET["login"]) && isset($_POST["input_password"])) {
    if (password_verify($_POST["input_password"], $config_content["admin_password"])) {
        $_SESSION["user_authenticated"] = true;
        header("Location: admin.php");
        exit;
    } else {
        $incorrect_password = true;
    }
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
    <title>Login | <?php echo $config_content["settings"]["name"]; ?></title>
    <link href="assets/vendor/bootstrap/bootstrap.min.css" rel="stylesheet">
    <link href="assets/main.css" rel="stylesheet">
</head>

<body class="d-flex flex-column h-100">

    <main class="flex-shrink-0">
        <div class="container">
            <nav class="mt-3" aria-label="breadcrumb">
                <ol class="breadcrumb shadow-sm">
                    <li class="breadcrumb-item"><a href="<?php echo $config_content["settings"]["home_link"]; ?>">Home</a></li>
                    <li class="breadcrumb-item"><?php echo $config_content["settings"]["name"]; ?></li>
                    <li class="breadcrumb-item active" aria-current="page">Login</li>
                </ol>
            </nav>
            <h1 class="mt-5">Login</h1>
            <p class="lead">Please sign in to access the admin panel. If you need help, visit <a href="https://github.com/flokX/devShort/wiki">the devShort wiki</a>.</p>
            <form action="admin-auth.php?login" method="POST">
                <div class="alert alert-danger" role="alert" <?php if (!$incorrect_password) { echo "style=\"display: none;\""; } ?>>
                    The given password was incorrect, please try again!
                </div>
                <div class="form-group">
                  <label for="inputPassword">Password</label>
                  <input class="form-control" id="inputPassword" name="input_password" type="password" autofocus required>
                </div>
                <button class="btn btn-primary" type="submit">Login</button>
            </form>
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

</body>

</html>
