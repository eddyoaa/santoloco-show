// Drag & Drop im Schichtplan (Admin).
// Maus: Schicht greifen und ziehen. Touch: kurz gedrückt halten, dann ziehen.
// Alt-Taste (Mac: Option) beim Loslassen = kopieren statt verschieben.
(function () {
  const table = document.querySelector('table.schedule[data-dnd]');
  if (!table) return;

  const csrf = table.dataset.csrf;
  const moveUrl = table.dataset.moveUrl;
  let drag = null; // { el, id, ghost, startX, startY, offX, offY, active, target, pointerId, touchTimer }

  function cellAt(x, y) {
    const el = document.elementFromPoint(x, y);
    const td = el && el.closest('td[data-date]');
    return td && table.contains(td) ? td : null;
  }

  function setTarget(td) {
    if (drag.target === td) return;
    if (drag.target) drag.target.classList.remove('drop-target');
    drag.target = td;
    if (td) td.classList.add('drop-target');
  }

  function begin() {
    const r = drag.el.getBoundingClientRect();
    const ghost = drag.el.cloneNode(true);
    ghost.classList.add('drag-ghost');
    ghost.style.width = r.width + 'px';
    document.body.appendChild(ghost);
    drag.ghost = ghost;
    drag.active = true;
    drag.el.classList.add('dragging');
    document.body.classList.add('is-dragging');
    if (navigator.vibrate && drag.touch) navigator.vibrate(15);
  }

  function moveGhost(x, y, copy) {
    drag.ghost.style.transform = `translate(${x - drag.offX}px, ${y - drag.offY}px)`;
    drag.ghost.classList.toggle('copy', !!copy);
    setTarget(cellAt(x, y));
  }

  function cleanup() {
    if (!drag) return;
    clearTimeout(drag.touchTimer);
    if (drag.ghost) drag.ghost.remove();
    if (drag.target) drag.target.classList.remove('drop-target');
    drag.el.classList.remove('dragging');
    document.body.classList.remove('is-dragging');
    drag = null;
  }

  async function drop(td, copy) {
    if (!td || (td === drag.el.closest('td') && !copy)) return cleanup();
    const body = new URLSearchParams({
      _csrf: csrf, do: 'move', id: drag.id, user_id: td.dataset.user, date: td.dataset.date, copy: copy ? '1' : '0',
    });
    td.classList.add('drop-busy');
    const el = drag.el;
    cleanup();
    try {
      const res = await fetch(moveUrl, { method: 'POST', body, headers: { Accept: 'application/json' } });
      const data = await res.json().catch(() => ({ ok: false, message: 'Sitzung abgelaufen. Bitte Seite neu laden.' }));
      if (data.ok) {
        location.reload();
        return;
      }
      td.classList.remove('drop-busy');
      el.classList.add('shake');
      setTimeout(() => el.classList.remove('shake'), 500);
      alert(data.message || 'Verschieben nicht möglich.');
    } catch (e) {
      td.classList.remove('drop-busy');
      alert('Keine Verbindung zum Server.');
    }
  }

  table.addEventListener('pointerdown', (e) => {
    const el = e.target.closest('.shift[data-id]');
    if (!el || e.button > 0 || e.target.closest('.tools')) return;
    const r = el.getBoundingClientRect();
    drag = {
      el, id: el.dataset.id, startX: e.clientX, startY: e.clientY,
      offX: e.clientX - r.left, offY: e.clientY - r.top,
      active: false, target: null, pointerId: e.pointerId, touch: e.pointerType !== 'mouse',
    };
    if (drag.touch) {
      // Long-Press startet das Ziehen, normales Wischen scrollt weiter
      drag.touchTimer = setTimeout(() => {
        if (!drag) return;
        begin();
        moveGhost(drag.startX, drag.startY, false);
      }, 280);
    } else {
      e.preventDefault(); // keine Textauswahl
    }
  });

  document.addEventListener('pointermove', (e) => {
    if (!drag || e.pointerId !== drag.pointerId) return;
    const dist = Math.hypot(e.clientX - drag.startX, e.clientY - drag.startY);
    if (!drag.active) {
      if (drag.touch) {
        if (dist > 10) cleanup(); // Finger bewegt sich vor dem Long-Press: das ist Scrollen
        return;
      }
      if (dist < 5) return;
      begin();
    }
    moveGhost(e.clientX, e.clientY, e.altKey);
  });

  document.addEventListener('pointerup', (e) => {
    if (!drag || e.pointerId !== drag.pointerId) return;
    if (!drag.active) return cleanup();
    drop(cellAt(e.clientX, e.clientY), e.altKey);
  });

  document.addEventListener('pointercancel', () => {
    if (drag && !drag.active) cleanup();
  });

  // Während des Ziehens auf Touch-Geräten nicht scrollen
  document.addEventListener('touchmove', (e) => {
    if (drag && drag.active) e.preventDefault();
  }, { passive: false });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') cleanup();
    if (drag && drag.ghost && e.key === 'Alt') drag.ghost.classList.add('copy');
  });
  document.addEventListener('keyup', (e) => {
    if (drag && drag.ghost && e.key === 'Alt') drag.ghost.classList.remove('copy');
  });
  // Kontextmenü bei Long-Press unterdrücken
  table.addEventListener('contextmenu', (e) => {
    if (e.target.closest('.shift[data-id]')) e.preventDefault();
  });
})();
