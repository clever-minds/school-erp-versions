/**
 * salary-structure.js
 * Handles all interactive behaviour for the Salary Structure page.
 */
(function () {
    'use strict';

    /* ──────────────────────────────────────────
       Helpers
    ────────────────────────────────────────── */
    function currencyFormat(value) {
        var num = parseFloat(value) || 0;
        return document.getElementById('ss-hidden-currency-symbol').value + num.toLocaleString('en-IN', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
    }

    function getBasicSalary() {
        return parseFloat(document.getElementById('ss-basic-salary').value) || 0;
    }

    /* ──────────────────────────────────────────
       Real-time Salary Calculation
    ────────────────────────────────────────── */
    function calcAllowances() {
        var total = 0;
        var basic = getBasicSalary();

        var container = document.getElementById('allowance-repeater');
        if (container) {
            // New/Edit rows: Amount inputs
            container.querySelectorAll('.ss-row-edit .ss-amount-input:not([disabled])').forEach(function (inp) {
                total += parseFloat(inp.value) || 0;
            });

            // New/Edit rows: Percentage inputs
            container.querySelectorAll('.ss-row-edit .ss-pct-input:not([disabled])').forEach(function (inp) {
                var pct = parseFloat(inp.value) || 0;
                total += (basic * pct) / 100;
            });
        }

        // Existing saved items (using data attributes)
        document.querySelectorAll('.ss-existing-allowance-amount').forEach(function (el) {
            var row = el.closest('.ss-item-row');
            if (row && row.getAttribute('data-state') === 'display') {
                total += parseFloat(el.dataset.value) || 0;
            }
        });

        return total;
    }

    function calcDeductions() {
        var total = 0;
        var basic = getBasicSalary();

        var container = document.getElementById('deduction-repeater');
        if (container) {
            // New/Edit rows: Amount inputs
            container.querySelectorAll('.ss-row-edit .ss-amount-input:not([disabled])').forEach(function (inp) {
                total += parseFloat(inp.value) || 0;
            });

            // New/Edit rows: Percentage inputs
            container.querySelectorAll('.ss-row-edit .ss-pct-input:not([disabled])').forEach(function (inp) {
                var pct = parseFloat(inp.value) || 0;
                total += (basic * pct) / 100;
            });
        }

        // Existing saved items
        document.querySelectorAll('.ss-existing-deduction-amount').forEach(function (el) {
            var row = el.closest('.ss-item-row');
            if (row && row.getAttribute('data-state') === 'display') {
                total += parseFloat(el.dataset.value) || 0;
            }
        });

        return total;
    }

    function updateSummary() {
        var basic = getBasicSalary();
        var allowances = calcAllowances();
        var deductions = calcDeductions();
        var net = basic + allowances - deductions;

        // Update Summary Card
        var elBasic = document.getElementById('ss-live-basic');
        var elAllowances = document.getElementById('ss-live-allowances');
        var elDeductions = document.getElementById('ss-live-deductions');
        var elNet = document.getElementById('ss-live-net');

        if (elBasic) elBasic.textContent = currencyFormat(basic);
        if (elAllowances) elAllowances.textContent = '+' + currencyFormat(allowances);
        if (elDeductions) elDeductions.textContent = '-' + currencyFormat(deductions);
        if (elNet) elNet.textContent = currencyFormat(net);

        // Update Card subtotals
        var elAllowSub = document.getElementById('ss-allowance-subtotal');
        var elDeductSub = document.getElementById('ss-deduction-subtotal');
        if (elAllowSub) elAllowSub.textContent = currencyFormat(allowances);
        if (elDeductSub) elDeductSub.textContent = currencyFormat(deductions);

        // Update hidden inputs
        var hidBasic = document.getElementById('ss-hidden-basic');
        var hidAllow = document.getElementById('ss-hidden-allowance');
        var hidDeduct = document.getElementById('ss-hidden-deduction');
        var hidNet = document.getElementById('ss-hidden-net');
        if (hidBasic) hidBasic.value = basic;
        if (hidAllow) hidAllow.value = allowances;
        if (hidDeduct) hidDeduct.value = deductions;
        if (hidNet) hidNet.value = net;
    }

    /* ──────────────────────────────────────────
       UI Sync: Labels
    ────────────────────────────────────────── */
    function syncDisplayLabels(row) {
        var select = row.querySelector('.ss-new-row-select');
        var opt = select.options[select.selectedIndex];
        if (!opt || !select.value) return;

        var nameLabel = row.querySelector('.ss-display-name');
        var subtextLabel = row.querySelector('.ss-display-subtext');
        var valueLabel = row.querySelector('.ss-display-value');

        var name = opt.getAttribute('data-name') || '---';
        var type = opt.getAttribute('data-type');
        var val = 0;

        if (type === 'amount') {
            var amt = parseFloat(row.querySelector('.ss-amount-input').value) || 0;
            subtextLabel.textContent = 'Fixed Amount';
            val = amt;
        } else {
            var pct = parseFloat(row.querySelector('.ss-pct-input').value) || 0;
            subtextLabel.textContent = pct + '% of Basic';
            val = (getBasicSalary() * pct) / 100;
        }

        nameLabel.textContent = name;
        valueLabel.textContent = currencyFormat(val);

        // Also update data-value for existing rows calculation logic
        var existingData = row.querySelector('.ss-existing-allowance-amount, .ss-existing-deduction-amount');
        if (existingData) existingData.dataset.value = val;
    }

    /* ──────────────────────────────────────────
       Inputs Visibility Toggle
    ────────────────────────────────────────── */
    function handleTypeChange(selectEl) {
        var row = selectEl.closest('.ss-item-row');
        if (!row) return;

        var opt = selectEl.options[selectEl.selectedIndex];
        var type = opt ? opt.getAttribute('data-type') : '';
        var defaultValue = opt ? opt.getAttribute('data-value') : '';

        var amountWrap = row.querySelector('.ss-amount-wrap');
        var pctWrap = row.querySelector('.ss-pct-wrap');
        var amountInp = row.querySelector('.ss-amount-input');
        var pctInp = row.querySelector('.ss-pct-input');

        if (type === 'amount') {
            amountWrap.style.display = 'block';
            pctWrap.style.display = 'none';
            if (amountInp) { amountInp.disabled = false; if (!amountInp.value) amountInp.value = defaultValue; }
            if (pctInp) { pctInp.disabled = true; }
        } else if (type === 'percentage') {
            amountWrap.style.display = 'none';
            pctWrap.style.display = 'block';
            if (pctInp) { pctInp.disabled = false; if (!pctInp.value) pctInp.value = defaultValue; }
            if (amountInp) { amountInp.disabled = true; }
        } else {
            amountWrap.style.display = 'none';
            pctWrap.style.display = 'none';
            if (amountInp) amountInp.disabled = true;
            if (pctInp) pctInp.disabled = true;
        }
        updateSummary();
    }

    /* ──────────────────────────────────────────
       Repeater / State Actions
    ────────────────────────────────────────── */
    function addNewRow(containerId, counterAttr) {
        var container = document.getElementById(containerId);
        var template = container.querySelector('[data-template]');
        var counter = parseInt(container.getAttribute(counterAttr)) || 1;

        var clone = template.cloneNode(true);
        clone.removeAttribute('data-template');
        clone.style.display = 'flex';
        clone.setAttribute('data-state', 'edit');

        // Update name indexes
        clone.querySelectorAll('[name]').forEach(function (el) {
            var name = el.getAttribute('name');
            el.setAttribute('name', name.replace(/\[\d+\]/, '[' + counter + ']'));
        });

        // Event listeners
        var select = clone.querySelector('.ss-new-row-select');
        select.addEventListener('change', function () { handleTypeChange(select); });
        clone.querySelectorAll('input').forEach(i => i.addEventListener('input', updateSummary));

        // Insert before add button
        var addBtnContainer = container.querySelector('.ss-add-row');
        container.insertBefore(clone, addBtnContainer);

        container.setAttribute(counterAttr, counter + 1);
    }

    function deleteExistingRow(btn) {
        var rowId = btn.getAttribute('data-id');
        var row = btn.closest('.ss-item-row');
        if (!confirm('Are you sure you want to remove this adjustment?')) return;

        var token = document.querySelector('meta[name="csrf-token"]').content;
        var url = window.payrollDeleteUrl + '/' + rowId;

        fetch(url, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' } })
            .then(res => res.json())
            .then(data => {
                if (data.status === true || data.success === true) {
                    row.remove();
                    updateSummary();
                } else { alert(data.message || 'Error occurred.'); }
            })
            .catch(() => alert('Network error.'));
    }

    /* ──────────────────────────────────────────
       Initialization & Delegation
    ────────────────────────────────────────── */
    document.addEventListener('DOMContentLoaded', function () {

        // Global delegation for dynamic buttons
        document.addEventListener('click', function (e) {
            var row = e.target.closest('.ss-item-row');

            // Edit
            if (e.target.closest('.btn-ss-edit')) {
                row.setAttribute('data-state', 'edit');
                updateSummary();
                return;
            }

            // Confirm
            if (e.target.closest('.btn-ss-confirm')) {
                var select = row.querySelector('.ss-new-row-select');
                if (!select.value) { alert('Please select a type.'); return; }

                syncDisplayLabels(row);
                row.setAttribute('data-state', 'display');
                updateSummary();
                return;
            }

            // Cancel (New)
            if (e.target.closest('.ss-remove-new-row')) {
                row.remove();
                updateSummary();
                return;
            }

            // Cancel (Existing)
            if (e.target.closest('.btn-ss-cancel-existing')) {
                row.setAttribute('data-state', 'display');
                updateSummary();
                return;
            }

            // Delete Existing
            var delBtn = e.target.closest('.ss-delete-existing');
            if (delBtn) {
                deleteExistingRow(delBtn);
                return;
            }

            // Add Buttons
            if (e.target.closest('#btn-add-allowance')) {
                addNewRow('allowance-repeater', 'data-counter');
                return;
            }
            if (e.target.closest('#btn-add-deduction')) {
                addNewRow('deduction-repeater', 'data-counter');
                return;
            }
        });

        // Delegation for dropdowns
        document.addEventListener('change', function (e) {
            if (e.target.classList.contains('ss-new-row-select')) {
                handleTypeChange(e.target);
            }
        });

        // Basic salary recalc
        var basicInp = document.getElementById('ss-basic-salary');
        if (basicInp) basicInp.addEventListener('input', updateSummary);

        // Reset
        var resetBtn = document.getElementById('btn-ss-reset');
        if (resetBtn) resetBtn.addEventListener('click', () => {
            // add swal fire
            Swal.fire({
                title: window.trans['Are you sure'],
                text: window.trans['discard_changes'],
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: window.trans['Yes']
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.reload();
                }
            });
        });

        // Form loading
        var form = document.getElementById('ss-payroll-form');
        if (form) {
            form.addEventListener('submit', () => {
                var btn = document.getElementById('btn-save-structure');
                btn.disabled = true;
                btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Saving...';
            });
        }

        setTimeout(updateSummary, 200);
    });

    window.formSuccessFunction = (r) => setTimeout(() => window.location.reload(), 1500);

})();
