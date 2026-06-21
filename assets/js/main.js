/**
 * Furniture Shop – Custom JavaScript
 * Lightweight utilities; Bootstrap 5 handles most interactive components.
 */

'use strict';

// -------------------------------------------------------
// Confirm-before-delete / confirm-before-action
// -------------------------------------------------------
document.addEventListener('DOMContentLoaded', () => {

    // Any form with data-confirm attribute will show a dialog before submit
    document.querySelectorAll('form[data-confirm]').forEach(form => {
        form.addEventListener('submit', e => {
            const msg = form.dataset.confirm || 'Are you sure?';
            if (!confirm(msg)) {
                e.preventDefault();
            }
        });
    });

    // Any link/button with data-confirm attribute
    document.querySelectorAll('[data-confirm]:not(form)').forEach(el => {
        el.addEventListener('click', e => {
            const msg = el.dataset.confirm || 'Are you sure?';
            if (!confirm(msg)) {
                e.preventDefault();
            }
        });
    });

    // Auto-dismiss alerts after 5 seconds
    document.querySelectorAll('.alert-auto-dismiss').forEach(alert => {
        setTimeout(() => {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            bsAlert.close();
        }, 5000);
    });

    // Live total-amount calculation on place-order form
    const qtyInput   = document.getElementById('oqty');
    const priceInput = document.getElementById('fprice');
    const totalEl    = document.getElementById('total_display');

    if (qtyInput && priceInput && totalEl) {
        const recalc = () => {
            const qty   = parseInt(qtyInput.value)   || 0;
            const price = parseFloat(priceInput.value) || 0;
            totalEl.textContent = 'HK$' + (qty * price).toFixed(2);
        };
        qtyInput.addEventListener('input', recalc);
        recalc();
    }
});
