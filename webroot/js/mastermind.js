/* Mastermind — logique client.
   Communique avec /games/mastermind/move et /games/mastermind/new.
   L'état autoritatif est côté serveur ; le client n'affiche que ce qui lui revient. */

(() => {
    const root = document.getElementById('mastermind');
    if (!root) return;

    let state     = JSON.parse(root.dataset.state);
    let selected  = Array(state.codeLen).fill(null);
    let activeIdx = 0;

    const board       = document.getElementById('mm-board');
    const slotsEl     = document.getElementById('mm-slots');
    const colorsEl    = document.getElementById('mm-colors');
    const submitBtn   = document.getElementById('btn-submit');
    const newBtn      = document.getElementById('btn-new');
    const modal       = document.getElementById('mm-modal');
    const modalTitle  = document.getElementById('mm-modal-title');
    const modalMsg    = document.getElementById('mm-modal-msg');
    const modalSecret = document.getElementById('mm-modal-secret');
    const modalClose  = document.getElementById('mm-modal-close');

    /* -------- Rendu -------- */

    const renderBoard = () => {
        board.innerHTML = '';
        const rows = state.max;
        for (let i = 0; i < rows; i++) {
            const entry = state.guesses[i];
            const row = document.createElement('div');
            row.className = 'mm-row' + (entry ? '' : ' mm-row--empty');

            const num = document.createElement('div');
            num.className = 'mm-row__num';
            num.textContent = String(i + 1).padStart(2, '0');
            row.appendChild(num);

            const pegs = document.createElement('div');
            pegs.className = 'mm-row__pegs';
            for (let j = 0; j < state.codeLen; j++) {
                const peg = document.createElement('div');
                const color = entry ? entry.guess[j] : null;
                peg.className = color ? `peg peg--${color}` : 'peg peg--empty';
                pegs.appendChild(peg);
            }
            row.appendChild(pegs);

            const pips = document.createElement('div');
            pips.className = 'mm-row__pips';
            const total = state.codeLen;
            const black = entry?.black ?? 0;
            const white = entry?.white ?? 0;
            for (let k = 0; k < total; k++) {
                const pip = document.createElement('span');
                if (k < black)             pip.className = 'pip pip--black';
                else if (k < black + white) pip.className = 'pip pip--white';
                else                        pip.className = 'pip pip--empty';
                pips.appendChild(pip);
            }
            row.appendChild(pips);
            board.appendChild(row);
        }
    };

    const renderSlots = () => {
        slotsEl.innerHTML = '';
        for (let i = 0; i < state.codeLen; i++) {
            const slot = document.createElement('div');
            const c = selected[i];
            slot.className = c ? `peg peg--${c}` : 'peg peg--empty';
            if (i === activeIdx) slot.classList.add('active');
            slot.addEventListener('click', () => { activeIdx = i; renderSlots(); });
            slotsEl.appendChild(slot);
        }
        submitBtn.disabled = selected.some(c => c === null) || state.status !== 'playing';
    };

    const render = () => { renderBoard(); renderSlots(); };

    /* -------- Interactions -------- */

    colorsEl.querySelectorAll('.peg').forEach(btn => {
        btn.addEventListener('click', () => {
            if (state.status !== 'playing') return;
            selected[activeIdx] = btn.dataset.color;
            // avance au prochain slot vide
            const next = selected.findIndex((v, i) => v === null && i >= activeIdx);
            activeIdx = next === -1
                ? (selected.findIndex(v => v === null) === -1 ? activeIdx : selected.findIndex(v => v === null))
                : next;
            renderSlots();
        });
    });

    submitBtn.addEventListener('click', async () => {
        if (selected.some(c => c === null)) return;
        submitBtn.disabled = true;
        try {
            const res = await fetch(`/games/mastermind/move`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ guess: selected }),
            });
            const data = await res.json();
            if (!data.ok) { alert(data.error || 'Erreur'); return; }
            state = data.state;
            selected = Array(state.codeLen).fill(null);
            activeIdx = 0;
            render();
            if (state.status !== 'playing') showEnd();
        } finally {
            submitBtn.disabled = false;
        }
    });

    newBtn.addEventListener('click', () => startNew());

    modalClose.addEventListener('click', () => { modal.close(); startNew(); });

    async function startNew() {
        const res = await fetch(`/games/mastermind/new`, { method: 'POST' });
        const data = await res.json();
        if (data.ok) {
            state = data.state;
            selected = Array(state.codeLen).fill(null);
            activeIdx = 0;
            render();
        }
    }

    function showEnd() {
        const won = state.status === 'won';
        modalTitle.textContent = won ? 'Bien joué.' : 'Raté.';
        modalMsg.textContent   = won
            ? `Résolu en ${state.round} essai${state.round > 1 ? 's' : ''}.`
            : 'Le code était :';

        modalSecret.innerHTML = '';
        if (state.secret) {
            state.secret.forEach(c => {
                const p = document.createElement('div');
                p.className = `peg peg--${c} peg--sm`;
                modalSecret.appendChild(p);
            });
        }
        modal.showModal();
    }

    render();
})();
