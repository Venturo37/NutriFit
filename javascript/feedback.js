// NAME : Mr. Ivan Shak Loong Wye  
// PROJECT NAME : feedback.js  
// DESCRIPTION OF PROGRAM :  
//     This script controls the interactivity of the Feedback page.  
//     It manages the display of a popup when a feedback record is selected, including applying blur effects 
//     to the background and populating the popup with detailed user feedback such as date, name, email, 
//     rating, and written response. Also includes functions to both show and hide the popup smoothly.

// FIRST WRITTEN : June 16th, 2025  
// LAST MODIFIED : July 9th, 2025  

// FOR SHOWING FEEDBACK POPUP
function showPopup(date, name, email, rating, response) {
    // DISPLAY POPUP
    document.getElementById('feedback_popup').style.display = 'block';

    // ADD BLUR TO PAGE
    document.querySelector(".overlay").style.display = "block";
    document.querySelector("header").classList.add("blur");
    document.querySelector(".content").classList.add("blur");
    document.querySelector("footer").classList.add("blur");

    
    // INSERT ROW DATA
    document.getElementById('popup_date').innerText = date;
    document.getElementById('popup_name').innerText = name;
    document.getElementById('popup_email').innerText = email;
    document.getElementById('popup_rating').innerText = rating;
    document.getElementById('popup_response').innerText = response;

   
    
}

function hidePopup() {
    // REMOVE POPUP
    document.getElementById('feedback_popup').style.display = 'none';

    // REMOVE BLUR FROM PAGE
    document.querySelector(".overlay").style.display = "none";
    document.querySelector("header").classList.remove("blur");
    document.querySelector(".content").classList.remove("blur");
    document.querySelector("footer").classList.remove("blur");

}
