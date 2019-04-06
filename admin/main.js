var currentDate = new Date();
var startDate = new Date(new Date().setFullYear(currentDate.getFullYear() - 1));
var spinner = document.getElementById('spinner');
var chartsDiv = document.getElementById('charts');
$.ajaxSetup({
    cache: false
});

$('button#add-shortlink').click(function (event) {
    'use strict';
    event.preventDefault();
    spinner.style.display = '';
    $.post('index.php?add', {
        name: document.getElementById('name').value,
        link: document.getElementById('link').value
    }, function (data) {
        if (data === '{"status": "successful"}') {
            document.getElementById('name').value = '';
            document.getElementById('link').value = '';
            getCharts();
        } else if (data === '{"status": "unvalid-url"}') {
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
    $.getJSON('stats.json', function (json) {
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
                $.post('index.php?delete', {
                    name: name
                });
                document.getElementById('card-' + name).remove();
            });
        });
        spinner.style.display = 'none';
    });
}

getCharts();
