(() => {
    const root = document.getElementById('labyrinth');
    if (!root) return;

    const code = root.dataset.code;
    const meAttr = root.dataset.me;
    const me = meAttr === '' ? null : parseInt(meAttr, 10);
    const EQUIPS = JSON.parse(root.dataset.equips);

    let state = JSON.parse(root.dataset.state);
    let players = JSON.parse(root.dataset.players);
    let status = root.dataset.status;
    let version = parseInt(root.dataset.version, 10);

    const boardEl = document.getElementById('lab-board');
    const modal = document.getElementById('lab-modal');
    const modalT = document.getElementById('lab-modal-title');
    const modalM = document.getElementById('lab-modal-msg');
    const logEl = document.getElementById('lab-log');
    const mySlotsEl = document.getElementById('lab-my-slots');

    function renderBoard() {
        boardEl.innerHTML = '';
        boardEl.style.setProperty('--cols', state.width);
        boardEl.style.setProperty('--rows', state.height);

        for (let y = 0; y < state.height; y++) {
            for (let x = 0; x < state.width; x++) {
                const cell = document.createElement('div');
                const type = state.grid[y][x];
                cell.className = 'lab-cell lab-' + type;

                if (!state.treasure.taken
                    && x === state.treasure.x
                    && y === state.treasure.y) {
                    cell.classList.add('lab-treasure');
                    cell.textContent = '$';
                }

                (state.equipments || []).forEach(eq => {
                    if (eq.x === x && eq.y === y) {
                        cell.classList.add('lab-item');
                        cell.textContent = EQUIPS[eq.type].icon;
                        cell.title = EQUIPS[eq.type].name;
                    }
                });

                (state.monsters || []).forEach(m => {
                    if (m.alive && m.x === x && m.y === y) {
                        cell.classList.add('lab-monster');
                        cell.textContent = '☠';
                        cell.title = 'Monstre (HP ' + m.hp + ')';
                    }
                });

                state.players.forEach((p, i) => {
                    if (p.alive && p.x === x && p.y === y) {
                        cell.classList.remove('lab-item', 'lab-monster');
                        cell.classList.add('lab-player', 'lab-p' + i);
                        cell.textContent = (i === 0 ? '①' : '②');
                        if (p.hasTreasure) cell.classList.add('lab-has-treasure');
                    }
                });

                boardEl.appendChild(cell);
            }
        }
    }

    function renderHUD() {
        for (let i = 0; i < 2; i++) {
            const p = state.players[i];
            document.getElementById('lab-pa-' + i).textContent = p.pa;
            document.getElementById('lab-hp-' + i).textContent = p.hp;
            document.getElementById('lab-name-' + i).textContent = players[i]?.name || (i === 0 ? '—' : '…');

            const badgeEl = document.getElementById('lab-badge-' + i);
            let badge = '';
            if (!p.alive) badge = '✖';
            else if (p.hasTreasure) badge = '$';
            badgeEl.textContent = badge;

            const playerEl = document.querySelector('.lab-hud__player--' + i);
            playerEl.classList.toggle('is-dead', !p.alive);
            playerEl.classList.toggle('is-me', me === i);

            const invEl = document.getElementById('lab-inv-' + i);
            invEl.innerHTML = '';
            (p.equipment || []).forEach(type => {
                const el = document.createElement('span');
                el.className = 'lab-inv__item';
                el.textContent = EQUIPS[type].icon;
                el.title = EQUIPS[type].name;
                invEl.appendChild(el);
            });
        }
        renderMySlots();
    }

    function renderMySlots() {
        mySlotsEl.innerHTML = '';
        if (me === null) return;
        const eq = state.players[me]?.equipment || [];
        for (let i = 0; i < 2; i++) {
            const slot = document.createElement('button');
            slot.className = 'lab-slot';
            const type = eq[i];
            if (type) {
                slot.classList.add('is-filled');
                const def = EQUIPS[type];
                slot.innerHTML = `<span class="lab-slot__icon">${def.icon}</span><span class="lab-slot__name">${def.name}</span>`;
                slot.title = stattooltip(def);
                slot.addEventListener('click', (e) => useOrDrop(i, e.shiftKey));
            } else {
                slot.classList.add('is-empty');
                slot.textContent = '—';
                slot.disabled = true;
            }
            mySlotsEl.appendChild(slot);
        }
    }

    function stattooltip(def) {
        const bits = [];
        if (def.attack) bits.push('atk +' + def.attack);
        if (def.defense) bits.push('def +' + def.defense);
        if (def.bonus_pa) bits.push('+' + def.bonus_pa + ' PA (consommable)');
        return def.name + (bits.length ? ' — ' + bits.join(', ') : '');
    }

    function renderLog() {
        logEl.innerHTML = '';
        (state.log || []).slice(-6).forEach(line => {
            const li = document.createElement('div');
            li.className = 'lab-log__line';
            li.textContent = line;
            logEl.appendChild(li);
        });
    }

    function canPlay() {
        if (state.status !== 'playing') return false;
        if (status !== 'playing') return false;
        if (me === null) return false;
        return state.players[me]?.alive;
    }

    function render() {
        renderBoard();
        renderHUD();
        renderLog();
        document.querySelectorAll('.lab-dbtn[data-dir]').forEach(b => {
            b.disabled = !canPlay() || state.players[me]?.pa < 1;
        });
    }

    function showEnd() {
        if (state.winner === me) {
            modalT.textContent = 'Victoire.';
            modalM.textContent = 'Tu es le dernier survivant.';
        } else if (state.winner === -1) {
            modalT.textContent = 'Match nul.';
            modalM.textContent = 'Tout le monde est mort.';
        } else if (state.winner !== null && state.winner !== undefined) {
            modalT.textContent = 'Défaite.';
            modalM.textContent = (players[state.winner]?.name || 'L\'adversaire') + ' survit.';
        } else {
            modalT.textContent = 'Terminé.';
            modalM.textContent = '';
        }
        modal.showModal();
    }

    async function send(body) {
        try {
            const res = await fetch(`/rooms/${code}/move`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(body),
            });
            const data = await res.json();
            if (!data.ok) { flashError(data.error); return; }
            state = data.state;
            status = data.status;
            version = data.version;
            render();
            if (state.status === 'finished') showEnd();
        } catch (e) {}
    }

    function move(dir) {
        if (!canPlay()) return;
        send({ action: 'move', dir });
    }

    function useOrDrop(slot, shift) {
        if (!canPlay()) return;
        send({ action: shift ? 'drop' : 'use', slot });
    }

    function flashError(msg) {
        const el = document.createElement('div');
        el.className = 'lab-flash';
        el.textContent = msg || 'Action impossible';
        document.body.appendChild(el);
        setTimeout(() => el.remove(), 1800);
    }

    document.querySelectorAll('.lab-dbtn[data-dir]').forEach(btn => {
        btn.addEventListener('click', () => move(btn.dataset.dir));
    });

    document.addEventListener('keydown', (e) => {
        const map = { ArrowUp:'up', ArrowDown:'down', ArrowLeft:'left', ArrowRight:'right',
                      z:'up', s:'down', q:'left', d:'right',
                      w:'up', a:'left' };
        if (map[e.key]) { e.preventDefault(); move(map[e.key]); }
        if (e.key === '1') useOrDrop(0, e.shiftKey);
        if (e.key === '2') useOrDrop(1, e.shiftKey);
    });

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
            render();
            if (state.status === 'finished' && !modal.open) showEnd();
        } catch (e) {}
    }, 1500);

    render();
    if (state.status === 'finished') showEnd();
})();
