// Этот файл подключается на всех страницах

// Пример: Плавное скрытие flash-сообщений через 5 секунд
document.addEventListener('DOMContentLoaded', () => {
    const flashMessages = document.querySelectorAll('.flash-message');
    flashMessages.forEach(message => {
        setTimeout(() => {
            message.style.transition = 'opacity 0.5s ease';
            message.style.opacity = '0';
            setTimeout(() => message.remove(), 500); // Удалить из DOM после анимации
        }, 5000); // 5 секунд
    });

    // Добавляем слушатели для подтверждения удаления (если такие формы есть на странице)
    const deleteForms = document.querySelectorAll('form[onsubmit*="confirm"]'); // Находим формы с confirm
    deleteForms.forEach(form => {
        // Стандартный onsubmit уже есть, этот код больше для примера,
        // как можно было бы добавить логику без inline JS,
        // но в данном случае inline `onsubmit="return confirm(...)"` уже решает задачу.
        // Если бы мы хотели убрать inline JS:
        /*
        form.addEventListener('submit', function(event) {
            // Ищем текст для подтверждения (например, из data-атрибута кнопки или инпута)
            const confirmMessage = this.dataset.confirmMessage || 'Вы уверены?';
            if (!confirm(confirmMessage)) {
                event.preventDefault(); // Отменяем отправку формы
            }
        });
        */
    });

});

// Простая функция подсветки невалидных полей (можно вызывать из validation.js)
function highlightInvalidField(field) {
    field.style.border = '1px solid red';
    // Можно добавить сообщение об ошибке рядом с полем
}

function clearFieldHighlight(field) {
     field.style.border = ''; // Возвращаем стиль по умолчанию
}
// ... (предыдущий код для flash-сообщений и др.) ...

document.addEventListener('DOMContentLoaded', () => {
    // ... (код для flash-сообщений) ...

    // --- Логика обновления цен для пиццы ---
    const pizzaSizeSelectors = document.querySelectorAll('.pizza-size-select');

    pizzaSizeSelectors.forEach(select => {
        select.addEventListener('change', function() {
            const basePrice = parseFloat(this.dataset.basePrice);
            const selectedOption = this.options[this.selectedIndex];
            const priceFactor = parseFloat(selectedOption.dataset.priceFactor);

            if (!isNaN(basePrice) && !isNaN(priceFactor)) {
                const calculatedPrice = basePrice * priceFactor;
                // Находим соответствующий элемент цены (используя data-item-id или обходя DOM)
                const priceElement = this.closest('.menu-item').querySelector('.dynamic-price');
                if (priceElement) {
                    // Форматируем цену (можно сделать сложнее для разных локалей)
                    priceElement.textContent = calculatedPrice.toLocaleString('ru-RU', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                }
            }
        });

        // Устанавливаем начальную цену при загрузке страницы
        const initialSelectedOption = select.options[select.selectedIndex];
        const initialBasePrice = parseFloat(select.dataset.basePrice);
        const initialPriceFactor = parseFloat(initialSelectedOption.dataset.priceFactor);
        if (!isNaN(initialBasePrice) && !isNaN(initialPriceFactor)) {
             const initialCalculatedPrice = initialBasePrice * initialPriceFactor;
             const initialPriceElement = select.closest('.menu-item').querySelector('.dynamic-price');
             if (initialPriceElement) {
                  initialPriceElement.textContent = initialCalculatedPrice.toLocaleString('ru-RU', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
             }
        }
    });

    // ... (код для подтверждения удаления, если он был) ...
});

// ... (функции highlightInvalidField и clearFieldHighlight, если они есть) ...