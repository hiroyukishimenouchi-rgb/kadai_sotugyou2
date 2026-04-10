/**
 * Oneカルテ — app.js（GitHub Pages デモ版）
 * PHPサーバー不要・ローカルストレージで動作
 */

"use strict";

// ═══════════════════════════════════════════════
// 設定
// ═══════════════════════════════════════════════
const STORAGE_KEY_NOTES = 'onekarte_notes';
const STORAGE_KEY_LANG  = 'onekarte_lang';

const MONTHS_JP = ['1月','2月','3月','4月','5月','6月','7月','8月','9月','10月','11月','12月'];
const MONTHS_EN = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
const DAYS_JP   = ['日','月','火','水','木','金','土'];
const DAYS_EN   = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];

// ── 多言語テキスト ──
const T = {
  ja: {
    appSub:       '難しい説明をわかりやすく整理します',
    noRecord:     '本日の記録はまだありません',
    todayNote:    '本日のOneカルテ',
    seeAll:       '件）',
    seeAllPre:    'すべて見る（',
    addRecord:    '今日の記録を追加する',
    updateRecord: '内容を更新する',
    placeholder:  '説明を聞いた内容をここに入力してください。\n\n例：「血液検査の結果、HbA1cが7.2%と少し高く…」',
    btnRecord:    '🎙️ 音声で入力する',
    btnRecording: '🔴 録音中… タップして停止',
    btnSave:      '💾 このまま保存する',
    btnAI:        '✨ AI で要点をまとめる',
    btnAILoading: '✨ AI が要約中…',
    saving:       '保存中…',
    summary:      '要点まとめ',
    terms:        '専門用語一覧（タップで解説）',
    rawText:      '元のテキスト',
    noMemo:       'この日のメモはありません',
    createMemo:   'メモを作成する',
    notifSaved:   '保存しました！',
    notifAISaved: '要約して保存しました！',
    notifInput:   'テキストを入力してください',
    notifError:   'エラーが発生しました。再度お試しください',
    notifNoKey:   'デモ版ではAI機能は利用できません',
    notifMic:     'マイクへのアクセスが許可されていません',
    notifStop:    '録音を停止しました。テキストを確認してからAI要約してください',
    saveFail:     '保存に失敗しました',
    aiLoading:    '✨ AIが調べています…',
    termFail:     '説明を取得できませんでした。',
    termUnavail:  'デモ版ではAI機能は利用できません',
    history:      '記録一覧',
    noHistory:    'まだ記録がありません',
    logout:       'ログアウト',
    logoutConfirm:'ログアウトしますか？',
    defaultTitle: 'Oneカルテ',
    recordTitle:  '記録（',
    demoNotice:   '※ デモ版：データはこのブラウザにのみ保存されます',
  },
  en: {
    appSub:       'Organize complex explanations simply',
    noRecord:     'No records for today',
    todayNote:    "Today's OneKarte",
    seeAll:       ' items)',
    seeAllPre:    'See all (',
    addRecord:    "Add today's record",
    updateRecord: 'Update record',
    placeholder:  'Enter what you heard here.\n\nExample: "Your blood test shows HbA1c at 7.2%, slightly elevated..."',
    btnRecord:    '🎙️ Record voice',
    btnRecording: '🔴 Recording… Tap to stop',
    btnSave:      '💾 Save as-is',
    btnAI:        '✨ Summarize with AI',
    btnAILoading: '✨ AI summarizing…',
    saving:       'Saving…',
    summary:      'Key Points',
    terms:        'Terms (tap for explanation)',
    rawText:      'Original Text',
    noMemo:       'No memo for this day',
    createMemo:   'Create memo',
    notifSaved:   'Saved!',
    notifAISaved: 'Summarized and saved!',
    notifInput:   'Please enter some text',
    notifError:   'An error occurred. Please try again',
    notifNoKey:   'AI features are unavailable in demo mode',
    notifMic:     'Microphone access denied',
    notifStop:    'Recording stopped. Review text before summarizing',
    saveFail:     'Failed to save',
    aiLoading:    '✨ AI is thinking…',
    termFail:     'Could not retrieve explanation.',
    termUnavail:  'AI features are unavailable in demo mode',
    history:      'History',
    noHistory:    'No records yet',
    logout:       'Logout',
    logoutConfirm:"Are you sure you want to logout?",
    defaultTitle: 'OneKarte',
    recordTitle:  'Record (',
    demoNotice:   '※ Demo: Data is stored in this browser only',
  }
};
const getText = () => T[state.lang];

// ═══════════════════════════════════════════════
// アプリ状態
// ═══════════════════════════════════════════════
const state = {
  today:         new Date(),
  currentDate:   new Date(),
  calendarDate:  new Date(new Date().getFullYear(), new Date().getMonth(), 1),
  notes:         {},
  view:          'home',
  isRecording:   false,
  isProcessing:  false,
  mediaRecorder: null,
  audioChunks:   [],
  username:      'デモユーザー',
  hasApiKey:     false, // デモ版はAI機能なし
  lang:          'ja',
};

const toKey = d =>
  `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;

// ═══════════════════════════════════════════════
// 認証（デモ版：ログアウトはリロードのみ）
// ═══════════════════════════════════════════════
function handleLogout() {
  if (!confirm(getText().logoutConfirm)) return;
  // デモ版：login.php へは飛ばさずリロード
  location.reload();
}
window.handleLogout = handleLogout;

// ═══════════════════════════════════════════════
// 言語切り替え
// ═══════════════════════════════════════════════
async function toggleLang() {
  state.lang = state.lang === 'ja' ? 'en' : 'ja';
  localStorage.setItem(STORAGE_KEY_LANG, state.lang);
  const sub = document.querySelector('.app-header p');
  if (sub) sub.textContent = getText().appSub;
  const btn = document.getElementById('langBtn');
  if (btn) btn.textContent = state.lang === 'ja' ? 'EN' : 'JP';
  setView(state.view);
}
window.toggleLang = toggleLang;

// ═══════════════════════════════════════════════
// ストレージヘルパー（ローカルストレージ版）
// ═══════════════════════════════════════════════
function localLoad() {
  try {
    const raw = localStorage.getItem(STORAGE_KEY_NOTES);
    return raw ? JSON.parse(raw) : {};
  } catch {
    return {};
  }
}

function localSave(key, note) {
  try {
    const notes = localLoad();
    notes[key] = note;
    localStorage.setItem(STORAGE_KEY_NOTES, JSON.stringify(notes));
  } catch (e) {
    throw new Error('保存に失敗しました');
  }
}

function localDelete(key) {
  try {
    const notes = localLoad();
    delete notes[key];
    localStorage.setItem(STORAGE_KEY_NOTES, JSON.stringify(notes));
  } catch {
    // 無視
  }
}

function localGetLang() {
  return localStorage.getItem(STORAGE_KEY_LANG) || 'ja';
}

// ═══════════════════════════════════════════════
// AI機能（デモ版：使用不可）
// ═══════════════════════════════════════════════
async function callClaudeSummarize(_text) {
  throw new Error('API_KEY_NOT_SET');
}

async function callClaudeTerm(_term) {
  throw new Error('API_KEY_NOT_SET');
}

// ═══════════════════════════════════════════════
// 通知
// ═══════════════════════════════════════════════
function showNotif(msg) {
  let el = document.getElementById('notif');
  el.textContent = msg;
  el.style.display = 'block';
  clearTimeout(el._t);
  el._t = setTimeout(() => (el.style.display = 'none'), 2500);
}

// ═══════════════════════════════════════════════
// 専門用語モーダル
// ═══════════════════════════════════════════════
async function openTermModal(term) {
  const overlay = document.getElementById('termModal');
  const title   = document.getElementById('termModalTitle');
  const body    = document.getElementById('termModalBody');

  title.textContent = `💡 ${term}`;
  overlay.style.display = 'flex';

  // デモ版はAI機能なし
  body.textContent = getText().termUnavail;
}

function closeTermModal() {
  document.getElementById('termModal').style.display = 'none';
}

// ═══════════════════════════════════════════════
// 要約テキスト → 用語ボタン付きに変換
// ═══════════════════════════════════════════════
function renderSummaryLine(line, terms) {
  const names = terms.map(term => term.term.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'));
  if (!names.length) return document.createTextNode(line);

  const pattern = new RegExp(`【(${names.join('|')})】`, 'g');
  const frag = document.createDocumentFragment();
  let last = 0, m;

  while ((m = pattern.exec(line)) !== null) {
    if (m.index > last) frag.appendChild(document.createTextNode(line.slice(last, m.index)));
    const btn = document.createElement('button');
    btn.className = 'term-btn';
    btn.textContent = m[1];
    btn.addEventListener('click', () => openTermModal(m[1]));
    frag.appendChild(btn);
    last = m.index + m[0].length;
  }
  if (last < line.length) frag.appendChild(document.createTextNode(line.slice(last)));
  return frag;
}

// ═══════════════════════════════════════════════
// テキストをそのまま保存
// ═══════════════════════════════════════════════
async function saveRaw() {
  const inputEl = document.getElementById('inputText');
  const text    = inputEl.value.trim();
  if (!text) { showNotif(getText().notifInput); return; }

  const btn = document.getElementById('btnSave');
  btn.disabled = true;
  btn.textContent = getText().saving;

  const key  = toKey(state.currentDate);
  const note = {
    date:      key,
    title:     getText().recordTitle + new Date().toLocaleDateString(state.lang === 'ja' ? 'ja-JP' : 'en-US') + ')',
    rawText:   text,
    summary:   [],
    terms:     [],
    createdAt: new Date().toISOString(),
  };

  try {
    state.notes[key] = note;
    localSave(key, note);
    inputEl.value = '';
    showNotif(getText().notifSaved);
    setView('detail');
  } catch {
    showNotif(getText().saveFail);
  }

  btn.disabled = false;
  btn.textContent = getText().btnSave;
}

// ═══════════════════════════════════════════════
// AI 要約（デモ版：使用不可メッセージを表示）
// ═══════════════════════════════════════════════
async function summarize() {
  showNotif(getText().notifNoKey);
}

// ═══════════════════════════════════════════════
// 音声認識（リアルタイム文字起こし）
// ═══════════════════════════════════════════════
let recognition = null;

function initSpeechRecognition() {
  const SR = window.SpeechRecognition || window.webkitSpeechRecognition;
  if (!SR) return null;

  const r = new SR();
  r.lang           = 'ja-JP';
  r.continuous     = true;
  r.interimResults = true;

  let finalText = '';

  r.onresult = (e) => {
    let interim = '';
    for (let i = e.resultIndex; i < e.results.length; i++) {
      if (e.results[i].isFinal) {
        finalText += e.results[i][0].transcript;
      } else {
        interim += e.results[i][0].transcript;
      }
    }
    const inputEl = document.getElementById('inputText');
    if (inputEl) inputEl.value = finalText + interim;
  };

  r.onerror = (e) => {
    if (e.error === 'not-allowed') {
      showNotif(getText().notifMic);
    } else if (e.error !== 'no-speech') {
      showNotif('音声認識エラー: ' + e.error);
    }
    stopRecording();
  };

  r.onend = () => {
    if (state.isRecording) r.start();
  };

  return r;
}

function stopRecording() {
  const btn     = document.getElementById('btnRecord');
  const inputEl = document.getElementById('inputText');
  if (recognition) { recognition.stop(); recognition = null; }
  state.isRecording = false;
  if (btn)     { btn.textContent = getText().btnRecord; btn.classList.remove('recording'); }
  if (inputEl) { inputEl.classList.remove('recording'); }
}

async function toggleRecording() {
  const btn = document.getElementById('btnRecord');

  if (!state.isRecording) {
    const SR = window.SpeechRecognition || window.webkitSpeechRecognition;
    if (!SR) {
      showNotif('このブラウザは音声認識に対応していません（Chrome推奨）');
      return;
    }
    recognition = initSpeechRecognition();
    if (!recognition) return;
    try {
      recognition.start();
      state.isRecording = true;
      btn.textContent = getText().btnRecording;
      btn.classList.add('recording');
      const inputEl = document.getElementById('inputText');
      if (inputEl) inputEl.classList.add('recording');
    } catch {
      showNotif(getText().notifMic);
    }
  } else {
    stopRecording();
    showNotif(getText().notifStop);
  }
}

// ═══════════════════════════════════════════════
// ビュー切り替え
// ═══════════════════════════════════════════════
function setView(v) {
  state.view = v;
  document.querySelectorAll('.nav-btn').forEach(b =>
    b.classList.toggle('active', b.dataset.view === v)
  );
  const main = document.getElementById('mainContent');
  main.innerHTML = '';

  if (v === 'home')     renderHome(main);
  if (v === 'detail')   renderDetail(main);
  if (v === 'calendar') renderCalendar(main);
}

// ═══════════════════════════════════════════════
// ホーム
// ═══════════════════════════════════════════════
function renderHome(container) {
  const d    = state.currentDate;
  const key  = toKey(d);
  const note = state.notes[key];

  // デモ通知バナー
  const banner = document.createElement('div');
  banner.style.cssText = 'background:#fef3c7;color:#92400e;font-size:.78rem;text-align:center;padding:6px 12px;border-radius:8px;margin:8px 16px 0;';
  banner.textContent = getText().demoNotice;
  container.appendChild(banner);

  const dateCard = document.createElement('div');
  dateCard.className = 'card date-strip';
  dateCard.innerHTML = `
    <div class="date-badge">
      <span class="day">${d.getDate()}</span>
      <span class="month">${MONTHS_JP[d.getMonth()]}</span>
    </div>
    <div class="date-info">
      <div class="title">${d.getFullYear()}年${MONTHS_JP[d.getMonth()]}${d.getDate()}日 ${DAYS_JP[d.getDay()]}曜日</div>
      ${note
        ? `<span class="date-chip">📋 ${note.title}</span>`
        : `<div class="sub">${getText().noRecord}</div>`
      }
    </div>`;
  container.appendChild(dateCard);

  if (note) {
    const prev = document.createElement('div');
    prev.className = 'card';
    prev.innerHTML = `<div class="section-label">${getText().todayNote}</div>`;
    const ul = document.createElement('ul');
    ul.className = 'summary-list';
    note.summary.slice(0, 3).forEach((line, i) => {
      const li = document.createElement('li');
      li.className = 'summary-item';
      li.innerHTML = `<div class="bullet">${i+1}</div>`;
      const span = document.createElement('span');
      span.appendChild(renderSummaryLine(line, note.terms || []));
      li.appendChild(span);
      ul.appendChild(li);
    });
    prev.appendChild(ul);
    if (note.summary.length > 3) {
      const moreBtn = document.createElement('button');
      moreBtn.className = 'btn-soft';
      moreBtn.style.cssText = 'display:block;margin:12px auto 0';
      moreBtn.textContent = `${getText().seeAllPre}${note.summary.length}${getText().seeAll}`;
      moreBtn.addEventListener('click', () => setView('detail'));
      prev.appendChild(moreBtn);
    }
    container.appendChild(prev);
  }

  const inputCard = document.createElement('div');
  inputCard.className = 'card';
  inputCard.innerHTML = `
    <div class="section-label">${note ? getText().updateRecord : getText().addRecord}</div>
    <textarea id="inputText" class="input-area"
      placeholder="${getText().placeholder}"></textarea>
    <button id="btnRecord" class="btn btn-record">🎙️ 音声で入力する</button>
    <button id="btnSave"   class="btn btn-save">💾 このまま保存する</button>
    <button id="btnAI" class="btn btn-ai" style="opacity:.5;cursor:not-allowed;" title="デモ版では利用不可">✨ AI で要点をまとめる（デモ版不可）</button>`;
  container.appendChild(inputCard);

  document.getElementById('btnRecord').addEventListener('click', toggleRecording);
  document.getElementById('btnSave').addEventListener('click', saveRaw);
  document.getElementById('btnAI').addEventListener('click', () => {
    showNotif(getText().notifNoKey);
  });
}

// ═══════════════════════════════════════════════
// 詳細
// ═══════════════════════════════════════════════
function renderDetail(container) {
  const d    = state.currentDate;
  const note = state.notes[toKey(d)];

  if (!note) {
    const empty = document.createElement('div');
    empty.className = 'card empty-state';
    empty.innerHTML = `<div class="icon">📋</div><p>${getText().noMemo}</p>`;
    const btn = document.createElement('button');
    btn.className = 'btn-soft';
    btn.style.cssText = 'margin-top:16px';
    btn.textContent = getText().createMemo;
    btn.addEventListener('click', () => setView('home'));
    empty.appendChild(btn);
    container.appendChild(empty);
    return;
  }

  const hdr = document.createElement('div');
  hdr.className = 'card date-strip';
  hdr.style.marginTop = '16px';
  hdr.innerHTML = `
    <div class="date-badge">
      <span class="day">${d.getDate()}</span>
      <span class="month">${MONTHS_JP[d.getMonth()]}</span>
    </div>
    <div class="date-info">
      <div class="title">${note.title}</div>
      <div class="sub">${d.getFullYear()}年${MONTHS_JP[d.getMonth()]}${d.getDate()}日</div>
    </div>`;
  container.appendChild(hdr);

  const sum = document.createElement('div');
  sum.className = 'card';
  sum.innerHTML = `<div class="section-label">${getText().summary}</div>`;
  const ul = document.createElement('ul');
  ul.className = 'summary-list';
  note.summary.forEach((line, i) => {
    const li = document.createElement('li');
    li.className = 'summary-item';
    li.innerHTML = `<div class="bullet">${i+1}</div>`;
    const span = document.createElement('span');
    span.appendChild(renderSummaryLine(line, note.terms || []));
    li.appendChild(span);
    ul.appendChild(li);
  });
  sum.appendChild(ul);
  container.appendChild(sum);

  if (note.terms && note.terms.length) {
    const tc = document.createElement('div');
    tc.className = 'card';
    tc.innerHTML = `<div class="section-label">${getText().terms}</div>`;
    const chips = document.createElement('div');
    chips.className = 'term-chips';
    note.terms.forEach(termItem => {
      const btn = document.createElement('button');
      btn.className = 'term-chip';
      btn.innerHTML = termItem.term + (termItem.simple ? `<small>${termItem.simple}</small>` : '');
      btn.addEventListener('click', () => openTermModal(termItem.term));
      chips.appendChild(btn);
    });
    tc.appendChild(chips);
    container.appendChild(tc);
  }

  const raw = document.createElement('div');
  raw.className = 'card';
  raw.innerHTML = `<div class="section-label">${getText().rawText}</div>
    <div class="raw-box">${escHtml(note.rawText)}</div>`;
  container.appendChild(raw);
}

function escHtml(str) {
  return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

// ═══════════════════════════════════════════════
// カレンダー
// ═══════════════════════════════════════════════
function renderCalendar(container) {
  const card = document.createElement('div');
  card.className = 'card';
  card.style.margin = '16px';

  const yr = state.calendarDate.getFullYear();
  const mo = state.calendarDate.getMonth();

  const hdr = document.createElement('div');
  hdr.className = 'cal-header';
  hdr.innerHTML = `
    <button class="cal-nav" id="calPrev">‹</button>
    <div class="cal-month">${yr}年 ${MONTHS_JP[mo]}</div>
    <button class="cal-nav" id="calNext">›</button>`;
  card.appendChild(hdr);

  const grid = document.createElement('div');
  grid.className = 'cal-grid';
  DAYS_JP.forEach((d, i) => {
    const cell = document.createElement('div');
    cell.className = 'cal-dow' + (i===0?' sun':i===6?' sat':'');
    cell.textContent = d;
    grid.appendChild(cell);
  });

  const firstDay    = new Date(yr, mo, 1).getDay();
  const daysInMonth = new Date(yr, mo+1, 0).getDate();
  const todayKey    = toKey(state.today);
  const curKey      = toKey(state.currentDate);

  for (let i = 0; i < firstDay; i++) grid.appendChild(document.createElement('div'));

  for (let day = 1; day <= daysInMonth; day++) {
    const dk   = toKey(new Date(yr, mo, day));
    const cell = document.createElement('div');
    cell.className = 'cal-cell';
    if      (dk === curKey)   cell.classList.add('selected');
    else if (dk === todayKey) cell.classList.add('today');
    cell.textContent = day;

    if (state.notes[dk]) {
      const dot = document.createElement('span');
      dot.className = 'cal-dot';
      cell.appendChild(dot);
    }
    cell.addEventListener('click', () => {
      state.currentDate = new Date(yr, mo, day);
      setView('detail');
    });
    grid.appendChild(cell);
  }
  card.appendChild(grid);

  const hist = document.createElement('div');
  hist.className = 'history-list';
  hist.innerHTML = `<div class="section-label" style="margin-top:18px">${getText().history}</div>`;

  const entries = Object.entries(state.notes).sort((a,b) => b[0].localeCompare(a[0]));
  if (!entries.length) {
    hist.innerHTML += `<p style="color:#9ca3af;text-align:center;padding:16px;font-size:.88rem">${getText().noHistory}</p>`;
  } else {
    entries.forEach(([dk, note]) => {
      const dt  = new Date(dk);
      const row = document.createElement('div');
      row.className = 'history-item';
      row.innerHTML = `
        <div class="history-badge">
          <span class="d">${dt.getDate()}</span>
          <span class="m">${MONTHS_JP[dt.getMonth()]}</span>
        </div>
        <div>
          <div class="history-title">${escHtml(note.title)}</div>
          <div class="history-sub">${note.summary.length}項目</div>
        </div>
        <div class="history-arrow">›</div>`;
      row.addEventListener('click', () => { state.currentDate = dt; setView('detail'); });
      hist.appendChild(row);
    });
  }
  card.appendChild(hist);
  container.appendChild(card);

  document.getElementById('calPrev').addEventListener('click', () => {
    state.calendarDate = new Date(yr, mo-1, 1);
    setView('calendar');
  });
  document.getElementById('calNext').addEventListener('click', () => {
    state.calendarDate = new Date(yr, mo+1, 1);
    setView('calendar');
  });
}

// ═══════════════════════════════════════════════
// 初期化
// ═══════════════════════════════════════════════
document.addEventListener('DOMContentLoaded', async () => {

  // 1. 言語設定取得（ローカルストレージ）
  state.lang = localGetLang();

  // 2. AIキーなし（デモ版固定）
  state.hasApiKey = false;

  // 3. ローカルストレージからメモ読み込み
  state.notes = localLoad();

  // 4. ナビゲーション
  document.querySelectorAll('.nav-btn').forEach(btn =>
    btn.addEventListener('click', () => setView(btn.dataset.view))
  );

  // 5. モーダル
  document.getElementById('termModal').addEventListener('click', e => {
    if (e.target === e.currentTarget) closeTermModal();
  });
  document.getElementById('termModalClose').addEventListener('click', closeTermModal);

  // 6. 初期ビュー
  setView('home');
});