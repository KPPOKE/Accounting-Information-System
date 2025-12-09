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
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer);
            toast.addEventListener('mouseleave', Swal.resumeTimer);
        }
    });

    Toast.fire({
        icon: type === 'success' ? 'success' : type === 'error' ? 'error' : 'info',
        title: message
    });
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
    singleDateInputs.forEach(function(input) {
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
            onChange: function(selectedDates) {
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
            onChange: function(selectedDates) {
                if (selectedDates.length > 0) {
                    fpFrom.set('maxDate', selectedDates[0]);
                }
            }
        });
    }
}
