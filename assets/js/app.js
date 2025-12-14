document.addEventListener('DOMContentLoaded', function () {
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    const sidebarNav = document.querySelector('.sidebar-nav');
    const mainContent = document.querySelector('.main-content');

    function isMobile() {
        return window.innerWidth <= 1024;
    }

    function applySidebarState() {
        if (!isMobile()) {
            const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
            if (isCollapsed) {
                sidebar.classList.add('collapsed');
                if (mainContent) mainContent.classList.add('expanded');
            }
        }
    }

    if (sidebar) {
        applySidebarState();
    }

    if (sidebarNav) {
        const savedScrollPos = localStorage.getItem('sidebarScrollPos');
        if (savedScrollPos) {
            sidebarNav.scrollTop = parseInt(savedScrollPos, 10);
        }

        sidebarNav.addEventListener('scroll', function () {
            localStorage.setItem('sidebarScrollPos', sidebarNav.scrollTop);
        });
    }

    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function () {
            if (isMobile()) {
                sidebar.classList.toggle('open');
            } else {
                const isCollapsed = !document.documentElement.classList.contains('sidebar-is-collapsed');
                if (isCollapsed) {
                    document.documentElement.classList.add('sidebar-is-collapsed');
                    sidebar.classList.add('collapsed');
                    if (mainContent) mainContent.classList.add('expanded');
                } else {
                    document.documentElement.classList.remove('sidebar-is-collapsed');
                    sidebar.classList.remove('collapsed');
                    if (mainContent) mainContent.classList.remove('expanded');
                }
                localStorage.setItem('sidebarCollapsed', isCollapsed);
            }
        });
    }

    document.addEventListener('click', function (e) {
        if (isMobile() && sidebar && sidebar.classList.contains('open') &&
            !sidebar.contains(e.target) &&
            !sidebarToggle.contains(e.target)) {
            sidebar.classList.remove('open');
        }
    });

    window.addEventListener('resize', function () {
        if (!isMobile()) {
            sidebar.classList.remove('open');
            applySidebarState();
        } else {
            sidebar.classList.remove('collapsed');
            if (mainContent) mainContent.classList.remove('expanded');
        }
    });
});

function showToast(message, type = 'success') {
    // Create custom toast without SweetAlert2 overlay issues
    const toastContainer = document.getElementById('custom-toast-container') || createToastContainer();

    const toast = document.createElement('div');
    toast.className = `custom-toast custom-toast-${type}`;

    const iconMap = {
        success: '<i class="fas fa-check-circle"></i>',
        error: '<i class="fas fa-times-circle"></i>',
        info: '<i class="fas fa-info-circle"></i>',
        warning: '<i class="fas fa-exclamation-circle"></i>'
    };

    toast.innerHTML = `
        <div class="custom-toast-icon">${iconMap[type] || iconMap.info}</div>
        <div class="custom-toast-message">${message}</div>
        <div class="custom-toast-progress"></div>
    `;

    toastContainer.appendChild(toast);

    // Trigger animation
    setTimeout(() => toast.classList.add('show'), 10);

    // Auto remove after 3 seconds
    setTimeout(() => {
        toast.classList.remove('show');
        toast.classList.add('hide');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

function createToastContainer() {
    const container = document.createElement('div');
    container.id = 'custom-toast-container';
    document.body.appendChild(container);
    return container;
}

function confirmDelete(message, callback) {
    Swal.fire({
        title: 'Konfirmasi Hapus',
        text: message || 'Apakah Anda yakin ingin menghapus data ini?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal',
        reverseButtons: true,
        customClass: {
            confirmButton: 'swal-btn-confirm',
            cancelButton: 'swal-btn-cancel'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            callback();
        }
    });
}

function confirmAction(title, message, confirmText, callback) {
    Swal.fire({
        title: title,
        text: message,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: confirmText || 'Ya, Lanjutkan!',
        cancelButtonText: 'Batal',
        reverseButtons: true,
        customClass: {
            confirmButton: 'swal-btn-confirm',
            cancelButton: 'swal-btn-cancel'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            callback();
        }
    });
}

function formatCurrency(amount) {
    return 'Rp ' + new Intl.NumberFormat('id-ID').format(amount);
}

function formatNumber(input) {
    let value = input.value.replace(/[^\d]/g, '');
    input.value = new Intl.NumberFormat('id-ID').format(value);
}

function validateDoubleEntry() {
    const debits = document.querySelectorAll('.debit-input');
    const credits = document.querySelectorAll('.credit-input');

    let totalDebit = 0;
    let totalCredit = 0;

    debits.forEach(function (input) {
        totalDebit += parseFloat(input.value.replace(/[^\d]/g, '') || 0);
    });

    credits.forEach(function (input) {
        totalCredit += parseFloat(input.value.replace(/[^\d]/g, '') || 0);
    });

    const balanceInfo = document.getElementById('balanceInfo');
    const submitBtn = document.getElementById('submitBtn');

    if (balanceInfo) {
        if (totalDebit === totalCredit && totalDebit > 0) {
            balanceInfo.className = 'alert alert-success';
            balanceInfo.innerHTML = '<i class="fas fa-check-circle"></i> Balance: Debit dan Kredit seimbang (' + formatCurrency(totalDebit) + ')';
            if (submitBtn) submitBtn.disabled = false;
        } else {
            balanceInfo.className = 'alert alert-danger';
            balanceInfo.innerHTML = '<i class="fas fa-exclamation-circle"></i> Tidak seimbang! Debit: ' + formatCurrency(totalDebit) + ' | Kredit: ' + formatCurrency(totalCredit);
            if (submitBtn) submitBtn.disabled = true;
        }
    }

    return totalDebit === totalCredit && totalDebit > 0;
}

function addJournalRow() {
    const tbody = document.getElementById('journalDetailsBody');
    const rowCount = tbody.getElementsByTagName('tr').length;

    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td>
            <select name="details[${rowCount}][account_id]" class="form-select" required>
                <option value="">Pilih Akun</option>
            </select>
        </td>
        <td>
            <input type="text" name="details[${rowCount}][description]" class="form-control" placeholder="Keterangan">
        </td>
        <td>
            <input type="text" name="details[${rowCount}][debit]" class="form-control debit-input" placeholder="0" onkeyup="formatNumber(this); validateDoubleEntry();">
        </td>
        <td>
            <input type="text" name="details[${rowCount}][credit]" class="form-control credit-input" placeholder="0" onkeyup="formatNumber(this); validateDoubleEntry();">
        </td>
        <td>
            <button type="button" class="btn btn-danger btn-icon btn-sm" onclick="removeJournalRow(this)">
                <i class="fas fa-trash"></i>
            </button>
        </td>
    `;

    tbody.appendChild(tr);

    const existingSelect = document.querySelector('select[name="details[0][account_id]"]');
    const newSelect = tr.querySelector('select');
    if (existingSelect && newSelect) {
        newSelect.innerHTML = existingSelect.innerHTML;
    }
}

function removeJournalRow(button) {
    const tr = button.closest('tr');
    const tbody = tr.parentElement;

    if (tbody.getElementsByTagName('tr').length > 2) {
        tr.remove();
        validateDoubleEntry();
    } else {
        showToast('Minimal harus ada 2 baris detail', 'error');
    }
}

document.addEventListener('DOMContentLoaded', function () {
    // Initialize Dark Mode
    initDarkMode();

    // Add click event to theme toggle button
    const themeToggle = document.getElementById('themeToggle');
    if (themeToggle) {
        themeToggle.addEventListener('click', toggleDarkMode);
    }

    // Form validation
    const forms = document.querySelectorAll('form[data-validate]');
    forms.forEach(function (form) {
        form.addEventListener('submit', function (e) {
            const requiredFields = form.querySelectorAll('[required]');
            let valid = true;

            requiredFields.forEach(function (field) {
                if (!field.value.trim()) {
                    valid = false;
                    field.classList.add('error');
                } else {
                    field.classList.remove('error');
                }
            });

            if (!valid) {
                e.preventDefault();
                showToast('Mohon lengkapi semua field yang wajib diisi', 'error');
            }
        });
    });

    initializeFlatpickr();
});

function initializeFlatpickr() {
    if (typeof flatpickr === 'undefined') {
        return;
    }

    flatpickr.localize(flatpickr.l10ns.id);

    const singleDateInputs = document.querySelectorAll('input[type="date"]');
    singleDateInputs.forEach(function (input) {
        if (!input.placeholder) {
            input.placeholder = 'Pilih Tanggal';
        }

        flatpickr(input, {
            dateFormat: 'Y-m-d',
            altInput: true,
            altFormat: 'd F Y',
            locale: 'id',
            disableMobile: false,
            allowInput: true
        });
    });

    const dateFromInput = document.querySelector('input[name="date_from"]');
    const dateToInput = document.querySelector('input[name="date_to"]');

    if (dateFromInput && dateToInput) {
        if (!dateFromInput.placeholder) {
            dateFromInput.placeholder = 'Dari Tanggal';
        }
        if (!dateToInput.placeholder) {
            dateToInput.placeholder = 'Sampai Tanggal';
        }

        const fpFrom = flatpickr(dateFromInput, {
            dateFormat: 'Y-m-d',
            altInput: true,
            altFormat: 'd F Y',
            locale: 'id',
            disableMobile: false,
            allowInput: true,
            onChange: function (selectedDates) {
                if (selectedDates.length > 0) {
                    fpTo.set('minDate', selectedDates[0]);
                }
            }
        });

        const fpTo = flatpickr(dateToInput, {
            dateFormat: 'Y-m-d',
            altInput: true,
            altFormat: 'd F Y',
            locale: 'id',
            disableMobile: false,
            allowInput: true,
            onChange: function (selectedDates) {
                if (selectedDates.length > 0) {
                    fpFrom.set('maxDate', selectedDates[0]);
                }
            }
        });
    }
}

// Dark Mode Toggle
function initDarkMode() {
    // Check for saved theme preference or default to light mode
    const savedTheme = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-theme', savedTheme);
    console.log('Dark mode initialized with theme:', savedTheme);

    // Remove preload class immediately to prevent overlay issues
    document.body.classList.remove('preload');

    // Apply Flatpickr theme based on current theme
    applyFlatpickrTheme(savedTheme);
}

function toggleDarkMode() {
    const currentTheme = document.documentElement.getAttribute('data-theme');
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';

    console.log('Toggling theme from', currentTheme, 'to', newTheme);

    // Add theme-changing class to prevent transition issues
    document.body.classList.add('theme-changing');

    // Apply new theme
    document.documentElement.setAttribute('data-theme', newTheme);
    localStorage.setItem('theme', newTheme);

    // Apply Flatpickr theme
    applyFlatpickrTheme(newTheme);

    // Remove theme-changing class after a brief moment
    setTimeout(() => {
        document.body.classList.remove('theme-changing');
    }, 50);

    // Show toast notification
    const message = newTheme === 'dark' ? 'Dark Mode Enabled' : 'Light Mode Enabled';
    showToast(message, 'success');
}

// Dynamically apply Flatpickr dark theme with inline styles for maximum specificity
function applyFlatpickrTheme(theme) {
    const FLATPICKR_DARK_THEME_ID = 'flatpickr-dark-theme-inline';

    let existingStyle = document.getElementById(FLATPICKR_DARK_THEME_ID);

    if (theme === 'dark') {
        // Add dark theme inline styles if not already present
        if (!existingStyle) {
            const style = document.createElement('style');
            style.id = FLATPICKR_DARK_THEME_ID;
            style.textContent = `
                /* Flatpickr Dark Theme - Inline CSS */
                .flatpickr-calendar {
                    background: #1e293b !important;
                    border: 1px solid #334155 !important;
                    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5) !important;
                }
                .flatpickr-calendar.arrowTop:after {
                    border-bottom-color: #1e293b !important;
                }
                .flatpickr-calendar.arrowTop:before {
                    border-bottom-color: #334155 !important;
                }
                .flatpickr-calendar.arrowBottom:after {
                    border-top-color: #1e293b !important;
                }
                .flatpickr-calendar.arrowBottom:before {
                    border-top-color: #334155 !important;
                }
                .flatpickr-months {
                    background: #1e293b !important;
                }
                .flatpickr-months .flatpickr-month {
                    background: #1e293b !important;
                    color: #f1f5f9 !important;
                    fill: #f1f5f9 !important;
                }
                .flatpickr-current-month .flatpickr-monthDropdown-months {
                    background: #1e293b !important;
                    color: #f1f5f9 !important;
                }
                .flatpickr-current-month .flatpickr-monthDropdown-months .flatpickr-monthDropdown-month {
                    background: #1e293b !important;
                    color: #f1f5f9 !important;
                }
                .flatpickr-current-month input.cur-year {
                    color: #f1f5f9 !important;
                }
                .flatpickr-months .flatpickr-prev-month,
                .flatpickr-months .flatpickr-next-month {
                    color: #f1f5f9 !important;
                    fill: #f1f5f9 !important;
                }
                .flatpickr-months .flatpickr-prev-month:hover svg,
                .flatpickr-months .flatpickr-next-month:hover svg {
                    fill: #818cf8 !important;
                }
                .flatpickr-weekdays {
                    background: #1e293b !important;
                }
                span.flatpickr-weekday {
                    background: #1e293b !important;
                    color: #94a3b8 !important;
                }
                .flatpickr-days {
                    background: #1e293b !important;
                    border: none !important;
                }
                .dayContainer {
                    background: #1e293b !important;
                }
                .flatpickr-day {
                    color: #f1f5f9 !important;
                    background: transparent !important;
                    border-color: transparent !important;
                }
                .flatpickr-day:hover {
                    background: #334155 !important;
                    border-color: #334155 !important;
                    color: #f1f5f9 !important;
                }
                .flatpickr-day.today {
                    border-color: #818cf8 !important;
                }
                .flatpickr-day.selected,
                .flatpickr-day.startRange,
                .flatpickr-day.endRange,
                .flatpickr-day.selected.inRange,
                .flatpickr-day.startRange.inRange,
                .flatpickr-day.endRange.inRange,
                .flatpickr-day.selected:focus,
                .flatpickr-day.startRange:focus,
                .flatpickr-day.endRange:focus,
                .flatpickr-day.selected:hover,
                .flatpickr-day.startRange:hover,
                .flatpickr-day.endRange:hover {
                    background: #818cf8 !important;
                    border-color: #818cf8 !important;
                    color: white !important;
                }
                .flatpickr-day.inRange {
                    background: rgba(129, 140, 248, 0.3) !important;
                    border-color: transparent !important;
                    box-shadow: none !important;
                }
                .flatpickr-day.prevMonthDay,
                .flatpickr-day.nextMonthDay {
                    color: #64748b !important;
                }
                .flatpickr-day.prevMonthDay:hover,
                .flatpickr-day.nextMonthDay:hover {
                    background: #334155 !important;
                    border-color: #334155 !important;
                }
                .flatpickr-day.disabled,
                .flatpickr-day.disabled:hover {
                    color: #475569 !important;
                }
                .flatpickr-time {
                    background: #1e293b !important;
                    border-top: 1px solid #334155 !important;
                }
                .flatpickr-time input {
                    color: #f1f5f9 !important;
                    background: #1e293b !important;
                }
                .flatpickr-time .flatpickr-time-separator,
                .flatpickr-time .flatpickr-am-pm {
                    color: #f1f5f9 !important;
                }
                .numInputWrapper span {
                    border-color: #334155 !important;
                }
                .numInputWrapper:hover {
                    background: #334155 !important;
                }
                .numInputWrapper span.arrowUp:after {
                    border-bottom-color: #94a3b8 !important;
                }
                .numInputWrapper span.arrowDown:after {
                    border-top-color: #94a3b8 !important;
                }
            `;
            document.head.appendChild(style);
            console.log('Flatpickr dark theme (inline) applied');
        }
    } else {
        // Remove dark theme if present
        if (existingStyle) {
            existingStyle.remove();
            console.log('Flatpickr dark theme (inline) removed');
        }
    }
}

