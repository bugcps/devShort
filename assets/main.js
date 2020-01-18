// Register variables
const currentDate = new Date();
const startDate = new Date(new Date().setFullYear(currentDate.getFullYear() - 1));
const spinner = document.getElementById('spinner');
const statusDiv = document.getElementById('status');
const template = document.getElementById('chart-template');
const version = "v3.0.0";

// Helper function to post to page api
function post(url, data) {
    'use strict';
    return fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    }).then(function (response) {
        return response.json();
    });
}

// Vue chart component
Vue.component('chart', {
    props: ['name', 'stats'],
    data: function () {
        return {
            identifier: Math.floor(Math.random() * 10000),
            accessCount: { sevenDays: 0, total: 0 }
        }
    },
    template: template,
    mounted: function () {
        let dataset = [];
        for (let [unixTimestamp, count] of Object.entries(this.stats)) {
            let timestamp = new Date(unixTimestamp * 1000);
            if (((currentDate - timestamp) / (60 * 60 * 24 * 1000)) <= 7) {
                this.accessCount.sevenDays += count;
            }
            this.accessCount.total += count;
            dataset.push({ x: timestamp, y: count });
        }
        new frappe.Chart('div#' + this.chartId, {
            type: 'heatmap',
            title: 'Access statistics for ' + this.name,
            data: {
                dataPoints: this.stats,
                start: startDate,
                end: currentDate
            },
            countLabel: 'Accesses',
            discreteDomains: 0
        });
    },
    methods: {
        remove: function (event) {
            post('admin.php?delete', {
                name: this.name
            }).then(function (response) {
                vm.loadData();
            });
        }
    },
    computed: {
        chartId: function () {
            return 'heatmap-' + this.identifier;
        },
        shortlinkUrl: function () {
            return this.$parent.dataObject.shortlinks[this.name];
        }
    }
});

// Vue app instance
var vm = new Vue({
    el: '#app',
    data: {
        dataObject: [],
        loaded: false,
        search: ''
    },
    methods: {
        loadData: function (event) {
            if (event) {
                // When calling this function via a card-link
                event.preventDefault();
            }
            this.loaded = false;
            var vm = this;
            fetch('admin.php?get_data')
                .then(function (response) {
                    return response.json()
                })
                .then(function (data) {
                    vm.dataObject = data
                });
            this.loaded = true;
        },
        displayStyle: function (name) {
            if (!name.toLowerCase().includes(this.search.toLowerCase())) {
                return 'display: none;'
            } else {
                return 'display: block;'
            }
        }
    },
    created: function () {
        this.loadData();
    }
});

// Add a new shortlink
document.getElementById('add-form').addEventListener('submit', function (event) {
    'use strict';
    event.preventDefault();
    post('admin.php?add', {
        name: document.getElementById('name').value,
        url: document.getElementById('url').value
    }).then(function (data) {
        if (data.status === 'successful') {
            document.getElementById('name').value = '';
            document.getElementById('url').value = 'https://';
            vm.loadData();
        } else if (data.status === 'unvalid-url') {
            statusDiv.insertAdjacentHTML('afterbegin', '<div class="alert alert-danger" role="alert">Unvalid URL. Please provide a valid URL.</div>');
        } else {
            statusDiv.insertAdjacentHTML('afterbegin', '<div class="alert alert-danger" role="alert">Error. Please try again.</div>');
        }
    });
});

// Check for updates
fetch('https://devshort.flokX.dev/api.php?mode=version&current=' + version).then(function (response) {
    return response.json();
}).then(function (json) {
    let inner = ' ' + version;
    if (json['latest'] !== version) {
        inner += ' <span class="text-warning">(<a class="text-warning" href="https://github.com/flokX/devShort/releases/latest">update available</a>!)</span>';
    }
    document.getElementById('version-1').insertAdjacentHTML('beforeend', inner);
    document.getElementById('version-2').insertAdjacentHTML('beforeend', inner);
});
