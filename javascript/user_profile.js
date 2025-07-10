// Name: Mr. Chung Yhung Yie
// Project Name: user_profile.js
// Description: dynamically renders three interactive charts — a weight trend line chart, a calories burned bar chart, and a stacked meal intake bar chart, 
//              using Chart.js. It fetches data asynchronously from a PHP backend based on the user’s selected month and year, updates the charts in real-time, 
//              and ensures smooth performance by properly destroying and reinitializing chart instances. 
// First Written: 1/6/2025
// Last Modified: 6/7/2025 


// Global chart instance. Changed from 'let' to 'var' to prevent redeclaration errors in environments where the script might be executed multiple times or in specific scopes.
var weightChart;
var kcalChart;
var mealIntakeChart;

function renderWeightChart(labels, weights, yAxisMax) {
    const ctx = document.getElementById('weightChart').getContext('2d');

    // Destroy existing chart instance to prevent memory leaks and render issues
    if (weightChart) {
        weightChart.destroy();
    }

    // Create a new Chart.js instance
    weightChart = new Chart(ctx, {
        type: 'line', // Type of chart: Bar chart
        data: {
            labels: labels, // X-axis labels (weeks)
            datasets: [{
                label: 'Estimated Weight (KG)', // Label for the dataset
                data: weights, // Y-axis data (weight values)
                fill: true,
                backgroundColor: 'rgba(188, 212, 84, 0.3)',  // Area color
                borderColor: '#BCD454',        // Line color
                borderWidth: 1, // Width of the bar border
                borderRadius: 3, // Rounded corners for a modern look
                pointBackgroundColor: '#BCD454',
                tension: 0.4
            }]
        },
        options: {
            responsive: true, // Chart resizes with its container
            maintainAspectRatio: false, // Allows the chart to take height from its container
            scales: {
                y: {
                    // beginAtZero: true,
                    min: (yAxisMax * .3) , // Set minimum y-axis value
                    max: yAxisMax, // Set max y-axis value
                    ticks: {
                        stepSize: 5 // Set step size for y-axis ticks
                    }
                }, // Apply the Y-axis options, including the dynamic max
                x: {
                    title: {
                        display: true,
                        text: 'Day of Month', // X-axis title
                        font: {
                            size: 14 // Font size for title
                        }
                    }
                }
            },
            plugins: {
                legend: {
                    onClick: () => {}
                }
            }
        }
    });
}

function renderKcalChart (labels, kcal_burned, yAxisMax) {
    const ctx = document.getElementById('kcalChart').getContext('2d');

    if (kcalChart) {
        kcalChart.destroy();
    }

    kcalChart = new Chart(ctx, {
        type: 'bar', // Type of chart: Bar chart
        data: {
            labels: labels, // X-axis labels (weeks)
            datasets: [{
                label: 'Calories Burned (Kcal)', // Label for the dataset
                data: kcal_burned, // Y-axis data (weight values)
                backgroundColor: 'rgba(211, 91, 80, 0.5)',  // Area color
                borderColor: '#D35B50',        // Line color
                borderWidth: 1, // Width of the bar border
                borderRadius: 5 // Rounded corners for a modern look
            }]
        },
        options: {
            responsive: true, // Chart resizes with its container
            maintainAspectRatio: false, // Allows the chart to take height from its container
            scales: {
                y: {
                    beginAtZero: true,
                    max: yAxisMax, // Set max y-axis value
                    ticks: {
                        stepSize: 100 // Set step size for y-axis ticks
                    }
                }, // Apply the Y-axis options, including the dynamic max
                x: {
                    title: {
                        display: true,
                        text: 'Day of Month', // X-axis title
                        font: {
                            size: 14 // Font size for title
                        }
                    }
                }
            },
            plugins: {
                legend: {
                    onClick: () => {}
                }
            }
        }
    });
}


function renderMealIntakeChart (labels, deficit_values, tdee_values, surplus_values) {
    const ctx = document.getElementById('mealIntakeChart').getContext('2d');

    if (mealIntakeChart) {
        mealIntakeChart.destroy();
    }

    let max_val = 0;
    for (let i = 0; i < labels.length; i++) {
        let totalBarHeight = deficit_values[i] + tdee_values[i] + surplus_values[i];
        max_val = Math.max(max_val, totalBarHeight);
    }

    let yAxisMax = Math.ceil((max_val + 500) / 500) * 500;
    if (yAxisMax === 0) {
        yAxisMax = 1000;
    }
    
    mealIntakeChart = new Chart(ctx, {
        type: 'bar', // Type of chart: Bar chart
        data: {
            labels: labels, // X-axis labels (weeks)
            datasets: [
                {
                    label: 'Underconsumption (Kcal)', // Label for the dataset
                    data: deficit_values, // Y-axis data (weight values)
                    backgroundColor: 'rgba(188, 212, 84, 0.7)',  // Area color
                    borderColor: '#BCD454',        // Line color
                    borderWidth: 1, // Width of the bar border
                    borderRadius: 5, // Rounded corners for a modern look
                    stack: 'mealKcalStack'
                }, 
                {
                    label: 'Balanced Goal (Kcal)', // Label for the dataset
                    data: tdee_values, // Y-axis data (weight values)
                    backgroundColor: 'rgba(243, 206, 110, 0.5)',  // Area color
                    borderColor: '#F3CE6E',        // Line color
                    borderWidth: 1, // Width of the bar border
                    borderRadius: 5, // Rounded corners for a modern look
                    stack: 'mealKcalStack'
                },
                {
                    label: 'Overconsumption (Kcal)', // Label for the dataset
                    data: surplus_values, // Y-axis data (weight values)
                    backgroundColor: 'rgba(211, 91, 80, 0.7)',  // Area color
                    borderColor: '#D35B50',        // Line color
                    borderWidth: 1, // Width of the bar border
                    borderRadius: 5, // Rounded corners for a modern look
                    stack: 'mealKcalStack'
                }
            ]
        },
        options: {
            responsive: true, // Chart resizes with its container
            maintainAspectRatio: false, // Allows the chart to take height from its container
            scales: {
                y: {
                    // stacked: true, // Enable stacking for Y-axis
                    beginAtZero: true, // Y-axis starts from zero
                    max: yAxisMax, // Dynamic max Y-axis value
                    title: {
                        display: true,
                        text: "Calories (Kcal)", // Y-axis title
                        font: {
                            size: 14 // Font size for title
                        }
                    },
                    ticks: {
                        stepSize: 500 // Step size for Y-axis ticks
                    }
                },
                x: {
                    stacked: true, // Enable stacking for X-axis
                    title: {
                        display: true,
                        text: 'Day of Month', // X-axis title
                        font: {
                            size: 14// Font size for title
                        }
                    }
                }
            },

            plugins: { // This block was moved here, directly under 'options'
                legend: {
                    onClick: () => {}
                },
                tooltip: {
                    mode: 'index', // Show tooltips for all datasets at the same X-coordinate
                    intersect: false // Tooltip appears even if not directly over a bar segment
                }
            }
        }
    });

}



// 'async' means this function can pause its execution using 'await' while waiting for long operations (like network requests) to complete, without blocking the entire browser (it runs in the background).
async function fetchWeightData(year, month) {
    try {
        // Send a POST request to user_profile.php
        const response = await fetch('../interfaces/user_profile.php', {
            method: 'POST',
            headers: {
                // Important: Use 'application/x-www-form-urlencoded' for form data
                'Content-Type': 'application/x-www-form-urlencoded',
                // 'application/x-www-form-urlencoded' is the standard content type for data sent from HTML forms, and it's what PHP's $_POST superglobal expects to parse.
            },
            // Construct the request body with fetch_weights flag and selected values
            body: `fetch_weights=true&year=${year}&month=${month}`
        });

        // Check if the response was successful (HTTP status 200-299)
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        // 'response.ok' is a boolean property of the 'Response' object.
        // It's 'true' if the HTTP status code of the server's response is between 200 and 299 (inclusive),
        // indicating a successful response (e.g., 200 OK, 204 No Content).
        // If it's 'false' (e.g., 404 Not Found, 500 Internal Server Error), it means the request failed on the server side.

        // Parse the JSON response
        const data = await response.json();
        //  For example, 'data' will look like: 
        // { 
        //      labels: ["Week 1", "Week 2"], 
        //      weights: [75.1, 74.8] 
        // }

        // Calculate max weight for y-axis
        // Add 6 to the max weight for some padding at the top of the chart
        let max_weight = Math.max(...data.weights) + 5; 
        // Round up to the nearest 10 for a clean y-axis maximum
        let y_axis_max = Math.ceil(max_weight / 10) * 10; 

        // Update the chart with the newly fetched data and the calculated y-axis max
        renderWeightChart(data.labels, data.weights, y_axis_max);
    } catch (error) {
        console.error('Error fetching weight data:', error);
        // document.getElementById('chartErrorMessage').textContent = 'Failed to load chart data.'; OPTIONAL
    }
}

async function fetchKcalData (year, month) {
    try {
        const response = await fetch('../interfaces/user_profile.php', {
            method: 'POST', 
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            }, 
            body: `fetch_kcal_burned=true&year=${year}&month=${month}`
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();

        let max_kcal = 0;
        if (data.kcal_burned.length > 0) {
            max_kcal = Math.max(...data.kcal_burned);
        }

        let y_axis_max = Math.ceil((max_kcal + 100) / 100) * 100;

        renderKcalChart(data.labels, data.kcal_burned, y_axis_max);
        
    } catch (error) {
        console.error('Error fetching kcal data:', error);
        // document.getElementById('chartErrorMessage').textContent = 'Failed to load chart data.'; OPTIONAL
    }
}

async function fetchMealIntakeData(year, month) {
    try {
        const response = await fetch('../interfaces/user_profile.php', {
            method: 'POST', 
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            }, 
            body: `fetch_meal_intake=true&year=${year}&month=${month}`
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();

        renderMealIntakeChart(data.labels, data.deficit_values, data.tdee_values, data.surplus_values);
        
    } catch (error) {
        console.error('Error fetching meal intake data:', error);
    }
}

// Event listener for when the DOM is fully loaded
document.addEventListener('DOMContentLoaded', () => {
    const monthSelect = document.getElementById('month_select');
    const yearSelect = document.getElementById('year_select');
    const weightChartForm = document.getElementById('weight_chart_filer_form');

    const kcalMonthSelect = document.getElementById('kcal_month_select');
    const kcalYearSelect = document.getElementById('kcal_year_select');
    const kcalChartForm = document.getElementById('kcal_chart_filer_form');

    const mealIntakeMonthSelect = document.getElementById('meal_intake_month_select');
    const mealIntakeYearSelect = document.getElementById('meal_intake_year_select');
    const mealIntakeChartForm = document.getElementById('meal_intake_chart_filter_form');


    // Initial chart rendering when the page loads
    // 'init_weightChart_data' is passed from user_profile.php via a script tag
    if (typeof init_weightChart_data !== 'undefined' && init_weightChart_data.weights.length > 0) {
        // Calculate initial max weight for y-axis if data exists
        let max_weight_initial = Math.max(...init_weightChart_data.weights) + 6;
        let y_axis_max_initial = Math.ceil(max_weight_initial / 10) * 10;
        renderWeightChart(init_weightChart_data.labels, init_weightChart_data.weights, y_axis_max_initial);
    } else {
        console.warn("init_weightChart_data not found or empty. Chart might not render on initial load.");
        // Fallback: If initial data isn't provided by PHP, try fetching for the current month/year
        const today = new Date();
        fetchWeightData(today.getFullYear(), today.getMonth() + 1);
    }

    if (typeof init_kcalChart_data !== 'undefined' && init_kcalChart_data.kcal_burned.length > 0) { 
        let max_kcal_initial = Math.max(...init_kcalChart_data.kcal_burned);
        let y_axis_max_initial = Math.ceil((max_kcal_initial + 100) / 100) * 100;
        renderKcalChart(init_kcalChart_data.labels, init_kcalChart_data.kcal_burned, y_axis_max_initial);
    } else {
        console.warn("init_kcalChart_data not found or empty. Kcal chart might not render on initial load.");
        const today = new Date();
        fetchKcalData(today.getFullYear(), today.getMonth() + 1);
    }
    
    if (typeof init_mealIntakeChart_data !== 'undefined' && init_mealIntakeChart_data.labels.length > 0) { 
        renderMealIntakeChart(init_mealIntakeChart_data.labels, init_mealIntakeChart_data.deficit_values, init_mealIntakeChart_data.tdee_values, init_mealIntakeChart_data.surplus_values);
    } else {
        console.warn("init_mealIntakeChart_data not found or empty. Kcal chart might not render on initial load.");
        const today = new Date();
        fetchMealIntakeData(today.getFullYear(), today.getMonth() + 1);
    }


    // Add event listeners for changes in month and year dropdowns
    if (monthSelect && yearSelect && weightChartForm) {
        // Listen for changes on the form itself, or individual selects
        weightChartForm.addEventListener('change', () => {
            // When either month or year changes, fetch new data
            fetchWeightData(yearSelect.value, monthSelect.value);
        });
    }
    
    if (kcalMonthSelect && kcalYearSelect && kcalChartForm) {
        // Listen for changes on the form itself, or individual selects
        kcalChartForm.addEventListener('change', () => {
            // When either month or year changes, fetch new data
            fetchKcalData(kcalYearSelect.value, kcalMonthSelect.value);
        });
    }
    
    if (mealIntakeMonthSelect && mealIntakeYearSelect && mealIntakeChartForm) {
        // Listen for changes on the form itself, or individual selects
        mealIntakeChartForm.addEventListener('change', () => {
            // When either month or year changes, fetch new data
            fetchMealIntakeData(mealIntakeYearSelect.value, mealIntakeMonthSelect.value);
        });
    }

});

