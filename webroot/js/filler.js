(() => {
    const root = document.getElementById('filler');
    if (!root) return;

    const mode    = root.dataset.mode;
    const code    = root.dataset.code || null;
    const meAttr  = root.dataset.me;
    const me      = meAttr === '' ? null : parseInt(meAttr, 10);

    let state   = JSON.parse(root.dataset.state);
    let players = root.dataset.players ? JSON.parse(root.dataset.players) : null;
    let status  = root.dataset.status || 'playing';
    let version = parseInt(root.dataset.version || '0', 10);

    const boardEl   = document.getElementById('fl-board');
    const paletteEl = document.getElementById('fl-palette');
    const turnEl    = document.getElementById('fl-turn');
    const score0El  = document.getElementById('fl-score-0');
    const score1El  = document.getElementById('fl-score-1');
    const modal     = document.getElementById('fl-modal');
    const modalT    = document.getElementById('fl-modal-title');
    const modalM    = document.getElementById('fl-modal-msg');
    const modalC    = document.getElementById('fl-modal-close');
    const newBtn    = document.getElementById('btn-new');

    function renderBoard() {
        boardEl.innerHTML = '';
        boardEl.style.setProperty('--cols', state.width);
        for (let y = 0; y < state.height; y++) {
            for (let x = 0; x < state.width; x++) {
                const cell = document.createElement('div');
                cell.className = `fl-cell fl-c-${state.grid[y][x]}`;
                const owner = state.owners[y][x];
                if (owner === 0) cell.classList.add('fl-own-0');
                else if (owner === 1) cell.classList.add('fl-own-1');
                boardEl.appendChild(cell);
            }
        }
    }

    function renderPlayers() {
        score0El.textContent = state.scores[0];
        score1El.textContent = state.scores[1];
        document.querySelector('.fl-player--0')?.classList.toggle('is-active', state.turn === 0 && state.status === 'playing');
        document.querySelector('.fl-player--1')?.classList.toggle('is-active', state.turn === 1 && state.status === 'playing');
    }

    function renderPalette() {
        const locked = state.last || [];
        const canPlay = canICurrentlyPlay();
        paletteEl.querySelectorAll('.fl-swatch').forEach(btn => {
            const c = btn.dataset.color;
            const isLocked = locked.includes(c);
            btn.classList.toggle('is-locked', isLocked);
            btn.disabled = isLocked || !canPlay || state.status !== 'playing';
        });
    }

    function canICurrentlyPlay() {
        if (state.status !== 'playing') return false;
        if (mode === 'local') return true;
        if (status !== 'playing') return false;
        if (me === null) return false;
        return state.turn === me;
    }

    function renderTurn() {
        if (state.status === 'finished') {
            const w = state.winner;
            turnEl.textContent = w === -1 ? 'égalité' : `${nameOf(w)} gagne`;
            return;
        }
        if (mode === 'local') {
            turnEl.textContent = `au tour de ${nameOf(state.turn)}`;
        } else {
            if (status === 'waiting') {
                turnEl.textContent = `en attente d'un second joueur`;
            } else if (canICurrentlyPlay()) {
                turnEl.textContent = `à toi de jouer`;
            } else {
                turnEl.textContent = `au tour de ${nameOf(state.turn)}`;
            }
        }
    }

    function nameOf(idx) {
        if (mode === 'online' && players && players[idx]) return players[idx].name;
        return idx === 0 ? 'J1' : 'J2';
    }

    function render() {
        renderBoard();
        renderPlayers();
        renderPalette();
        renderTurn();
    }

    function showEnd() {
        const w = state.winner;
        let title, msg;
        if (w === -1) {
            title = 'Égalité.';
            msg = `${state.scores[0]} · ${state.scores[1]}`;
        } else {
            title = `${nameOf(w)} gagne.`;
            msg = `${state.scores[0]} · ${state.scores[1]}`;
        }
        modalT.textContent = title;
        modalM.textContent = msg;
        modal.showModal();
    }

    paletteEl.addEventListener('click', async (e) => {
        const btn = e.target.closest('.fl-swatch');
        if (!btn || btn.disabled) return;
        const color = btn.dataset.color;

        if (mode === 'local') {
            await sendLocal(color, state.turn);
        } else {
            await sendOnline(color);
        }
    });

    async function sendLocal(color, playerIndex) {
        const res = await fetch('/games/filler/move', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ color, playerIndex }),
        });
        const data = await res.json();
        if (!data.ok) { alert(data.error || 'Erreur'); return; }
        state = data.state;
        render();
        if (state.status === 'finished') showEnd();
    }

    async function sendOnline(color) {
        const res = await fetch(`/rooms/${code}/move`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ color }),
        });
        const data = await res.json();
        if (!data.ok) { alert(data.error || 'Erreur'); return; }
        state = data.state;
        status = data.status;
        version = data.version;
        render();
        if (state.status === 'finished') showEnd();
    }

    if (newBtn) {
        newBtn.addEventListener('click', async () => {
            const res = await fetch('/games/filler/new', { method: 'POST' });
            const data = await res.json();
            if (data.ok) {
                state = data.state;
                render();
            }
        });
    }

    if (modalC) {
        modalC.addEventListener('click', () => modal.close());
    }

    if (mode === 'online') {
        setInterval(async () => {
            try {
                const res = await fetch(`/rooms/${code}/state`);
                const data = await res.json();
                if (!data.ok) return;
                if (data.version === version) return;
                state = data.state;
                players = data.players;
                status = data.status;
                version = data.version;
                document.getElementById('fl-name-0').textContent = players[0]?.name || '—';
                document.getElementById('fl-name-1').textContent = players[1]?.name || '…';
                render();
                if (state.status === 'finished' && !modal.open) showEnd();
            } catch (e) {}
        }, 1500);
    }

    render();
})();
