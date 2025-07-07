//Get all dropdown from document
const dropdowns = document.querySelectorAll('.dropdown');
//Loop through all dropdown elements
dropdowns.forEach(dropdown => {
    const select = dropdown.querySelector('.select');
    const caret = dropdown.querySelector('.caret');
    const menu = dropdown.querySelector('.menu');
    const options = dropdown.querySelectorAll('.menu li');
    const selected = dropdown.querySelector('.selected');

    select.addEventListener('click', () => {
        select.classList.toggle('select_clicked');
        caret.classList.toggle('caret_rotate');
        menu.classList.toggle('menu_open');
    });

    options.forEach(option => {
        option.addEventListener('click', () => {
            selected.innerText = option.innerText;
            select.classList.remove('select_clicked');
            caret.classList.remove('caret_rotate');
            menu.classList.remove('menu_open');
            
            options.forEach(otherOption => {
                otherOption.classList.remove('active');
            });
            option.classList.add('active');

            const mealTimeId = option.dataset.mealTime;
            const searchInput = document.querySelector('.search_input');
            const searchQuery = searchInput ? searchInput.value.trim().toLowerCase() : '';
            fetchMeals(mealTimeId, searchQuery);
        });
    });
});

// SEARCH FUNCTIONALITY
const searchInput = document.querySelector('.search_input');
if (searchInput) {
    searchInput.addEventListener('input', () => {
        const selectedOption = document.querySelector('.menu li.active');
        const mealTimeId = selectedOption ? selectedOption.dataset.mealTime : 1;
        const searchQuery = searchInput.value.trim().toLowerCase();
        fetchMeals(mealTimeId, searchQuery);
    });
}

function fetchMeals(mealTimeId, searchQuery = '') {
    const mealContainer = document.querySelector('.dp_section_3');
    mealContainer.innerHTML = '<p style="text-align: center;">Loading meals...</p>';

    const url = `get_meals.php?meal_time=${mealTimeId}&search_query=${encodeURIComponent(searchQuery)}`;

    fetch(url)
        .then(response => response.json())
        .then(meals => {
            mealContainer.innerHTML = '';
            if (meals.length > 0) {
                meals.forEach(meal => {
                    const meal_kcal = (meal.meal_carbohydrates * 4) + (meal.meal_protein * 4) + (meal.meal_fats * 9);
                    const card = `
                        <form action="selected_meal.php" method="POST" class="card_form">
                            <input type='hidden' name='meal_id' value='${meal.meal_id}'/>
                            <div class="card">
                                <img src="${meal.meal_image}" alt="Meal image for ${meal.meal_name}" onerror="this.onerror=null;this.src='https://placehold.co/200x150/EFEFEF/AAAAAA&text=No+Image';">
                                <div class="card_button">${meal.meal_name}</div>
                                <div class="kcal_intake">
                                    <ion-icon name="flame-outline"></ion-icon>
                                    ${Math.round(meal_kcal)} Kcal
                                </div>
                            </div>
                        </form>`;
                    mealContainer.insertAdjacentHTML('beforeend', card);
                });

                // Enable full-card click after new DOM insert
                const cards = document.querySelectorAll('.card_form .card');
                cards.forEach(card => {
                    card.addEventListener('click', () => {
                        card.closest('form').submit();
                    });
                });
            } else {
                mealContainer.innerHTML = '<p style="text-align: center;">No meals found.</p>';
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            mealContainer.innerHTML = '<p style="color: red; text-align: center;">Error loading meals.</p>';
        });
}



function updateCalories(consumed, burned) {
    const circle = document.querySelector('.progress_ring_circle');
    const text = document.getElementById('kcalText');
    const innerCircle = document.querySelector('.bg_ring');
    const radius = 45;
    const circumference = 2 * Math.PI * radius;

    let offset;

    if (burned === 0) {
        // No calories burned → full ring, red (surplus)
        offset = 0;
        circle.style.stroke = '#D35B50'; // red
        innerCircle.style.stroke = '#f4c542'; // orange
    } else {
        let progress = consumed / burned;

        if (progress <= 1) {
            offset = circumference - (progress * circumference);
            circle.style.stroke = '#B6D83D'; // green
            innerCircle.style.stroke = '#e6e6e6'; // default grey or neutral
        } else if (progress > 1 && progress <= 2) {
            offset = circumference - ((progress % 1) * circumference);
            circle.style.stroke = '#f4c542'; // orange
            innerCircle.style.stroke = '#B6D83D'; // green
        } else {
            offset = 0;
            circle.style.stroke = '#D35B50'; // red
            innerCircle.style.stroke = '#f4c542'; // orange
        }
    }

    circle.style.strokeDasharray = `${circumference}`;
    circle.style.strokeDashoffset = offset;
    text.textContent = `${consumed} Kcal`;
}

function refreshCalorieStats() {
    fetch('get_calorie_summary.php?ts=' + Date.now())
        .then(res => res.json())
        .then(data => {
            console.log("Refreshed data:", data);

            // Update Kcal Consumed
            const kcalText = document.getElementById('kcalText');
            if (kcalText) {
                kcalText.textContent = `${data.consumed} Kcal`;
            }

            // Update Kcal Burned
            const burnedText = document.getElementById('kcalBurnedText');
            if (burnedText) {
                burnedText.textContent = data.burned;
            }

            // Update Calorie Ring with Goal (NOT burned!)
            updateCalories(data.consumed, data.goal);

            // Update BMI
            updateBMI(data.bmi);
        })
        .catch(err => {
            console.error('Failed to refresh calorie stats:', err);
        });
}


// Example usage
// updateCalories(1200, 2000); // or updateCalories(2600, 2000);

function updateBMI(bmi) {
    const bmiText = document.getElementById("bmiText");
    const bmiValue = document.getElementById("bmiValue");
    const circle = document.getElementById("bmiCircle");
    const statusBox = document.querySelector('.status');

    bmiValue.textContent = bmi.toFixed(1);

    if (bmi < 18.5) {
        bmiText.textContent = "Underweight";
        circle.style.backgroundColor = "#6EC1F3";
        statusBox.style.backgroundColor = "#6EC1F3";
    } else if (bmi >= 18.5 && bmi < 25) {
        bmiText.textContent = "Normal";
        circle.style.backgroundColor = "#B6D83D";
        statusBox.style.backgroundColor = "#B6D83D";
    } else if (bmi >= 25 && bmi < 30) {
        bmiText.textContent = "Overweight";
        circle.style.backgroundColor = "#F3CE6E";
        statusBox.style.backgroundColor = "#F3CE6E";
    } else {
        bmiText.textContent = "Obese";
        circle.style.backgroundColor = "#D35B50";
        statusBox.style.backgroundColor = "#D35B50";
    }
}

document.addEventListener('DOMContentLoaded', () => {

    // --- POP-UP FUNCTIONALITY ---
    const manualInputButton = document.querySelector('.input_button');
    const popupOverlay = document.querySelector('.popup-overlay');
    const closeButton = document.querySelector('.popup-container .close-btn');
    const calorieForm = document.getElementById('calorie-form');

    // Check if all elements exist before adding listeners
    if (manualInputButton && popupOverlay) {
        manualInputButton.addEventListener('click', () => {
            popupOverlay.classList.add('active');
        });

        // X button to close
        if (closeButton) {
            closeButton.addEventListener('click', () => {
                popupOverlay.classList.remove('active');
            });
        }

        // Clicking the blurred overlay closes modal
        popupOverlay.addEventListener('click', (e) => {
            if (e.target === popupOverlay) {
                popupOverlay.classList.remove('active');
            }
        });

    }

    // --- REAL-TIME CALORIE CALCULATION ---
    const carbsInput = document.getElementById('carbs');
    const proteinInput = document.getElementById('protein');
    const fatsInput = document.getElementById('fats');
    const totalCaloriesDisplay = document.querySelector('.total-calories');


    function calculateTotalCalories() {
        // Ensure all elements are found
        if (!carbsInput || !proteinInput || !fatsInput || !totalCaloriesDisplay) {
            return;
        }

        // Get values, default to 0 if input is empty or not a number
        const carbs = parseFloat(carbsInput.value) || 0;
        const protein = parseFloat(proteinInput.value) || 0;
        const fats = parseFloat(fatsInput.value) || 0;

        // Calculate total calories (Carbs: 3 cal/g, Protein: 4 cal/g, Fats: 9 cal/g)
        const totalCalories = (carbs * 3) + (protein * 4) + (fats * 9);

        // Update the display
        totalCaloriesDisplay.textContent = `Total Calories: ${totalCalories}`;
    }

    // Add event listeners to input fields to calculate on any change
    if(carbsInput && proteinInput && fatsInput) {
        carbsInput.addEventListener('input', calculateTotalCalories);
        proteinInput.addEventListener('input', calculateTotalCalories);
        fatsInput.addEventListener('input', calculateTotalCalories);
    }

    // --- FORM SUBMISSION (Example) ---
    if (calorieForm) {
        calorieForm.addEventListener('submit', (event) => {
            event.preventDefault(); // Prevents the page from reloading

            // You can now get all the data and send it to your server using fetch()
            // This is where you would put your PHP script logic
            console.log("Form submitted!");
            alert("Data submitted! (Check the console for the form data). You would replace this with a fetch call to your PHP script.");

            const formData = new FormData(calorieForm);

            // Log data to console
            for (let [key, value] of formData.entries()) {
                console.log(`${key}: ${value}`);
            }
        });
    }
    //manual input form
    if (calorieForm) {
        calorieForm.addEventListener('submit', function (e) {
            e.preventDefault();

            const formData = new FormData(calorieForm);
            const submitBtn = calorieForm.querySelector("button[type='submit']");

            // Disable the submit button
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = "Submitting...";
            }

            fetch('manual_input.php', {  
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest' 
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                // Re-enable the submit button
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = "Submit";
                }

                if (data.success) {
                    alert("Meal added successfully!");
                    popupOverlay.classList.remove('active');
                    calorieForm.reset();

                    
                    refreshCalorieStats();
                    
                } else {
                    alert("Error: " + data.message);
                    console.error("Insert failed:", data.message);
                }
            })
            .catch(error => {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = "Submit";
                }
                alert("An unexpected error occurred.");
                console.error("Fetch failed:", error);
            });
        });
    }

});

const chooseMealButton = document.querySelector('.choose_meal_btn');
if (chooseMealButton) {
    chooseMealButton.addEventListener('click', () => {
        const mealId = chooseMealButton.dataset.mealId;

        if (!mealId) {
            alert("Meal ID not found.");
            return;
        }

        fetch('insert_selected_meal.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ meal_id: mealId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update the UI with new calorie data
                refreshCalorieStats();
                alert('Meal logged successfully!');
                window.location.href = 'diet_page_name.php';
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Insert error:', error);
            alert('An error occurred.');
        });
    });
}
