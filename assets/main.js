/* Register variables */
const currentDate = new Date();
const startDate = new Date(new Date().setFullYear(currentDate.getFullYear() - 1));
const spinner = document.getElementById('spinner');
const chartsDiv = document.getElementById('charts');
const statusDiv = document.getElementById('status');

/* Helper function to post to page api */
function post(url, data) {
    'use strict';
    return fetch(url, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    }).then(function (response) {
        return response.json();
    });
}

/* Add a new shortlink */
document.getElementById('add-form').addEventListener('submit', function (event) {
    'use strict';
    event.preventDefault();
    spinner.style.display = '';
    post('admin.php?add', {
        name: document.getElementById('name').value,
        url: document.getElementById('url').value
    }).then(function (data) {
        if (data.status === 'successful') {
            document.getElementById('name').value = '';
            document.getElementById('url').value = 'https://';
            if (statusDiv.firstChild) {
                statusDiv.firstChild.remove();
            }
            getCharts();
        } else if (data.status === 'unvalid-url') {
            statusDiv.insertAdjacentHTML('afterbegin', '<div class="alert alert-danger" role="alert">Unvalid URL. Please provide a valid URL.</div>');
        } else {
            statusDiv.insertAdjacentHTML('afterbegin', '<div class="alert alert-danger" role="alert">Error. Please try again.</div>');
        }
    });
    spinner.style.display = 'none';
});

/* Provide search functionality */
var searchBox = document.getElementById('search-bar');
searchBox.addEventListener('input', function (event) {
    'use strict';
    for (let node of chartsDiv.childNodes) {
        let linkName = node.firstElementChild.innerHTML.toLowerCase();
        if (linkName.includes(searchBox.value.toLowerCase())) {
            node.style.display = 'block';
        } else {
            node.style.display = 'none';
        }
    }
});

/* Refresh charts */
function refreshCharts(event) {
    'use strict';
    event.preventDefault();
    getCharts();
}
document.getElementById('refresh-1').addEventListener('click', refreshCharts);
document.getElementById('refresh-2').addEventListener('click', refreshCharts);

/* Get charts and date (remove old when necessary) */
function getCharts() {
    'use strict';
    spinner.style.display = 'block';
    while (chartsDiv.firstChild) {
        chartsDiv.firstChild.remove();
    }
    fetch('admin.php?get_stats').then(function (response) {
        return response.json();
    }).then(function (json) {
        for (let [name, data] of Object.entries(json)) {
            chartsDiv.insertAdjacentHTML('beforeend', `<div id="card-${name}" class="card text-center mb-3">
    <div class="card-header">${name}</div>
    <div class="card-body p-2">
        <div id="heatmap-${name}" class="overflow-auto"></div>
    </div>
    <div class="card-footer text-muted">
        <a id="export-${name}" href="#export" class="card-link">Export chart</a><a id="delete-${name}" href="#delete" class="card-link">Delete shortlink and dataset</a>
    </div>
</div>`);
            let heatmap = new frappe.Chart('div#heatmap-' + name, {
                type: 'heatmap',
                title: 'Access statistics for ' + name,
                data: {
                    dataPoints: data,
                    start: startDate,
                    end: currentDate
                },
                countLabel: 'Access(es)',
                discreteDomains: 0
            });
            document.getElementById('export-' + name).addEventListener('click', function (event) {
                event.preventDefault();
                heatmap.export();
            });
            document.getElementById('delete-' + name).addEventListener('click', function (event) {
                event.preventDefault();
                post('admin.php?delete', {
                    name: name
                }).then(function () {
                    document.getElementById('card-' + name).remove();
                });
            });
        }
        spinner.style.display = 'none';
    });
}

getCharts();
