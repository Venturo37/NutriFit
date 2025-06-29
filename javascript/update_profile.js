document.addEventListener('DOMContentLoaded', () => {
    const isUser = (window.appConfig && window.appConfig.isUser) ? window.appConfig.isUser : false;
    const isAdmin = (window.appConfig && window.appConfig.isAdmin) ? window.appConfig.isAdmin : false;


    const update_profile_btn = document.querySelector('.update_profile_btn');
    const update_container = document.getElementById('update_container');

    const back_button = update_container.querySelector('.update_profile_directory .icon');
    const profile_pic_option = document.querySelectorAll('.profile_pic_option');
    const selected_pic_id = document.getElementById('selected_pic_id');
    const update_profile_form = document.getElementById('update_profile_form');
    const messageArea = document.getElementById('messageArea');
    const maleRadio = document.querySelector('input[name="new_gender"][value="M"]');
    const femaleRadio = document.querySelector('input[name="new_gender"][value="F"]');

    window.showPopUp = () => {
        if (update_container) {
            update_container.style.display = 'flex';
            document.body.classList.add('no-scroll');
        }

        update_container.addEventListener('click', (event) => {
            if (event.target === update_container) {
                window.hidePopUp();
            }
        })

    }

    window.hidePopUp = () => {
        if (update_container) {
            update_container.style.display = 'none';
            document.body.classList.remove('no-scroll');
            // document.body.classList.remove('modal-open'); // Add class to body to disable scroll via CSS

            if (messageArea) {
                messageArea.textContent = '';
                messageArea.style.display = 'none';
                messageArea.classList.remove('info', 'success', 'error');
            }
        }
    };

    function showMessage(message, type) {
        messageArea.textContent = message;
        messageArea.classList.remove('info', 'success', 'error');
        messageArea.classList.add(type);
        messageArea.style.display = 'block';

        setTimeout(() => {
            messageArea.style.display = 'none';
            messageArea.classList.remove('info', 'success', 'error');
            messageArea.textContent = '';
        }, 3000);
    }

    if (update_profile_btn) {
        update_profile_btn.addEventListener('click', window.showPopUp);
    }

    if (back_button) {
        back_button.addEventListener('click', window.hidePopUp);
    }

    if (update_container) {
        update_container.addEventListener('click', (event) => {
            if (event.target === update_container) {
                window.hidePopUp();
            }
        });
    }

    
    profile_pic_option.forEach(img => {
        img.addEventListener('click', () => {
            profile_pic_option.forEach(option => option.classList.remove('selected'));

            img.classList.add('selected');

            selected_pic_id.value = img.dataset.picId;

        });
    });

    if (update_profile_form) {
        update_profile_form.addEventListener('submit', async (event) => {
            event.preventDefault();

            messageArea.textContent = '';
            messageArea.style.display = 'none';
            messageArea.classList.remove('info','success', 'error');
            showMessage('Updating profile...', 'info');

            const formData = new FormData(update_profile_form);
            formData.append('update_profile', 'true');

            const new_username = formData.get('new_username');
            if (!new_username || new_username.trim() === '') {
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

                if (new_weight === null || new_weight === '' || isNaN(new_weight) || parseFloat(new_weight) <= 0) {
                    showMessage('Weight must be a positive number.', 'error');
                    return;
                }
                if (new_height === null || new_height === '' || isNaN(new_height) || parseFloat(new_height) <= 0) {
                    showMessage('Height must be a positive number.', 'error');
                    return;
                }
            }

            try {
                const response = await fetch('../interfaces/update_profile.php', {
                    method: 'POST', 
                    body: formData
                });

                if (!response.ok) {
                    const errorText = await response.text();
                    throw new Error(`HTTP error! Status: ${response.status}, Message: ${errorText}`);
                }

                const result = await response.json();

                if (result.success) {
                    showMessage(result.message, 'success');
                    
                    setTimeout(() => {
                        window.hidePopUp();
                        const profileNameElement = document.querySelector('.user_profile .name h1');
                        if (profileNameElement && result.new_username) {
                            profileNameElement.textContent = result.new_username;
                        }
                        const profilePicElement = document.querySelector('.user_profile .profile_pic img');
                        if (profilePicElement && result.new_profile_pic_src) {
                            profilePicElement.src = result.new_profile_pic_src;
                        }
                    }, 1500);
                } else {
                    showMessage (result.message, 'error');
                }
            } catch (error) {
                console.error('Error: ', error);
                showMessage('An unexpected error occurred. Please try again.', 'error');
            }
        });
    }            


    if (maleRadio && femaleRadio) {
        const maleSelect = maleRadio.closest('label').querySelector('.male_select');
        const femaleSelect = femaleRadio.closest('label').querySelector('.female_select');
        // const maleCheck = maleRadio.closest('label').querySelector('.check');
        // const femaleCheck = femaleRadio.closest('label').querySelector('.check');

        function GenderSelectVisual () {
            if (maleRadio.checked) {
                maleSelect.classList.add('selected');
                // maleCheck.classList.add('selected');
                femaleSelect.classList.remove('selected');
                // femaleCheck.classList.remove('selected');
            } else if (femaleRadio.checked) {
                femaleSelect.classList.add('selected');
                // femaleCheck.classList.add('selected');
                maleSelect.classList.remove('selected');
                // maleCheck.classList.remove('selected');
            } else {
                maleSelect.classList.remove('selected');
                femaleSelect.classList.remove('selected');
            }
        }

        maleRadio.addEventListener('change', GenderSelectVisual);
        femaleRadio.addEventListener('change', GenderSelectVisual);

        GenderSelectVisual();
    }

});