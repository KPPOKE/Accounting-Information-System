function initCustomDropdowns() {
    const dropdowns = document.querySelectorAll('.custom-dropdown');

    dropdowns.forEach(dropdown => {
        const trigger = dropdown.querySelector('.dropdown-trigger');
        const menu = dropdown.querySelector('.dropdown-menu');
        const items = dropdown.querySelectorAll('.dropdown-item');
        const valueDisplay = dropdown.querySelector('.dropdown-value');
        const hiddenInput = dropdown.querySelector('input[type="hidden"]');

        if (!trigger) return;

        trigger.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();

            document.querySelectorAll('.custom-dropdown.open').forEach(d => {
                if (d !== dropdown) d.classList.remove('open');
            });

            dropdown.classList.toggle('open');
        });

        items.forEach(item => {
            item.addEventListener('click', () => {
                const value = item.dataset.value;
                const text = item.textContent.trim();

                items.forEach(i => i.classList.remove('active'));
                item.classList.add('active');

                if (valueDisplay) valueDisplay.textContent = text;

                if (hiddenInput) hiddenInput.value = value;

                dropdown.classList.remove('open');

                dropdown.dispatchEvent(new CustomEvent('dropdown:change', {
                    detail: { value, text }
                }));

                if (dropdown.hasAttribute('data-submit')) {
                    const form = dropdown.closest('form');
                    if (form) form.submit();
                }

                const chartType = dropdown.dataset.chart;
                if (chartType && typeof updateChart === 'function') {
                    if (chartType === 'revenue_expense' && typeof revenueExpenseChart !== 'undefined') {
                        updateChart('revenue_expense', value, revenueExpenseChart);
                    } else if (chartType === 'cash_flow' && typeof cashFlowChart !== 'undefined') {
                        updateChart('cash_flow', value, cashFlowChart);
                    }
                }
            });
        });
    });

    document.addEventListener('click', () => {
        document.querySelectorAll('.custom-dropdown.open').forEach(d => {
            d.classList.remove('open');
        });
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            document.querySelectorAll('.custom-dropdown.open').forEach(d => {
                d.classList.remove('open');
            });
        }
    });
}

function createCustomDropdown(options) {
    const { name, items, placeholder = 'Pilih...', submit = false } = options;

    const selectedItem = items.find(item => item.selected) || { value: '', text: placeholder };

    let html = `<div class="custom-dropdown"${submit ? ' data-submit' : ''}>`;
    html += `<input type="hidden" name="${name}" value="${selectedItem.value}">`;
    html += `<button class="dropdown-trigger" type="button">`;
    html += `<span class="dropdown-value">${selectedItem.text}</span>`;
    html += `<i class="fas fa-chevron-down dropdown-arrow"></i>`;
    html += `</button>`;
    html += `<div class="dropdown-menu">`;

    items.forEach(item => {
        const activeClass = item.selected ? ' active' : '';
        html += `<div class="dropdown-item${activeClass}" data-value="${item.value}">${item.text}</div>`;
    });

    html += `</div></div>`;

    return html;
}

document.addEventListener('DOMContentLoaded', initCustomDropdowns);
