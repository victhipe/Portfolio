// Подключается на страницах register.php и checkout.php

document.addEventListener('DOMContentLoaded', () => {
    const registerForm = document.getElementById('register-form');
    const checkoutForm = document.getElementById('checkout-form');

    if (registerForm) {
        registerForm.addEventListener('submit', function(event) {
            let isValid = true;
            // --- Валидация формы регистрации ---
            const username = document.getElementById('reg_username');
            const email = document.getElementById('reg_email');
            const password = document.getElementById('reg_password');
            const passwordConfirm = document.getElementById('reg_password_confirm');
            const phone = document.getElementById('reg_phone');

             // Очистка предыдущих подсветок
            [username, email, password, passwordConfirm, phone].forEach(clearFieldHighlight);


            // Проверка username (простая, основная на сервере)
            if (!username.value.match(/^[a-zA-Z0-9_]{3,50}$/)) {
                highlightInvalidField(username);
                 isValid = false;
                 // Можно добавить сообщение пользователю рядом с полем
            }

            // Проверка email (простая)
            if (!email.value.match(/^[\w-\.]+@([\w-]+\.)+[\w-]{2,4}$/)) {
                 highlightInvalidField(email);
                 isValid = false;
            }

            // Проверка пароля (длина и совпадение)
            if (password.value.length < 6) {
                 highlightInvalidField(password);
                 isValid = false;
            }
            if (password.value !== passwordConfirm.value) {
                 highlightInvalidField(passwordConfirm);
                 isValid = false;
            }

             // Проверка телефона (простая)
             if (phone.value && !phone.value.match(/^\+?[0-9\s\-\(\)]+$/)) {
                 highlightInvalidField(phone);
                 isValid = false;
             }

            if (!isValid) {
                event.preventDefault(); // Отменяем отправку формы
                alert('Пожалуйста, исправьте ошибки в форме.');
            }
        });
    }

    if (checkoutForm) {
        checkoutForm.addEventListener('submit', function(event) {
            let isValid = true;
             // --- Валидация формы заказа ---
            const name = document.getElementById('checkout_name');
            const phone = document.getElementById('checkout_phone');
            const address = document.getElementById('checkout_address');

            [name, phone, address].forEach(clearFieldHighlight);

            if (name.value.trim() === '') {
                 highlightInvalidField(name);
                 isValid = false;
            }
            if (!phone.value.match(/^\+?[0-9\s\-\(\)]+$/)) {
                 highlightInvalidField(phone);
                 isValid = false;
            }
             if (address.value.trim() === '') {
                 highlightInvalidField(address);
                 isValid = false;
            }

            if (!isValid) {
                event.preventDefault();
                alert('Пожалуйста, проверьте правильность заполнения полей для доставки.');
            }
        });
    }
});