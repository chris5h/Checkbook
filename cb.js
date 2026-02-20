/* =============================================
   Checkbook — cb.js
   ============================================= */

// ---- Drawer helpers ----

function openDrawer(id) {
  document.getElementById('drawerOverlay').classList.add('open');
  document.getElementById(id).classList.add('open');
  document.body.style.overflow = 'hidden';
}

function closeAllDrawers() {
  document.getElementById('drawerOverlay').classList.remove('open');
  document.querySelectorAll('.drawer').forEach(d => d.classList.remove('open'));
  document.body.style.overflow = '';
}

function openSearch() { openDrawer('searchDrawer'); }

// Schedule list → edit/add: stack by hiding list, showing edit
function openSchedList() { openDrawer('schedListDrawer'); }

function backToSchedList() {
  document.getElementById('schedDrawer').classList.remove('open');
  document.getElementById('schedListDrawer').classList.add('open');
  // overlay stays open
}

// ---- Type toggle ----
function setType(val) {
  document.getElementById('trans_type').value = val;
  document.getElementById('btn_withdrawal').classList.toggle('active', val === 'withdrawal');
  document.getElementById('btn_deposit').classList.toggle('active', val === 'deposit');
}

// ---- Active toggle (schedule) ----
function setActive(val) {
  document.getElementById('active').value = val;
  document.getElementById('btn_active1').classList.toggle('active', val === 1);
  document.getElementById('btn_active0').classList.toggle('active', val === 0);
}

// ---- Today's date ----
function todayISO() {
  const d = new Date();
  const mm = String(d.getMonth() + 1).padStart(2, '0');
  const dd = String(d.getDate()).padStart(2, '0');
  return `${d.getFullYear()}-${mm}-${dd}`;
}

// ---- New Transaction ----
function newTrans() {
  document.getElementById('type').value = 'new';
  document.getElementById('submitBtn').textContent = 'Add Transaction';
  document.getElementById('transTitle').textContent = 'Add Transaction';
  document.getElementById('trans_id').value = '';
  document.getElementById('amount').value = '';
  document.getElementById('date').value = todayISO();
  document.getElementById('description').value = '';
  document.getElementById('checknumber').value = '';
  setType('withdrawal');
  document.getElementById('deleteBtn').style.display = 'none';
  openDrawer('transDrawer');
}

// ---- Edit Transaction ----
function editTrans(id) {
  document.getElementById('type').value = 'edit';
  document.getElementById('submitBtn').textContent = 'Save Changes';
  document.getElementById('transTitle').textContent = 'Edit Transaction';
  document.getElementById('trans_id').value = id;

  $.get("handler.php?type=gettransaction&id=" + id, function(data) {
    const t = JSON.parse(data);
    document.getElementById('amount').value = Math.abs(t.amount);
    document.getElementById('date').value = t.trans_date;
    document.getElementById('checknumber').value = t.checknumber || '';
    document.getElementById('description').value = t.description;
    setType(t.amount < 0 ? 'withdrawal' : 'deposit');
  });

  document.getElementById('deleteBtn').style.display = 'block';
  openDrawer('transDrawer');
}

// ---- Delete Transaction ----
function confirmDelete() {
  if (!confirm('Delete this transaction?')) return;
  const id = document.getElementById('trans_id').value;
  $.get("handler.php?type=deltrans&id=" + id)
    .done(() => { closeAllDrawers(); window.location.replace(window.location.href); });
}

// ---- New Schedule ----
function newSched() {
  document.getElementById('sched_type').value = 'newsched';
  document.getElementById('schedSubmitBtn').textContent = 'Add Schedule';
  document.getElementById('schedTitle').textContent = 'Add Schedule';
  document.getElementById('sched_id').value = '';
  document.getElementById('sched_amount').value = '';
  document.getElementById('sched_date').value = '28';
  document.getElementById('description_sched').value = '';
  setActive(1);
  document.getElementById('schedDeleteBtn').style.display = 'none';
  // swap list drawer for edit drawer
  document.getElementById('schedListDrawer').classList.remove('open');
  openDrawer('schedDrawer');
}

// ---- Edit Schedule ----
function editSched(id) {
  document.getElementById('sched_type').value = 'editsched';
  document.getElementById('schedSubmitBtn').textContent = 'Save Changes';
  document.getElementById('schedTitle').textContent = 'Edit Schedule';
  document.getElementById('sched_id').value = id;

  $.get("handler.php?type=getschedule&id=" + id, function(data) {
    const s = JSON.parse(data);
    document.getElementById('sched_amount').value = Math.abs(s.amount);
    document.getElementById('sched_date').value = s.dayofmonth;
    document.getElementById('description_sched').value = s.description;
    setActive(parseInt(s.active));
  });

  document.getElementById('schedDeleteBtn').style.display = 'block';
  // swap list drawer for edit drawer
  document.getElementById('schedListDrawer').classList.remove('open');
  openDrawer('schedDrawer');
}

// ---- Delete Schedule ----
function confirmDeleteSched() {
  if (!confirm('Delete this scheduled payment?')) return;
  const id = document.getElementById('sched_id').value;
  $.get("handler.php?type=delsched&id=" + id)
    .done(() => { closeAllDrawers(); window.location.replace(window.location.href); });
}

// kept for any legacy calls
function delTrans()  { confirmDelete(); }
function delSched()  { confirmDeleteSched(); }

// ---- Search validation ----
function validateForm() {
  const from = document.getElementById('search_from').value;
  const to   = document.getElementById('search_to').value;
  if (from && to && to < from) {
    alert('From date must be before To date.');
    return false;
  }
  return true;
}

// ---- Export CSV ----
function exportCSV() {
  if (!validateForm()) return false;
  const form = document.getElementById('search_form');
  const prev = form.action;
  form.action = 'search.php';
  form.submit();
  form.action = prev;
}

// ---- Reset search fields ----
function resetForm(cname) {
  document.querySelectorAll('.' + cname).forEach(el => el.value = '');
}

// ---- Swipe-down to close ----
(function () {
  let startY = 0;
  document.addEventListener('touchstart', e => { startY = e.touches[0].clientY; }, { passive: true });
  document.addEventListener('touchend', e => {
    const dy = e.changedTouches[0].clientY - startY;
    if (dy > 80) {
      // if sched edit drawer is open, go back to list; otherwise close all
      const schedOpen = document.getElementById('schedDrawer').classList.contains('open');
      if (schedOpen) { backToSchedList(); } else { closeAllDrawers(); }
    }
  }, { passive: true });
})();
