/**
 * ACL manager page behaviour: route search and filters, the rules offcanvas and the protect-route wizard.
 *
 * Plain JavaScript, no build step. It selects only by the neutral hooks the templates in
 * templates/default/acl carry (acl-*, badge-xs, data-*). Listeners are delegated from `document` and
 * registered once, so an HTMX body swap does not add them again.
 */
(function () {
    'use strict';

    if (window.webwareAclManager) {
        return;
    }
    window.webwareAclManager = true;

    const TOTAL_STEPS = 5;

    const state = {
        step: 1,
        routeName: '',
        methods: [],
        privs: [],
        ruleType: 'Allow',
        roleId: '',
        selectedPrivs: [],
        assertionAliases: [],
    };

    let activeFilter = 'all';

    const byId = (id) => document.getElementById(id);
    const all = (selector) => document.querySelectorAll(selector);

    const getModal = () => byId('protectWizardModal');

    function showStep(n) {
        state.step = n;
        for (let i = 1; i <= TOTAL_STEPS; i++) {
            const panel = byId('wiz-panel-' + i);
            if (panel) {
                panel.classList.toggle('d-none', i !== n);
            }
        }

        all('#wiz-stepper .acl-wiz-step').forEach((el, idx) => {
            el.classList.remove('active', 'done');
            if (idx + 1 < n) {
                el.classList.add('done');
            }
            if (idx + 1 === n) {
                el.classList.add('active');
            }
        });

        const back = byId('acl-wiz-back');
        const next = byId('acl-wiz-next');
        const save = byId('acl-wiz-save');
        if (back) {
            back.disabled = (n === 1);
        }
        if (next) {
            next.classList.toggle('d-none', n === TOTAL_STEPS);
        }
        if (save) {
            save.classList.toggle('d-none', n !== TOTAL_STEPS);
        }

        if (n === TOTAL_STEPS) {
            populateReview();
        }
    }

    function initWizard(btn) {
        state.routeName = btn.dataset.routeName || '';
        state.methods = JSON.parse(btn.dataset.methods || '[]');
        state.privs = JSON.parse(btn.dataset.privs || '[]');
        state.ruleType = 'Allow';
        state.roleId = '';
        state.selectedPrivs = [];
        state.assertionAliases = [];

        const routeDisplay = byId('wiz-route-display');
        if (routeDisplay) {
            routeDisplay.textContent = state.routeName;
        }

        const methodsSpan = byId('wiz-header-methods');
        if (methodsSpan) {
            methodsSpan.innerHTML = state.methods.map((m) =>
                '<span class="acl-method-pill acl-method-' + m + '">' + m + '</span>'
            ).join('');
        }

        const privsSpan = byId('wiz-header-privs');
        if (privsSpan) {
            privsSpan.innerHTML = state.privs.map((p) =>
                '<span class="badge bg-transparent border acl-priv-' + p + ' badge-xs">' + p + '</span>'
            ).join('');
        }

        // Privilege cards for step 3 are auto-selected and display-only.
        const privCards = byId('wiz-priv-cards');
        if (privCards) {
            privCards.innerHTML = state.privs.map((p) =>
                '<div class="acl-priv-card selected-' + p + '" data-priv="' + p + '">'
                + '<i class="bi bi-key-fill me-1"></i>' + p
                + '</div>'
            ).join('');
        }
        state.selectedPrivs = state.privs.slice();

        const noneAssert = document.querySelector('[data-assertion="none"]');
        if (noneAssert) {
            toggleAssertion(noneAssert);
        }

        all('.acl-grant-card').forEach((c) => c.classList.remove('selected'));

        all('.acl-role-item').forEach((el) => {
            el.classList.remove('selected');
            el.setAttribute('aria-selected', 'false');
        });

        const roleSearch = byId('acl-role-search');
        if (roleSearch) {
            roleSearch.value = '';
            filterRoleTree('');
        }

        const propAlert = byId('wiz-propagation-alert');
        if (propAlert) {
            propAlert.classList.add('d-none');
        }

        byId('wiz-input-route-name').value = state.routeName;
        byId('wiz-input-rule-type').value = 'Allow';

        showStep(1);
    }

    function selectGrant(el) {
        all('.acl-grant-card').forEach((c) => c.classList.remove('selected'));
        el.classList.add('selected');
        state.ruleType = el.dataset.aclStepGrant || 'Allow';
        byId('wiz-input-rule-type').value = state.ruleType;
    }

    function selectRole(el) {
        all('.acl-role-item').forEach((r) => {
            r.classList.remove('selected');
            r.setAttribute('aria-selected', 'false');
        });
        el.classList.add('selected');
        el.setAttribute('aria-selected', 'true');
        state.roleId = el.dataset.roleId || '';
        byId('wiz-input-role-id').value = state.roleId;
    }

    function filterRoleTree(query) {
        const q = query.toLowerCase();
        all('.acl-role-item').forEach((el) => {
            const label = (el.dataset.roleId || '').toLowerCase();
            const ancestry = (el.dataset.ancestry || '').toLowerCase();
            el.style.display = (!q || label.includes(q) || ancestry.includes(q)) ? '' : 'none';
        });
    }

    function syncAssertionInputs() {
        const container = byId('wiz-assertion-inputs');
        if (!container) {
            return;
        }
        container.innerHTML = '';

        // One alias posts as `assertions`, several as `assertions[]`, none posts nothing.
        const name = state.assertionAliases.length === 1 ? 'assertions' : 'assertions[]';
        state.assertionAliases.forEach((alias) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = name;
            input.value = alias;
            container.appendChild(input);
        });
    }

    function toggleAssertion(el) {
        const noneCard = document.querySelector('[data-assertion="none"]');

        if (el.dataset.assertion === 'none') {
            all('.acl-assertion-card').forEach((c) => c.classList.remove('selected'));
            el.classList.add('selected');
            state.assertionAliases = [];
        } else {
            const alias = el.dataset.assertionAlias || '';
            const idx = state.assertionAliases.indexOf(alias);
            if (idx !== -1) {
                state.assertionAliases.splice(idx, 1);
                el.classList.remove('selected');
                if (state.assertionAliases.length === 0 && noneCard) {
                    noneCard.classList.add('selected');
                }
            } else {
                if (noneCard) {
                    noneCard.classList.remove('selected');
                }
                state.assertionAliases.push(alias);
                el.classList.add('selected');
            }
        }
        syncAssertionInputs();
    }

    function populateReview() {
        const set = (id, val) => {
            const el = byId(id);
            if (el) {
                el.textContent = val;
            }
        };
        set('wiz-review-route', state.routeName);
        set('wiz-review-role', state.roleId || '(none selected)');

        const typeEl = byId('wiz-review-type');
        if (typeEl) {
            typeEl.innerHTML = state.ruleType === 'Allow'
                ? '<span class="badge bg-success-subtle border border-success-subtle text-success-emphasis">Allow</span>'
                : '<span class="badge bg-danger-subtle border border-danger-subtle text-danger-emphasis">Deny</span>';
        }

        const privsEl = byId('wiz-review-privs');
        if (privsEl) {
            privsEl.innerHTML = state.selectedPrivs.length
                ? state.selectedPrivs.map((p) =>
                    '<span class="badge bg-transparent border acl-priv-' + p + ' badge-xs">' + p + '</span>'
                ).join('')
                : '<span class="text-secondary">(none)</span>';
        }

        const assertRow = byId('wiz-review-assert-row');
        const assertEl = byId('wiz-review-assertion');
        if (state.assertionAliases.length > 0) {
            if (assertRow) {
                assertRow.classList.remove('d-none');
            }
            if (assertEl) {
                assertEl.textContent = state.assertionAliases.join(', ');
            }
        } else if (assertRow) {
            assertRow.classList.add('d-none');
        }
    }

    function canAdvance() {
        if (state.step === 1) {
            return !!document.querySelector('.acl-grant-card.selected');
        }
        if (state.step === 2) {
            return !!state.roleId;
        }
        return true;
    }

    function filterRoutes() {
        const q = ((byId('acl-route-search') || {}).value || '').toLowerCase();
        all('.acl-route-entry').forEach((row) => {
            const name = (row.dataset.routeName || '').toLowerCase();
            const status = row.dataset.status || 'unprotected';
            const matchFilter = activeFilter === 'all' || activeFilter === status;
            const matchSearch = !q || name.includes(q);
            row.style.display = (matchFilter && matchSearch) ? '' : 'none';

            // A hidden route's rules panel is hidden with it.
            const next = row.nextElementSibling;
            if (next && next.classList.contains('acl-rules-panel') && (!matchFilter || !matchSearch)) {
                next.classList.add('d-none');
            }
        });
    }

    function openRulesOffcanvas(trigger) {
        const source = trigger.dataset.rulesPanel ? byId(trigger.dataset.rulesPanel) : null;
        const body = byId('acl-offcanvas-body');
        const titleEl = byId('acl-offcanvas-route-name');
        if (titleEl) {
            titleEl.textContent = trigger.dataset.routeName || '';
        }
        if (body) {
            body.innerHTML = source ? source.innerHTML : '<p class="text-secondary small">No rules found.</p>';
            htmx.process(body);
        }
        bootstrap.Offcanvas.getOrCreateInstance(byId('acl-rule-offcanvas')).show();
    }

    document.addEventListener('click', (e) => {
        const modal = getModal();
        if (!modal) {
            return; // not on the ACL page
        }

        const wizardBtn = e.target.closest('[data-wizard-action]');
        if (wizardBtn) {
            initWizard(wizardBtn);
            bootstrap.Modal.getOrCreateInstance(modal).show();
            return;
        }

        const offcanvasTrigger = e.target.closest('.acl-rules-offcanvas-trigger');
        if (offcanvasTrigger) {
            openRulesOffcanvas(offcanvasTrigger);
            return;
        }

        const filterBtn = e.target.closest('[data-acl-filter]');
        if (filterBtn) {
            activeFilter = filterBtn.dataset.aclFilter || 'all';
            all('[data-acl-filter]').forEach((b) => b.classList.remove('active'));
            filterBtn.classList.add('active');
            filterRoutes();
            return;
        }

        const grantCard = e.target.closest('[data-acl-step-grant]');
        if (grantCard && modal.contains(grantCard)) {
            selectGrant(grantCard);
            return;
        }

        const roleItem = e.target.closest('.acl-role-item');
        if (roleItem && modal.contains(roleItem)) {
            selectRole(roleItem);
            return;
        }

        const assertCard = e.target.closest('.acl-assertion-card');
        if (assertCard && modal.contains(assertCard)) {
            toggleAssertion(assertCard);
            return;
        }

        if (e.target.closest('#acl-wiz-next')) {
            if (canAdvance() && state.step < TOTAL_STEPS) {
                showStep(state.step + 1);
            }
            return;
        }

        if (e.target.closest('#acl-wiz-back') && state.step > 1) {
            showStep(state.step - 1);
        }
    });

    document.addEventListener('input', (e) => {
        if (!getModal()) {
            return;
        }
        if (e.target.id === 'acl-route-search') {
            filterRoutes();
        } else if (e.target.id === 'acl-role-search') {
            filterRoleTree(e.target.value);
        }
    });

    document.addEventListener('keydown', (e) => {
        const modal = getModal();
        if (!modal || (e.key !== 'Enter' && e.key !== ' ')) {
            return;
        }
        const t = e.target;
        if (t.matches('[data-acl-step-grant]') && modal.contains(t)) {
            e.preventDefault();
            selectGrant(t);
        }
        if (t.matches('.acl-role-item') && modal.contains(t)) {
            e.preventDefault();
            selectRole(t);
        }
        if (t.matches('.acl-assertion-card') && modal.contains(t)) {
            e.preventDefault();
            toggleAssertion(t);
        }
    });

    // Hide the rules offcanvas before an HTMX swap so Bootstrap can remove its backdrop; the node lives
    // inside <main> and would otherwise be removed while still shown.
    document.addEventListener('htmx:beforeRequest', () => {
        const oc = byId('acl-rule-offcanvas');
        const instance = oc ? bootstrap.Offcanvas.getInstance(oc) : null;
        if (instance) {
            instance.hide();
        }
    });

    document.addEventListener('htmx:afterSettle', () => {
        const oc = byId('acl-rule-offcanvas');
        if (oc) {
            bootstrap.Offcanvas.getOrCreateInstance(oc);
        }
    });
})();
