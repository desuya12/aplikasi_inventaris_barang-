document.addEventListener('DOMContentLoaded', () => {

    // --- Chart Initialization ---

    // Temperature Chart (Water & Room)
    const ctxTemp = document.getElementById('tempChart').getContext('2d');
    const tempChart = new Chart(ctxTemp, {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'Water Temp (°C)',
                borderColor: '#29b6f6',
                backgroundColor: 'rgba(41, 182, 246, 0.1)',
                data: [],
                tension: 0.4,
                fill: true
            }, {
                label: 'Room Temp (°C)',
                borderColor: '#ff7043',
                backgroundColor: 'rgba(255, 112, 67, 0.1)',
                data: [],
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: { display: false }, // Hide timestamps for cleaner look
                y: {
                    grid: { color: 'rgba(255,255,255,0.05)' },
                    ticks: { color: '#a0a0a0' }
                }
            },
            plugins: {
                legend: { labels: { color: '#e0e0e0' } }
            }
        }
    });

    // Environment Chart (CO2 & Light) - Using two axes if needed, but simple line for now
    const ctxEnv = document.getElementById('envChart').getContext('2d');
    const envChart = new Chart(ctxEnv, {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'CO2 (ppm)',
                borderColor: '#66bb6a',
                borderDash: [5, 5],
                data: [],
                yAxisID: 'y',
                tension: 0.4
            }, {
                label: 'Light (lux)',
                borderColor: '#ffca28',
                data: [],
                yAxisID: 'y1',
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            scales: {
                x: { display: false },
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    grid: { color: 'rgba(255,255,255,0.05)' },
                    ticks: { color: '#a0a0a0' }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    grid: { drawOnChartArea: false }, // only want the grid lines for one axis to show up
                    ticks: { color: '#ffca28' }
                }
            },
            plugins: {
                legend: { labels: { color: '#e0e0e0' } }
            }
        }
    });

    // --- Data Fetching Logic ---

    const maxDataPoints = 20;

    function fetchData() {
        fetch('/api/metrics')
            .then(response => response.json())
            .then(data => {
                // Update DOM elements
                document.getElementById('water-val').innerText = data.water_temp;
                document.getElementById('room-val').innerText = data.room_temp;
                document.getElementById('co2-val').innerText = data.co2_level;
                document.getElementById('light-val').innerText = data.light_intensity;

                const now = new Date().toLocaleTimeString();

                // Update Temp Chart
                addData(tempChart, now, [data.water_temp, data.room_temp]);

                // Update Env Chart
                addData(envChart, now, [data.co2_level, data.light_intensity]);
            })
            .catch(error => console.error('Error fetching data:', error));
    }

    function addData(chart, label, dataPoints) {
        chart.data.labels.push(label);

        chart.data.datasets.forEach((dataset, index) => {
            dataset.data.push(dataPoints[index]);
        });

        // Remove old data if exceeded max points
        if (chart.data.labels.length > maxDataPoints) {
            chart.data.labels.shift();
            chart.data.datasets.forEach((dataset) => {
                dataset.data.shift();
            });
        }

        chart.update();
    }

    // Initialize logic
    fetchData(); // Run once immediately
    setInterval(fetchData, 2000); // Poll every 2 seconds
});
