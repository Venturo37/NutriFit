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
