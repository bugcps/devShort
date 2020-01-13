/* Register variables */
const currentDate = new Date();
const spinner = document.getElementById('spinner');
const chartsDiv = document.getElementById('charts');
const statusDiv = document.getElementById('status');
const version = "v3.0.0";

/* Helper function to post to page api */
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
            chart: null,
            accessCount: { sevenDays: 0, total: 0 }
        }
    },
    template: document.getElementById('chart-template'),
    mounted: function () {
        let ctx = document.getElementById(this.chartId);
        let dataset = [];
        for (let [unixTimestamp, count] of Object.entries(this.stats)) {
            let timestamp = new Date(unixTimestamp * 1000);
            if (((currentDate - timestamp) / (60 * 60 * 24 * 1000)) <= 7) {
                this.accessCount.sevenDays += count;
            }
            dataset.push({ x: timestamp, y: count });
        }
        this.accessCount.total = dataset.length;
        this.chart = new Chart(ctx, {
            type: 'bar',
            data: {
                datasets: [{
                    label: 'Access count',
                    data: dataset,
                    backgroundColor: 'rgba(0, 123, 255, 0.4)',
                    borderColor: '#007bff',
                    hoverBackgroundColor: 'rgba(0, 123, 255, 0.7)'
                }]
            },
            options: {
                legend: {
                    display: false
                },
                title: {
                    display: true,
                    text: 'Accesses to ' + this.name
                },
                scales: {
                    xAxes: [{
                        type: 'time',
                        distribution: 'linear',
                        ticks: {
                            min: currentDate.getTime() - (60 * 60 * 24 * 14 * 1000),
                            max: currentDate
                        },
                        time: {
                            tooltipFormat: 'YYYY-MM-DD',
                            unit: 'day'
                        }
                    }],
                    yAxes: [{
                        ticks: {
                            beginAtZero: true,
                            precision: 0
                        }
                    }]
                },
                plugins: {
                    zoom: {
                        pan: {
                            enabled: true,
                            mode: 'x',
                            rangeMax: {
                                x: currentDate
                            }
                        },
                        zoom: {
                            enabled: true,
                            mode: 'x',
                            rangeMax: {
                                x: currentDate
                            }
                        }
                    }
                }
            }
        });
    },
    beforeDestroy: function () {
        this.chart.destroy();
    },
    methods: {
        remove: function (event) {
            post('admin.php?delete', {
                name: this.name
            }).then(function (response) {
                vm.loadData();
            });
        },
        setView: function (event, range) {
            event.preventDefault();
            this.chart.options.scales.xAxes[0].ticks = {
                min: currentDate.getTime() - (60 * 60 * 24 * range * 1000),
                max: currentDate
            };
            this.chart.update();
        },
        viewOne: function (event) {
            this.setView(event, 14);
        },
        viewTwo: function (event) {
            this.setView(event, 31);
        }
    },
    computed: {
        chartId: function () {
            return 'chart-' + this.identifier;
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
        loaded: false
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
        }
    },
    created: function () {
        this.loadData();
    }
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

/* Add a new shortlink */
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

/* Check for updates */
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
