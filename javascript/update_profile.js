// Name: Mr. Chung Yhung Yie
// Project Name: update_profile.js
// Description: ontrols a user profile update popup. It handles opening and closing the popup, tracks the form’s initial state, 
//              restores unsaved changes, lets the user select a profile picture, validates the input (username, birthdate, gender, weight, height), 
//              sends the form data via AJAX to update the profile, and updates the UI with the new info if the update succeeds. It also provides 
//              feedback messages for success, errors, or info.
// First Written: 1/6/2025
// Last Modified: 6/7/2025 

document.addEventListener('DOMContentLoaded', () => {
    const isUser = window.appConfig?.isUser || false;

    const update_profile_btn = document.querySelector('.update_profile_btn');
    const update_container = document.getElementById('update_container');

    const back_button = update_container.querySelector('.update_profile_directory .icon');
    const profile_pic_option = document.querySelectorAll('.profile_pic_option');
    const selected_pic_id = document.getElementById('selected_pic_id');
    const update_profile_form = document.getElementById('update_profile_form');
    const messageArea = document.getElementById('messageArea');

    const maleRadio = document.querySelector('input[name="new_gender"][value="M"]');
    const femaleRadio = document.querySelector('input[name="new_gender"][value="F"]');

    let init_FormState = {};
    let GenderSelectVisual;

    function captureInitialFormState() {
        init_FormState = {
            selectedPicId: selected_pic_id.value,
            username: document.getElementById('update_username').value
        };

        if (isUser) {
            init_FormState.birthdate = document.getElementById('update_birthdate').value;
            init_FormState.weight = document.getElementById('update_weight').value;
            init_FormState.height = document.getElementById('update_height').value;
            init_FormState.gender = document.querySelector('input[name="new_gender"]:checked')?.value || '';
        }
    }

    function restoreInitialFormState() {
        profile_pic_option.forEach(img => {
            img.classList.toggle('selected', img.dataset.picId === init_FormState.selectedPicId);
        });
        selected_pic_id.value = init_FormState.selectedPicId;

        document.getElementById('update_username').value = init_FormState.username;
        if (isUser) {
            document.getElementById('update_birthdate').value = init_FormState.birthdate;
            document.getElementById('update_weight').value = init_FormState.weight;
            document.getElementById('update_height').value = init_FormState.height;

            document.querySelectorAll('input[name="new_gender"]').forEach(radio => {
                radio.checked = (radio.value === init_FormState.gender);
            });

            if (typeof GenderSelectVisual === 'function') {
                GenderSelectVisual();
            }
        }    
        hideMessage();
    }

    function showMessage(message, type) {
        messageArea.textContent = message;
        messageArea.classList.remove('info', 'success', 'error');
        messageArea.classList.add(type);
        messageArea.style.display = 'block';

        setTimeout(() => hideMessage(), 3500);
    }
    function hideMessage() {
        messageArea.textContent = '';
        messageArea.style.display = 'none';
        messageArea.className = '';
    }

    window.showPopUp = () => {
        update_container.style.display = 'flex';
        document.body.classList.add('no-scroll');
        captureInitialFormState();
    }

    window.hidePopUp = (reset = true) => {
        if (reset) {
            restoreInitialFormState();
        }
        update_container.style.display = 'none';
        document.body.classList.remove('no-scroll');
    };

    
    if (maleRadio && femaleRadio) {
        GenderSelectVisual = function() {
            const maleSelect = maleRadio.closest('label').querySelector('.male_select');
            const femaleSelect = femaleRadio.closest('label').querySelector('.female_select');

            maleSelect?.classList.toggle('selected', maleRadio.checked);
            femaleSelect?.classList.toggle('selected', femaleRadio.checked);   
        };
        GenderSelectVisual();
        // Add event listeners
        maleRadio.addEventListener('change', GenderSelectVisual);
        femaleRadio.addEventListener('change', GenderSelectVisual);
    }

    update_profile_btn?.addEventListener('click', window.showPopUp);
    back_button?.addEventListener('click', () => window.hidePopUp(true));

    update_container?.addEventListener('click', (event) => {
        if (event.target === update_container) {
            window.hidePopUp(true);
        }
    });

    profile_pic_option.forEach(img => {
        img.addEventListener('click', () => {
            profile_pic_option.forEach(option => option.classList.remove('selected'));

            img.classList.add('selected');

            selected_pic_id.value = img.dataset.picId;

        });
    });

    update_profile_form?.addEventListener('submit', async (event) => {
        event.preventDefault();
        hideMessage();
        showMessage('Updating profile...', 'info');

        const formData = new FormData(update_profile_form);
        formData.append('update_profile', 'true');

        const new_username = formData.get('new_username')?.trim();
        if (!new_username) {
            showMessage('Username cannot be empty.', 'error');
            return;
        }

        if (isUser) {
            const new_birthdate = formData.get('new_birthdate');
            const new_gender = formData.get('new_gender');
            const new_weight = formData.get('new_weight');
            const new_height = formData.get('new_height');

            if (!new_birthdate) {
                showMessage('Birthdate cannot be empty.', 'error');
                return;
            }

            if (!/^\d{4}-\d{2}-\d{2}$/.test(new_birthdate) || isNaN(new Date(new_birthdate))) {
                showMessage('Invalid birthdate format.', 'error');
                return;
            }

            if (!new_gender || (new_gender !== 'M' && new_gender !== 'F')) {
                showMessage('Gender must be selected (Male or Female).', 'error');
                return;
            }

            if (!new_weight || isNaN(new_weight) || parseFloat(new_weight) <= 0) {
                showMessage('Weight must be a positive number.', 'error');
                return;
            }
            if (!new_height || isNaN(new_height) || parseFloat(new_height) <= 0) {
                showMessage('Height must be a positive number.', 'error');
                return;
            }
        }

        try {
            const response = await fetch('../interfaces/update_profile.php', {
                method: 'POST', 
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                showMessage(result.message, 'success');

                captureInitialFormState(); // Capture the new state after successful update

                const profileNameElement = document.querySelector('.user_profile .name h1');
                if (profileNameElement) {
                    profileNameElement.textContent = result.new_username || new_username;
                }

                const profilePicElement = document.querySelector('.user_profile .profile_pic img');
                if (profilePicElement && result.new_profile_pic_src) {
                    profilePicElement.src = result.new_profile_pic_src;
                }

                setTimeout(() => {
                    window.hidePopUp(false);
                }, 1500);

            } else {
                showMessage(result.message, 'error');
            }
        } catch (error) {
            console.error('Error: ', error);
            showMessage('An unexpected error occurred. Please try again.', 'error');
        }

    });               
});