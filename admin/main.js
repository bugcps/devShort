var currentDate = new Date();
var startDate = new Date(new Date().setFullYear(currentDate.getFullYear() - 1));
var spinner = document.getElementById('spinner');
var chartsDiv = document.getElementById('charts');

function post(url, data) {
    'use strict';
    return fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        })
        .then(function (response) {
            return response.json();
        });
}

$('button#add-shortlink').click(function (event) {
    'use strict';
    event.preventDefault();
    spinner.style.display = '';
    post('index.php?add', {
        name: document.getElementById('name').value,
        url: document.getElementById('url').value
    }).then(function (data) {
        if (data.status === 'successful') {
            document.getElementById('name').value = '';
            document.getElementById('url').value = '';
            getCharts();
        } else if (data.status === 'unvalid-url') {
            document.getElementById('status').innerHTML = '<div class="alert alert-danger" role="alert">Unvalid URL. Please provide a valid URL.</div>';
        } else {
            document.getElementById('status').innerHTML = '<div class="alert alert-danger" role="alert">Error. Please try again.</div>';
        }
    });
    spinner.style.display = 'none';
});
$('a#refresh').click(function (event) {
    'use strict';
    event.preventDefault();
    getCharts();
});

function getCharts() {
    'use strict';
    spinner.style.display = '';
    while (chartsDiv.firstChild) {
        chartsDiv.firstChild.remove();
    }
    fetch('stats.json', {
        cache: 'no-cache'
    }).then(function (response) {
        return response.json();
    }).then(function (json) {
        $.each(json, function (name, data) {
            chartsDiv.insertAdjacentHTML('beforeend', '<div id="card-' + name + '" class="card mb-3"><div class="card-body"><div id="heatmap-' + name + '" class="heatmap"></div></div><div class="card-footer text-center text-muted"><a id="export-' + name + '" href="#download" class="card-link">Download chart</a><a id="delete-' + name + '" href="#delete" class="card-link">Delete shortlink and dataset</a></div></div>');
            let heatmap = new frappe.Chart('div#heatmap-' + name, {
                type: 'heatmap',
                title: 'Access statistics for ' + name,
                data: {
                    dataPoints: data,
                    start: startDate,
                    end: currentDate
                },
                countLabel: 'Access(es)'
            });
            $('a#export-' + name).click(function (event) {
                event.preventDefault();
                heatmap.export();
            });
            $('a#delete-' + name).click(function (event) {
                event.preventDefault();
                post('index.php?delete', {
                    name: name
                }).then(function () {
                    document.getElementById('card-' + name).remove();
                });
            });
        });
        spinner.style.display = 'none';
    });
}

getCharts();
