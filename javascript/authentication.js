// NAME : Mr. Ivan Shak Loong Wye  
// PROJECT NAME : authentication.js  
// DESCRIPTION OF PROGRAM :  
//     This script manages the dynamic behavior of the authentication interface.  
//     It enables switching between login, registration, verification, and reset forms.  
//     For registration, it supports navigation between two pages. It also includes responsive layout 
//     handling to adapt visual elements (like background images and shapes) based on screen size 
//     for an optimal user experience.

// FIRST WRITTEN : June 7th, 2025  
// LAST MODIFIED : July 9th, 2025  

function goToPage1() {
    document.getElementById('signup_page1').style.display = 'block';
    document.getElementById('signup_page2').style.display = 'none';
}

function goToPage2() {
    document.getElementById('signup_page1').style.display = 'none';
    document.getElementById('signup_page2').style.display = 'block';
}


function showForm(formType) {
    const allForms = ['login', 'verify', 'reset', 'signup'];

    // Hide all forms
    allForms.forEach(type => {
        const element = document.getElementById(type);
        if (element) {
            element.style.display = 'none';
        }
    });

    // Show selected form
    const target = document.getElementById(formType);
    if (target) {
        target.style.display = 'flex'; 
    }

    // If it's signup, reset to page 1
    if (formType === 'signup') {
        const page1 = document.getElementById('signup_page1');
        const page2 = document.getElementById('signup_page2');
        if (page1 && page2) {
            page1.style.display = 'block';
            page2.style.display = 'none';
        }
    }

    // Save the current formType
    window.currentFormType = formType;

    // Always call responsive image handler
    handleResponsiveImage();
}

function handleResponsiveImage() {
    const formType = window.currentFormType;
    const imageToShow = document.querySelector(`.image-${formType}`);
    const isShortScreen = window.matchMedia("(max-height: 800px)").matches;
    const isNarrowScreen = window.matchMedia("(max-width: 1200px)").matches;

    // Hide all bottom images first
    document.querySelectorAll('.bottom-image').forEach(img => {
        img.style.display = 'none';
    });

    // Hide all background shapes if height too short
    document.querySelectorAll('.background-shape').forEach(shape => {
        shape.style.display = isShortScreen ? 'none' : 'block';
    });

    // Special case: hide image-signup if screen height < 800px
    const imageSignup = document.querySelector('.image-signup');
    if (imageSignup) {
        imageSignup.style.display = (formType === 'signup' && !isShortScreen && isNarrowScreen) ? 'block' : 'none';
    }

    // Show appropriate image (if not signup, or signup and tall enough)
    if (imageToShow && imageToShow !== imageSignup && isNarrowScreen && !isShortScreen) {
        imageToShow.style.display = 'block';
    }
}









