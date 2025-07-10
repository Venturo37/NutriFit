// Name: Mr. Chung Yhung Yie
// Project Name: admin_dashboard.js
// Description: listens for the DOM to load, then creates two pie charts, one for fitness activity and one for meal intake, using Chart.js. 
//              It formats the input data, sorts it (putting “Others” last), and applies custom colors.

// First Written: 2/6/2025
// Last Modified: 5/7/2025  

document.addEventListener('DOMContentLoaded', () => {

    function createPieChart (canvas_Id, data, colors) {
        const ctx = document.getElementById(canvas_Id).getContext('2d');
        return new Chart(ctx, {
            type: 'pie',
            data: {
                labels: data.map(item => item.name),
                datasets: [{
                    data: data.map(item => item.count),
                    backgroundColor: colors, 
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true, 
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                        // position: 'right', 
                        // labels: {
                        //     boxwidth: 15, 
                        //     padding: 15,
                        //     font: {
                        //         size: 12
                        //     }
                        // }
                    }, 
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `${context.label}: ${context.raw}(${Math.round(context.parsed)}%)`;
                            }
                        }
                    }
                }
            }
        });
    }
    
    const fitnessData = window.fitnessData.map(item => ({
        name: item.work_name, 
        count: item.count
    })).sort((a, b) => {
        if (a.name === 'Others') {
            return 1; //a comes after b
        }
        if (b.name === 'Others') {
            return -1; //a comes before b
        }
        return b.count - a.count; //Sort by count
    });
// fitnessData = [
//     { name: 'Push Ups', count: 5 },
//     { name: 'Running', count: 2 },
//     { name: 'Jumping Jacks', count: 2 },
//     { name: 'Others', count: 1 }
// ];

    const mealData = window.mealData.map(item => ({
        name: item.meal_name,
        count: item.count
    })).sort((a, b) => {
        if (a.name === 'Others') {
            return 1;
        }
        if (b.name === 'Others') {
            return -1;
        }
        return b.count - a.count;
    });

    const fitnessColors = ['#4A3AFF', '#C6D2FD', '#E0C6FD', '#962DFF'];
    const mealColors = ['#4A3AFF', '#C6D2FD', '#E0C6FD', '#962DFF'];

    if (fitnessData.length > 0) {
        createPieChart('fitnessChart', fitnessData, fitnessColors);
    }
    
    if (mealData.length > 0) {
        createPieChart('mealChart', mealData, mealColors);
    }
    

});