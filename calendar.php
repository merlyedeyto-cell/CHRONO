<?php
session_start();
require_once 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: login.php');
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
<title>CHRONOMUSE</title>
<link rel="stylesheet" href="style.css?v=<?php echo filemtime('style.css'); ?>">
<script>
    (function() {
        const savedTheme = localStorage.getItem('cm_theme');
        if (savedTheme === 'dark') {
            document.documentElement.classList.add('dark');
        }
    })();
</script>
<style>
    @keyframes welcome-bounce {
        0%, 100% { transform: translateY(0); }
        40% { transform: translateY(-6px); }
        55% { transform: translateY(0); }
        70% { transform: translateY(-3px); }
    }
</style>
</head>
<body>

<header class="top-header">
    <div class="topbar-stars" aria-hidden="true"></div>
        <div class="brand">
            <div class="brand-logo">
                <img src="logo.png" alt="CHRONOMUSE logo">
            </div>
            <div class="brand-text">
                <div class="brand-title">CHRONOMUSE</div>
                <div class="brand-subtitle">Remember your days</div>
            </div>
        </div>
    <div class="header-actions">
        <div class="auth-buttons">
            <button type="button" class="action-btn mood-tracker-btn" id="moodTrackerBtn">Mood Graph</button>
            <a href="?logout=true" class="logout-btn" id="logoutBtn">Logout</a>
        </div>
    </div>
</header>

<div id="floatingSparkLayer" aria-hidden="true"></div>

<div id="logoutModal" class="modal">
    <div class="modal-content logout-modal-content" style="max-width: 420px;">
        <div class="modal-stars" aria-hidden="true"></div>
        <span class="close-btn" id="logoutCloseBtn">&times;</span>
        <div class="modal-header">
            <h2>Confirm Logout</h2>
        </div>
        <p class="logout-modal-text" style="margin: 8px 0 20px;">Are you sure you want to logout?</p>
        <div style="display: flex; gap: 10px; justify-content: flex-end;">
            <button type="button" id="logoutCancelBtn" class="action-btn logout-btn-secondary" style="min-width: 56px; height: 32px; display: inline-flex; align-items: center; justify-content: center;">No</button>
            <a href="?logout=true" id="logoutConfirmBtn" class="action-btn logout-btn-primary" style="text-decoration: none; min-width: 70px; height: 32px; display: inline-flex; align-items: center; justify-content: center;">Yes</a>
        </div>
    </div>
</div>

<div id="moodTrackerModal" class="modal">
    <div class="modal-content mood-tracker-content">
        <div class="modal-stars" aria-hidden="true"></div>
        <span class="close-btn" id="moodTrackerCloseBtn">&times;</span>
        <div class="modal-header mood-tracker-header">
            <h2 id="moodTrackerTitle">This Week</h2>
            <div class="mood-tracker-controls">
                <button type="button" class="mood-range-btn is-active" data-range="this_week">This Week</button>
                <button type="button" class="mood-range-btn" data-range="last_week">Last Week</button>
                <button type="button" class="mood-range-btn" data-range="this_month">This Month</button>
            </div>
        </div>
        <div class="mood-tracker-sub" id="moodTrackerSub"></div>
        <div class="mood-tracker-body">
            <div class="mood-tracker-chart">
                <canvas id="moodTrackerCanvas" width="640" height="320"></canvas>
            </div>
            <div class="mood-tracker-legend" id="moodTrackerLegend"></div>
        </div>
    </div>
</div>

<button type="button" id="themeToggle" class="theme-toggle" aria-pressed="false" aria-label="Switch to dark mode">
    <span class="theme-icon theme-icon-light" aria-hidden="true">☀︎</span>
    <span class="theme-icon theme-icon-dark" aria-hidden="true">⏾</span>
</button>
<div class="welcome-user welcome-float">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</div>

<div class="container">

        <!-- PINNED CARD -->
    <div class="card" id="pinnedCard">
        <div class="pinned-header">
            <h2 class="pinned-title"><span class="pin-icon" aria-hidden="true"></span>Pinned</h2>

        </div>
        <div id="pinnedList" class="pinned-list">
            <div class="pinned-empty">No pinned memories yet.</div>
        </div>
    </div>
<!-- CALENDAR CARD -->
    <div class="card" id="calendarCard">
<div class="calendar-header">
    <button class="nav-btn" id="prevMonth">‹</button>    <button class="today-btn today-btn-small" id="todayBtn" type="button">Today</button>
    <h2 id="currentMonth">◦ January 2025</h2>
    <button class="nav-btn" id="nextMonth">›</button>
</div>
        <div class="calendar-filter">
            <label for="calendarTagFilter">Filter Tag:</label>
            <select id="calendarTagFilter">
                <option value="all">All</option>
                <option value="general">General</option>
                <option value="birthday">Birthday</option>
                <option value="anniversary">Anniversary</option>
                <option value="holiday">Holiday</option>
                <option value="travel">Travel</option>
                <option value="food">Food</option>
                <option value="family">Family</option>
                <option value="friends">Friends</option>
                <option value="work">Work</option>
                <option value="special">Special</option>
            </select>
            <label for="calendarReactionFilter">Reaction:</label>
            <select id="calendarReactionFilter">
                <option value="all">All</option>
                <option value="❤️">❤️</option>
                <option value="😮">😮</option>
                <option value="😂">😂</option>
                <option value="😡">😡</option>
            </select>
        </div>
        <div class="calendar-select-toolbar">
            <button type="button" id="calendarSelectToggle" class="calendar-select-btn">Select Dates</button>
            <button type="button" id="calendarSelectAllBtn" class="calendar-select-btn is-ghost" style="display:none;">Select All</button>
            <button type="button" id="calendarCancelSelectBtn" class="calendar-select-btn is-ghost" style="display:none;">Cancel</button>
        </div>
        <div class="calendar" id="calendar"></div>
        <div class="calendar-list" id="calendarList" style="display:none;"></div>
        <button type="button" id="calendarFilterBack" class="calendar-filter-back" style="display:none;">Back</button>
    </div>

    <!-- MEMORY MODAL -->
    <div id="memoryModal" class="modal">
        <div class="modal-content">
            <div class="modal-stars" aria-hidden="true"></div>
            <span class="close-btn" onclick="closeModal()">&times;</span>
            <div class="modal-header">
                <h2 id="modalTitle">Memories</h2>
                <div style="display: flex; gap: 8px; align-items: center;">
                    <button id="deleteMemoriesBtn" class="delete-btn" onclick="showDeleteConfirmation()" style="display: none;">Delete All Memories</button>
                </div>
            </div>
            <div id="modalMemories"></div>
        </div>
    </div>

    <!-- CONFIRM MODAL -->
    <div id="confirmModal" class="modal confirm-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Confirm</h2>
            </div>
            <p id="confirmMessage"></p>
            <div class="modal-actions">
                <button type="button" id="confirmYes" class="action-btn">OK</button>
                <button type="button" id="confirmNo" class="action-btn is-ghost">Cancel</button>
            </div>
        </div>
    </div>

    <!-- ADD MEMORY CARD -->
    <div class="card" id="memoryCard">
        <h2>+ Add Memory</h2>
        
        <div class="selected-date-display">
            <p><b>Selected Date:</b></p>
            <span id="selectedDate">None</span>
        </div>

        <form action="save_memory.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="memory_date" id="memoryDateInput">
            
            <div class="file-input-wrapper">
                <input type="file" name="media" id="mediaInput" class="file-input" accept="image/*,video/*" required>
                <label for="mediaInput" class="file-input-label">
                    <div class="file-input-content">
                        <span class="file-input-icon">+</span>
                        <span id="fileLabel">Choose Photo or Video</span>
                    </div>
                    <img id="imagePreview" class="image-preview" style="display: none;">
                </label>
            </div>

            <div class="file-input-wrapper">
                <input type="file" name="audio" id="audioInput" class="file-input" accept="audio/*">
                <label for="audioInput" class="file-input-label">
                    <div class="file-input-content">
                        <span class="file-input-icon">+</span>
                        <span id="audioLabel">Choose Music</span>
                    </div>
                </label>
            </div>
            
            <div class="tag-mood-row">
                <div class="tag-selector">
                    <label for="tag">Memory Tag:</label>
                    <select name="tag" id="tag" class="tag-select">
                        <option value="general">General</option>
                        <option value="birthday">Birthday</option>
                        <option value="anniversary">Anniversary</option>
                        <option value="holiday">Holiday</option>
                        <option value="travel">Travel</option>
                        <option value="food">Food</option>
                        <option value="family">Family</option>
                        <option value="friends">Friends</option>
                        <option value="work">Work</option>
                        <option value="special">Special</option>
                        <option value="custom">Custom Tag</option>
                    </select>
                    <div id="customTagContainer" style="display: none; margin-top: 10px;">
                        <input type="text" id="customTagInput" placeholder="Enter custom tag" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                    </div>
                </div>
                <div class="mood-selector">
                    <label for="moodSelect">Mood:</label>
                    <select name="mood_select" id="moodSelect" class="tag-select">
                        <option value="happy">Happy</option>
                        <option value="calm">Calm</option>
                        <option value="grateful">Grateful</option>
                        <option value="excited">Excited</option>
                        <option value="sad">Sad</option>
                        <option value="angry">Angry</option>
                        <option value="anxious">Anxious</option>
                        <option value="tired">Tired</option>
                        <option value="stressed">Stressed</option>
                        <option value="proud">Proud</option>
                        <option value="loved">Loved</option>
                        <option value="peaceful">Peaceful</option>
                        <option value="hopeful">Hopeful</option>
                        <option value="bored">Bored</option>
                        <option value="lonely">Lonely</option>
                        <option value="motivated">Motivated</option>
                        <option value="overwhelmed">Overwhelmed</option>
                        <option value="inspired">Inspired</option>
                        <option value="confident">Confident</option>
                        <option value="sleepy">Sleepy</option>
                        <option value="other">Other</option>
                    </select>
                    <div class="mood-custom" id="moodCustomWrap" style="display: none; margin-top: 10px;">
                        <input type="text" id="moodCustom" name="mood_custom" placeholder="Enter custom mood" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                    </div>
                    <input type="hidden" name="mood" id="moodFinal" value="happy">
                </div>
            </div>
            
            <div style="position: relative;">
                <textarea name="message" id="messageTextarea" placeholder="Write your precious memories here." required></textarea>
                <button type="button" id="emojiBtn" class="emoji-btn">😊</button>
                <div id="emojiPicker" class="emoji-picker">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <span style="font-size: 14px; color: #ff1493; font-weight: 600;">Select Emojis</span>
                        <button type="button" onclick="emojiPicker.classList.remove('show')" style="background: none; border: none; color: #ff1493; cursor: pointer; font-size: 18px;">×</button>
                    </div>
                    <div class="emoji-row">  
                        <span class="emoji">😀</span><span class="emoji">😃</span><span class="emoji">😄</span><span class="emoji">😁</span><span class="emoji">😆</span><span class="emoji">😅</span><span class="emoji">😂</span><span class="emoji">🤣</span><span class="emoji">😊</span><span class="emoji">😇</span>
                    </div>
                    <div class="emoji-row">
                        <span class="emoji" >🙂</span><span class="emoji">🙃</span><span class="emoji">😉</span><span class="emoji">😌</span><span class="emoji">😍</span><span class="emoji">🥰</span><span class="emoji">😘</span><span class="emoji">😗</span><span class="emoji">😙</span><span class="emoji">😚</span>
                    </div>
                    <div class="emoji-row">
                        <span class="emoji">😋</span><span class="emoji">😛</span><span class="emoji">😜</span><span class="emoji">🤪</span><span class="emoji">😝</span><span class="emoji">🤑</span><span class="emoji">🤗</span><span class="emoji">🤭</span><span class="emoji">🤫</span><span class="emoji">🤔</span>
                    </div>
                    <div class="emoji-row">
                        <span class="emoji">👍</span><span class="emoji">👎</span><span class="emoji">👌</span><span class="emoji">✌️</span><span class="emoji">🤞</span><span class="emoji">🤟</span><span class="emoji">🤘</span><span class="emoji">🤙</span><span class="emoji">👈</span><span class="emoji">👉</span>
                    </div>
                    <div class="emoji-row">
                        <span class="emoji">❤️</span><span class="emoji">🧡</span><span class="emoji">💛</span><span class="emoji">💚</span><span class="emoji">💙</span><span class="emoji">💜</span><span class="emoji">🖤</span><span class="emoji">💔</span><span class="emoji">❣️</span><span class="emoji">💕</span>
                    </div>
                    <div class="emoji-row">
                        <span class="emoji">🎉</span><span class="emoji">🎊</span><span class="emoji">🎈</span><span class="emoji">🎁</span><span class="emoji">🎂</span><span class="emoji">🍰</span><span class="emoji">🧁</span><span class="emoji">🍪</span><span class="emoji">🍫</span><span class="emoji">🍬</span>
                    </div>
                    <div class="emoji-row">
                        <span class="emoji">🌟</span><span class="emoji">⭐</span><span class="emoji">✨</span><span class="emoji">💫</span><span class="emoji">🌈</span><span class="emoji">🌸</span><span class="emoji">🌺</span><span class="emoji">🌻</span><span class="emoji">🌹</span><span class="emoji">🌷</span>
                    </div>
                    <div class="emoji-row">
                        <span class="emoji">📸</span><span class="emoji">📷</span><span class="emoji">🎥</span><span class="emoji">📹</span><span class="emoji">💻</span><span class="emoji">📱</span><span class="emoji">⏰</span><span class="emoji">📅</span><span class="emoji">📆</span><span class="emoji">📇</span>
                    </div>
                </div>
            </div>
            <button type="submit">Save Memory</button>
        </form>
    </div>

</div>

<div id="cursorSparkleLayer" aria-hidden="true"></div>

<script>
const calendarEl = document.getElementById("calendar");
const calendarListEl = document.getElementById("calendarList");
const selectedDateEl = document.getElementById("selectedDate");
const memoryDateInput = document.getElementById("memoryDateInput");
const currentMonthEl = document.getElementById("currentMonth");
const calendarTagFilter = document.getElementById("calendarTagFilter");
const calendarReactionFilter = document.getElementById("calendarReactionFilter");
const calendarFilterBack = document.getElementById("calendarFilterBack");
const calendarSelectToggle = document.getElementById("calendarSelectToggle");
const calendarSelectAllBtn = document.getElementById("calendarSelectAllBtn");
const calendarCancelSelectBtn = document.getElementById("calendarCancelSelectBtn");
const todayBtn = document.getElementById("todayBtn");
const themeToggle = document.getElementById("themeToggle");
const moodTrackerBtn = document.getElementById("moodTrackerBtn");
const moodTrackerModal = document.getElementById("moodTrackerModal");
const moodTrackerCloseBtn = document.getElementById("moodTrackerCloseBtn");
const moodTrackerCanvas = document.getElementById("moodTrackerCanvas");
const moodTrackerLegend = document.getElementById("moodTrackerLegend");
const moodRangeButtons = document.querySelectorAll(".mood-range-btn");
const moodTrackerSub = document.getElementById("moodTrackerSub");
const moodTrackerTitle = document.getElementById("moodTrackerTitle");
const moodSummaryCloseBtn = document.getElementById("moodSummaryCloseBtn");
const moodSelect = document.getElementById("moodSelect");
const moodCustomWrap = document.getElementById("moodCustomWrap");
const moodCustom = document.getElementById("moodCustom");
const moodFinal = document.getElementById("moodFinal");
let moodActiveIndex = -1;
let moodLastData = null;
let moodHighlightStrength = 0;
let moodHighlightAnimId = 0;
let moodCanvasBound = false;
let moodHoverIndex = -1;
let currentMoodRange = "this_week";

function updateMoodHighlightState() {
    const container = moodTrackerCanvas ? moodTrackerCanvas.parentElement : null;
    if (!container) return;
    container.classList.toggle("is-highlight", moodActiveIndex >= 0);
}

function animateMoodHighlight() {
    if (moodHighlightAnimId) cancelAnimationFrame(moodHighlightAnimId);
    const start = performance.now();
    const from = moodHighlightStrength;
    const to = moodActiveIndex >= 0 ? 1 : 0;
    const duration = 420;
    const tick = (now) => {
        const t = Math.min(1, (now - start) / duration);
        const eased = t < 0.5 ? 4 * t * t * t : 1 - Math.pow(-2 * t + 2, 3) / 2;
        moodHighlightStrength = from + (to - from) * eased;
        drawActiveSlice();
        if (t < 1) {
            moodHighlightAnimId = requestAnimationFrame(tick);
        } else {
            moodHighlightAnimId = 0;
        }
    };
    moodHighlightAnimId = requestAnimationFrame(tick);
}
let currentDate = new Date();
let currentYear = currentDate.getFullYear();
let currentMonth = currentDate.getMonth();

function applyTheme(theme) {
    const isDark = theme === "dark";
    document.documentElement.classList.toggle("dark", isDark);
    if (themeToggle) {
        themeToggle.classList.toggle("is-dark", isDark);
        themeToggle.setAttribute("aria-pressed", isDark ? "true" : "false");
        themeToggle.setAttribute("aria-label", isDark ? "Switch to light mode" : "Switch to dark mode");
        themeToggle.setAttribute("title", isDark ? "Light mode" : "Dark mode");
    }
}

const savedTheme = localStorage.getItem("cm_theme");
applyTheme(savedTheme === "dark" ? "dark" : "light");

if (themeToggle) {
    themeToggle.addEventListener("click", () => {
        const isDark = document.documentElement.classList.contains("dark");
        const nextTheme = isDark ? "light" : "dark";
        localStorage.setItem("cm_theme", nextTheme);
        applyTheme(nextTheme);
    });
}

const monthNames = ["January", "February", "March", "April", "May", "June",
                    "July", "August", "September", "October", "November", "December"];
let currentModalDateRaw = "";
let highlightRequestId = 0;
let activeTagFilter = "all";
let activeReactionFilter = "all";
let currentReactionFilter = "";
let calendarSelectionMode = false;
const selectedCalendarDates = new Set();

function updateCalendarHeader(){
    currentMonthEl.innerText = `◦ ${monthNames[currentMonth]} ${currentYear}`;
}

function formatDateLong(dateStr){
    const parts = dateStr.split('-').map(Number);
    if (parts.length !== 3 || parts.some(n => Number.isNaN(n))) return dateStr;
    const [y, m, d] = parts;
    const monthName = monthNames[m - 1] || "";
    return `${monthName} ${d}, ${y}`;
}

function formatDateLongFull(dateStr){
    const parts = dateStr.split('-').map(Number);
    if (parts.length !== 3 || parts.some(n => Number.isNaN(n))) return dateStr;
    const [y, m, d] = parts;
    const monthName = monthNames[m - 1] || "";
    return `${monthName} ${d}, ${y}`;
}

function getDaysInMonth(year, month){
    return new Date(year, month + 1, 0).getDate();
}

function generateCalendar(){
    calendarEl.innerHTML = "";
    const daysInMonth = getDaysInMonth(currentYear, currentMonth);
    const weekdayLabels = ["Mon","Tue","Wed","Thu","Fri","Sat","Sun"];
    const firstDay = new Date(currentYear, currentMonth, 1).getDay(); // 0=Sun
    const startOffset = (firstDay + 6) % 7; // shift so Monday=0
    const today = new Date();
    const isCurrentMonth = today.getFullYear() === currentYear && today.getMonth() === currentMonth;

    // Weekday headers
    weekdayLabels.forEach(label => {
        const wd = document.createElement("div");
        wd.className = "weekday";
        wd.innerText = label;
        calendarEl.appendChild(wd);
    });

    // Empty cells before first day
    for (let i = 0; i < startOffset; i++) {
        const empty = document.createElement("div");
        empty.className = "day empty";
        calendarEl.appendChild(empty);
    }
    
    // Generate days
    for(let i=1;i<=daysInMonth;i++){
        const day = document.createElement("div");
        day.className = "day";
        day.innerText = i;
        if (isCurrentMonth && i === today.getDate()) {
            day.classList.add("today");
        }
        day.onclick = () => selectDate(i, day);
        calendarEl.appendChild(day);
    }
    
    // Highlight dates with memories
    highlightMemoryDates();
    applySelectedCalendarDates();
}

function formatCalendarDate(year, monthIndex, day) {
    return `${year}-${monthIndex + 1}-${day}`;
}

function applySelectedCalendarDates() {
    if (!calendarEl) return;
    const dayEls = calendarEl.querySelectorAll(".day:not(.empty)");
    dayEls.forEach(el => {
        const dayNum = parseInt(el.innerText, 10);
        const dateKey = formatCalendarDate(currentYear, currentMonth, dayNum);
        el.classList.toggle("multi-selected", selectedCalendarDates.has(dateKey));
    });
}

function changeMonth(direction){
    currentMonth += direction;
    if(currentMonth < 0){
        currentMonth = 11;
        currentYear--;
    } else if(currentMonth > 11){
        currentMonth = 0;
        currentYear++;
    }
    updateCalendarHeader();
    generateCalendar();
}

// Navigation buttons
document.getElementById("prevMonth").onclick = () => changeMonth(-1);
document.getElementById("nextMonth").onclick = () => changeMonth(1);
if (todayBtn) {
    todayBtn.onclick = () => {
        const t = new Date();
        currentYear = t.getFullYear();
        currentMonth = t.getMonth();
        updateCalendarHeader();
        generateCalendar();
        const dayEls = document.querySelectorAll(".day");
        dayEls.forEach(el => {
            if (parseInt(el.innerText, 10) === t.getDate()) {
                selectDate(t.getDate(), el);
            }
        });
    };
}

// Initialize calendar
updateCalendarHeader();
generateCalendar();
loadPinnedMemories();

function openMoodTracker() {
    if (!moodTrackerModal) return;
    moodTrackerModal.style.display = "flex";
    moodTrackerModal.classList.add("show");
    document.body.classList.add("modal-open");
    const range = getActiveMoodRange();
    setMoodRange(range);
    fetchWeeklyMoodData(range);
}

function closeMoodTracker() {
    if (!moodTrackerModal) return;
    moodTrackerModal.classList.remove("show");
    moodTrackerModal.style.display = "none";
    document.body.classList.remove("modal-open");
}


function getActiveMoodRange() {
    const active = document.querySelector(".mood-range-btn.is-active");
    return active ? active.getAttribute("data-range") : "this_week";
}

function setMoodRange(range) {
    currentMoodRange = range;
    moodRangeButtons.forEach(btn => {
        const isActive = btn.getAttribute("data-range") === range;
        btn.classList.toggle("is-active", isActive);
    });
    if (moodTrackerSub) {
        moodTrackerSub.textContent = "";
    }
    if (moodTrackerTitle) {
        const label = range === "last_week" ? "Last Week" : range === "this_month" ? "This Month" : "This Week";
        moodTrackerTitle.textContent = label;
    }
}

function fetchWeeklyMoodData(range = "this_week") {
    if (!moodTrackerCanvas) return;
    fetch(`get_weekly_mood.php?range=${encodeURIComponent(range)}`, { cache: "no-store" })
        .then(res => res.json())
        .then(data => {
            if (!data || !Array.isArray(data.labels)) {
                renderMoodTrackerChart({ labels: [], counts: [] });
                return;
            }
            renderMoodTrackerChart(data);
        })
        .catch(() => renderMoodTrackerChart({ labels: [], counts: [] }));
}

function renderMoodTrackerChart(data) {
    moodLastData = data;
    moodActiveIndex = -1;
    moodHighlightStrength = 0;
    updateMoodHighlightState();
    updateMoodTrackerHeader(data);
    const ctx = moodTrackerCanvas.getContext("2d");
    const container = moodTrackerCanvas.parentElement;
    const cssWidth = container ? container.clientWidth - 4 : 640;
    const cssHeight = 360;
    const dpr = window.devicePixelRatio || 1;
    moodTrackerCanvas.width = cssWidth * dpr;
    moodTrackerCanvas.height = cssHeight * dpr;
    moodTrackerCanvas.style.width = `${cssWidth}px`;
    moodTrackerCanvas.style.height = `${cssHeight}px`;
    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    const width = cssWidth;
    const height = cssHeight;
    ctx.clearRect(0, 0, width, height);

    const labels = data.labels || [];
    const counts = data.counts || [];
    const colors = data.colors || [];

    if (!labels.length) {
        ctx.fillStyle = "#ff69b4";
        ctx.font = "14px Arial";
        ctx.textAlign = "center";
        ctx.fillText("No mood data for the last 7 days.", width / 2, height / 2);
        if (moodTrackerLegend) moodTrackerLegend.innerHTML = "";
        return;
    }

    const total = counts.reduce((sum, v) => sum + v, 0);
    const centerX = width / 2;
    const centerY = height / 2;
    const radius = Math.min(width, height) / 2 - 24;
    let startAngle = -Math.PI / 2;

    const sliceCount = counts.filter(v => v > 0).length;
    const minPctForLabel = 1;
    const baseFontSize = sliceCount > 16 ? 8 : sliceCount > 12 ? 9 : 12;

    counts.forEach((value, index) => {
        if (value <= 0) return;
        const sliceAngle = (value / total) * Math.PI * 2;
        const endAngle = startAngle + sliceAngle;
        const color = colors[index] || "rgba(255,105,180,0.85)";

        ctx.beginPath();
        ctx.moveTo(centerX, centerY);
        ctx.arc(centerX, centerY, radius, startAngle, endAngle);
        ctx.closePath();
        ctx.shadowColor = "rgba(0, 0, 0, 0.25)";
        ctx.shadowBlur = 12;
        ctx.shadowOffsetX = 0;
        ctx.shadowOffsetY = 4;
        ctx.fillStyle = color;
        ctx.fill();
        // Subtle inner gradient for depth
        const grad = ctx.createRadialGradient(centerX, centerY, radius * 0.15, centerX, centerY, radius);
        grad.addColorStop(0, "rgba(255,255,255,0.22)");
        grad.addColorStop(0.6, "rgba(255,255,255,0.08)");
        grad.addColorStop(1, "rgba(0,0,0,0.2)");
        ctx.fillStyle = grad;
        ctx.fill();
        ctx.shadowColor = "transparent";
        ctx.shadowBlur = 0;
        ctx.shadowOffsetX = 0;
        ctx.shadowOffsetY = 0;

        const midAngle = (startAngle + endAngle) / 2;
        const labelRadius = radius * 0.72;
        const labelX = centerX + Math.cos(midAngle) * labelRadius;
        const labelY = centerY + Math.sin(midAngle) * labelRadius;
        const pct = Math.round((value / total) * 100);
        if (pct >= minPctForLabel) {
            ctx.fillStyle = "#ffffff";
            ctx.font = `${baseFontSize}px Arial`;
            ctx.textAlign = "center";
            ctx.textBaseline = "middle";
            const labelText = sliceCount > 16 ? `${pct}%` : labels[index] ? `${labels[index]} ${pct}%` : `${pct}%`;
            ctx.fillText(labelText, labelX, labelY);
        }

        startAngle = endAngle;
    });

    if (moodTrackerLegend) {
        moodTrackerLegend.innerHTML = labels.map((label, idx) => {
            const color = colors[idx] || "rgba(255,105,180,0.8)";
            const count = counts[idx] || 0;
            return `
                <div class="mood-legend-card" data-index="${idx}" style="cursor:pointer;">
                    <span class="mood-legend-dot" style="background:${color};"></span>
                    <span class="mood-legend-label">${label}</span>
                    <span class="mood-legend-count">${count}</span>
                </div>
            `;
        }).join("");
        const legendCards = moodTrackerLegend.querySelectorAll(".mood-legend-card");
        legendCards.forEach(card => {
            card.addEventListener("click", () => {
                const idx = Number(card.getAttribute("data-index"));
                const nextIndex = moodActiveIndex === idx ? -1 : idx;
                if (nextIndex !== -1 && nextIndex !== moodActiveIndex) {
                    moodHighlightStrength = 0;
                }
                moodActiveIndex = nextIndex;
                updateMoodHighlightState();
                animateMoodHighlight();
                updateLegendActive();
            });
        });
    }

    if (moodTrackerCanvas && !moodCanvasBound) {
        moodTrackerCanvas.addEventListener("click", handleMoodCanvasClick);
        moodTrackerCanvas.addEventListener("mousemove", handleMoodCanvasHover);
        moodTrackerCanvas.addEventListener("mouseleave", () => {
            moodHoverIndex = -1;
            if (moodActiveIndex < 0) {
                drawActiveSlice();
            }
        });
        moodCanvasBound = true;
    }
}

function updateMoodTrackerHeader(data) {
    if (!moodTrackerTitle) return;
    const labels = data && Array.isArray(data.labels) ? data.labels : [];
    const counts = data && Array.isArray(data.counts) ? data.counts : [];
    const baseLabel = currentMoodRange === "last_week" ? "Last Week" : currentMoodRange === "this_month" ? "This Month" : "This Week";
    if (!labels.length || !counts.length) {
        moodTrackerTitle.textContent = baseLabel;
        return;
    }
    let maxIdx = 0;
    counts.forEach((val, idx) => {
        if (val > counts[maxIdx]) maxIdx = idx;
    });
    const topMood = labels[maxIdx] || "";
    moodTrackerTitle.textContent = topMood ? `${baseLabel} your mood is ${topMood}` : baseLabel;
}

function updateLegendActive() {
    if (!moodTrackerLegend) return;
    const cards = moodTrackerLegend.querySelectorAll(".mood-legend-card");
    cards.forEach(card => {
        const idx = Number(card.getAttribute("data-index"));
        card.classList.toggle("is-active", idx === moodActiveIndex && moodActiveIndex >= 0);
    });
}

function handleMoodCanvasClick(event) {
    if (!moodLastData || !moodTrackerCanvas) return;
    const rect = moodTrackerCanvas.getBoundingClientRect();
    const x = event.clientX - rect.left;
    const y = event.clientY - rect.top;
    const width = rect.width;
    const height = rect.height;
    const centerX = width / 2;
    const centerY = height / 2;
    const radius = Math.min(width, height) / 2 - 24;
    const dx = x - centerX;
    const dy = y - centerY;
    const dist = Math.hypot(dx, dy);
    if (dist > radius) return;

    let angle = Math.atan2(dy, dx);
    angle = angle < -Math.PI / 2 ? angle + Math.PI * 2 : angle;
    let startAngle = -Math.PI / 2;

    const counts = moodLastData.counts || [];
    const total = counts.reduce((sum, v) => sum + v, 0);
    let hitIndex = -1;
    counts.forEach((value, index) => {
        if (value <= 0) return;
        const sliceAngle = (value / total) * Math.PI * 2;
        const endAngle = startAngle + sliceAngle;
        if (angle >= startAngle && angle < endAngle) {
            hitIndex = index;
        }
        startAngle = endAngle;
    });

    const nextIndex = moodActiveIndex === hitIndex ? -1 : hitIndex;
    if (nextIndex !== -1 && nextIndex !== moodActiveIndex) {
        moodHighlightStrength = 0;
    }
    moodActiveIndex = nextIndex;
    updateMoodHighlightState();
    animateMoodHighlight();
    updateLegendActive();
}

function drawActiveSlice() {
    if (!moodLastData) return;
    updateMoodHighlightState();
    const ctx = moodTrackerCanvas.getContext("2d");
    const container = moodTrackerCanvas.parentElement;
    const cssWidth = container ? container.clientWidth - 4 : 640;
    const cssHeight = 360;
    const dpr = window.devicePixelRatio || 1;
    moodTrackerCanvas.width = cssWidth * dpr;
    moodTrackerCanvas.height = cssHeight * dpr;
    moodTrackerCanvas.style.width = `${cssWidth}px`;
    moodTrackerCanvas.style.height = `${cssHeight}px`;
    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    const width = cssWidth;
    const height = cssHeight;
    ctx.clearRect(0, 0, width, height);

    const labels = moodLastData.labels || [];
    const counts = moodLastData.counts || [];
    const colors = moodLastData.colors || [];
    const total = counts.reduce((sum, v) => sum + v, 0);
    const centerX = width / 2;
    const centerY = height / 2;
    const radius = Math.min(width, height) / 2 - 24;
    let startAngle = -Math.PI / 2;

    const sliceCount = counts.filter(v => v > 0).length;
    const minPctForLabel = 1;
    const baseFontSize = sliceCount > 16 ? 8 : sliceCount > 12 ? 9 : 12;

    counts.forEach((value, index) => {
        if (value <= 0) return;
        const sliceAngle = (value / total) * Math.PI * 2;
        const endAngle = startAngle + sliceAngle;
        const color = colors[index] || "rgba(255,105,180,0.85)";
        const isActive = moodActiveIndex === index;
        const isHover = moodActiveIndex < 0 && moodHoverIndex === index;

        ctx.beginPath();
        ctx.moveTo(centerX, centerY);
        ctx.arc(centerX, centerY, radius, startAngle, endAngle);
        ctx.closePath();
        ctx.shadowColor = "rgba(0, 0, 0, 0.25)";
        ctx.shadowBlur = 12;
        ctx.shadowOffsetX = 0;
        ctx.shadowOffsetY = 4;
        if (moodActiveIndex >= 0 && !isActive) {
            ctx.globalAlpha = 1 - (0.75 * moodHighlightStrength);
        } else if (isHover) {
            ctx.globalAlpha = 1;
        } else {
            ctx.globalAlpha = 1;
        }
        ctx.fillStyle = color;
        ctx.fill();
        ctx.globalAlpha = 1;
        ctx.shadowColor = "transparent";
        ctx.shadowBlur = 0;
        ctx.shadowOffsetX = 0;
        ctx.shadowOffsetY = 0;

        if (isActive || isHover) {
            const glowStrength = isHover ? 0.6 : moodHighlightStrength;
            ctx.lineWidth = 1.5 + 1.5 * glowStrength;
            ctx.strokeStyle = `rgba(199, 24, 91, ${0.35 + 0.25 * glowStrength})`;
            ctx.shadowColor = "rgba(199, 24, 91, 0.5)";
            ctx.shadowBlur = 12 * glowStrength;
            ctx.stroke();
            ctx.shadowColor = "transparent";
            ctx.shadowBlur = 0;
        }

        const midAngle = (startAngle + endAngle) / 2;
        const labelRadius = radius * 0.72;
        const labelX = centerX + Math.cos(midAngle) * labelRadius;
        const labelY = centerY + Math.sin(midAngle) * labelRadius;
        const pct = Math.round((value / total) * 100);
        if (pct >= minPctForLabel) {
            ctx.fillStyle = "#ffffff";
            ctx.font = `${baseFontSize}px Arial`;
            ctx.textAlign = "center";
            ctx.textBaseline = "middle";
            const labelText = sliceCount > 16 ? `${pct}%` : labels[index] ? `${labels[index]} ${pct}%` : `${pct}%`;
            ctx.fillText(labelText, labelX, labelY);
        }

        startAngle = endAngle;
    });
}

function handleMoodCanvasHover(event) {
    if (!moodLastData || !moodTrackerCanvas) return;
    const hitIndex = getSliceIndexFromEvent(event);
    if (hitIndex !== moodHoverIndex) {
        moodHoverIndex = hitIndex;
        drawActiveSlice();
    }
    moodTrackerCanvas.style.cursor = hitIndex >= 0 ? "pointer" : "default";
}

function getSliceIndexFromEvent(event) {
    const rect = moodTrackerCanvas.getBoundingClientRect();
    const x = event.clientX - rect.left;
    const y = event.clientY - rect.top;
    const width = rect.width;
    const height = rect.height;
    const centerX = width / 2;
    const centerY = height / 2;
    const radius = Math.min(width, height) / 2 - 24;
    const dx = x - centerX;
    const dy = y - centerY;
    const dist = Math.hypot(dx, dy);
    if (dist > radius) return -1;

    let angle = Math.atan2(dy, dx);
    angle = angle < -Math.PI / 2 ? angle + Math.PI * 2 : angle;
    let startAngle = -Math.PI / 2;
    const counts = moodLastData.counts || [];
    const total = counts.reduce((sum, v) => sum + v, 0);
    let hitIndex = -1;
    counts.forEach((value, index) => {
        if (value <= 0) return;
        const sliceAngle = (value / total) * Math.PI * 2;
        const endAngle = startAngle + sliceAngle;
        if (angle >= startAngle && angle < endAngle) {
            hitIndex = index;
        }
        startAngle = endAngle;
    });
    return hitIndex;
}

if (moodTrackerBtn) {
    moodTrackerBtn.addEventListener("click", openMoodTracker);
}
if (moodTrackerCloseBtn) {
    moodTrackerCloseBtn.addEventListener("click", closeMoodTracker);
}
if (moodTrackerModal) {
    moodTrackerModal.addEventListener("click", (event) => {
        if (event.target === moodTrackerModal) {
            closeMoodTracker();
        }
    });
}
if (moodRangeButtons && moodRangeButtons.length) {
    moodRangeButtons.forEach(btn => {
        btn.addEventListener("click", () => {
            const range = btn.getAttribute("data-range") || "this_week";
            setMoodRange(range);
            fetchWeeklyMoodData(range);
        });
    });
}

function updateMoodValue() {
    if (!moodFinal) return;
    const selected = moodSelect ? moodSelect.value : "";
    if (selected === "other") {
        if (moodCustomWrap) moodCustomWrap.style.display = "block";
        const customValue = moodCustom ? moodCustom.value.trim() : "";
        moodFinal.value = customValue !== "" ? customValue : "other";
    } else {
        if (moodCustomWrap) moodCustomWrap.style.display = "none";
        if (moodCustom) moodCustom.value = "";
        moodFinal.value = selected;
    }
}

if (moodSelect) {
    moodSelect.addEventListener("change", updateMoodValue);
}
if (moodCustom) {
    moodCustom.addEventListener("input", updateMoodValue);
}
updateMoodValue();

function updateCalendarFilterView() {
    const hasFilter = activeTagFilter !== "all" || activeReactionFilter !== "all";
    if (!hasFilter) {
        calendarEl.style.display = "grid";
        if (calendarListEl) calendarListEl.style.display = "none";
        currentReactionFilter = "";
        if (calendarFilterBack) calendarFilterBack.style.display = "none";
        highlightMemoryDates();
        return;
    }
    calendarEl.style.display = "none";
    if (calendarListEl) calendarListEl.style.display = "block";
    currentReactionFilter = activeReactionFilter !== "all" ? activeReactionFilter : "";
    if (calendarFilterBack) calendarFilterBack.style.display = "inline-flex";
    loadFilteredDateList(activeTagFilter, activeReactionFilter);
    // Disable selection mode when list view is active
    setCalendarSelectionMode(false, true);
}

if (calendarTagFilter) {
    calendarTagFilter.addEventListener("change", () => {
        activeTagFilter = calendarTagFilter.value;
        updateCalendarFilterView();
    });
}

if (calendarReactionFilter) {
    calendarReactionFilter.addEventListener("change", () => {
        activeReactionFilter = calendarReactionFilter.value;
        updateCalendarFilterView();
    });
}

if (calendarFilterBack) {
    calendarFilterBack.addEventListener("click", () => {
        activeTagFilter = "all";
        activeReactionFilter = "all";
        if (calendarTagFilter) calendarTagFilter.value = "all";
        if (calendarReactionFilter) calendarReactionFilter.value = "all";
        updateCalendarFilterView();
    });
}

// Check for success/error messages from URL parameters
const urlParams = new URLSearchParams(window.location.search);
function showToast(message) {
    const toast = document.createElement("div");
    toast.textContent = message;
    toast.style.position = "fixed";
    toast.style.right = "22px";
    toast.style.bottom = "22px";
    toast.style.padding = "10px 14px";
    toast.style.background = "rgba(255, 105, 180, 0.92)";
    toast.style.color = "#fff";
    toast.style.borderRadius = "12px";
    toast.style.boxShadow = "0 10px 24px rgba(255, 20, 147, 0.25)";
    toast.style.zIndex = "4000";
    toast.style.fontSize = "13px";
    toast.style.backdropFilter = "blur(6px)";
    document.body.appendChild(toast);
    setTimeout(() => {
        toast.style.transition = "opacity 0.3s ease, transform 0.3s ease";
        toast.style.opacity = "0";
        toast.style.transform = "translateY(6px)";
        setTimeout(() => toast.remove(), 300);
    }, 1200);
}

function setCalendarSelectionMode(isActive, skipToast = false) {
    calendarSelectionMode = isActive;
    if (calendarSelectToggle) {
        if (!isActive) {
            calendarSelectToggle.textContent = "Select Dates";
            calendarSelectToggle.classList.remove("is-active", "is-danger");
        } else {
            calendarSelectToggle.textContent = selectedCalendarDates.size > 0 ? `Delete Selected (${selectedCalendarDates.size})` : "Select Dates";
            calendarSelectToggle.classList.add("is-active");
            calendarSelectToggle.classList.toggle("is-danger", selectedCalendarDates.size > 0);
        }
    }
    if (calendarCancelSelectBtn) {
        calendarCancelSelectBtn.style.display = isActive ? "inline-flex" : "none";
    }
    if (calendarSelectAllBtn) {
        calendarSelectAllBtn.style.display = isActive ? "inline-flex" : "none";
    }
    if (!isActive) {
        selectedCalendarDates.clear();
        applySelectedCalendarDates();
    }
    updateCalendarSelectionUI();
    if (isActive && !skipToast) {
        showToast("Select dates with memories to delete.");
    }
}

function updateCalendarSelectionUI() {
    if (selectedDateEl) {
        if (calendarSelectionMode && selectedCalendarDates.size > 0) {
            selectedDateEl.innerText = `${selectedCalendarDates.size} selected`;
        } else if (calendarSelectionMode) {
            selectedDateEl.innerText = "Select dates";
        }
    }
    if (calendarSelectToggle && calendarSelectionMode) {
        calendarSelectToggle.textContent = selectedCalendarDates.size > 0 ? `Delete Selected (${selectedCalendarDates.size})` : "Select Dates";
        calendarSelectToggle.classList.toggle("is-danger", selectedCalendarDates.size > 0);
    }
}

if (calendarSelectToggle) {
    calendarSelectToggle.addEventListener("click", () => {
        if (!calendarSelectionMode) {
            setCalendarSelectionMode(true);
            return;
        }
        if (selectedCalendarDates.size === 0) {
            setCalendarSelectionMode(false);
            return;
        }
        openConfirm(`Delete memories for ${selectedCalendarDates.size} selected date(s)?`, () => {
            const dates = Array.from(selectedCalendarDates);
            Promise.all(dates.map(date =>
                fetch(`delete_memories.php?date=${encodeURIComponent(date)}`, { cache: "no-store" })
                    .then(res => res.json())
            )).then(() => {
                showToast("Selected dates deleted.");
                selectedCalendarDates.clear();
                applySelectedCalendarDates();
                updateCalendarSelectionUI();
                highlightMemoryDates();
                loadPinnedMemories();
                setCalendarSelectionMode(false, true);
            }).catch(() => {
                showToast("Delete failed. Please try again.");
            });
        });
    });
}
if (calendarSelectAllBtn) {
    calendarSelectAllBtn.addEventListener("click", () => {
        if (!calendarSelectionMode) return;
        const dayEls = calendarEl.querySelectorAll(".day.hasMemory:not(.empty)");
        dayEls.forEach(el => {
            const dayNum = parseInt(el.innerText, 10);
            const dateKey = formatCalendarDate(currentYear, currentMonth, dayNum);
            selectedCalendarDates.add(dateKey);
            el.classList.add("multi-selected");
        });
        updateCalendarSelectionUI();
    });
}
if (calendarCancelSelectBtn) {
    calendarCancelSelectBtn.addEventListener("click", () => {
        setCalendarSelectionMode(false);
    });
}
if (urlParams.has('success')) {
    showToast("Memory saved successfully!");
    const cleanUrl = new URL(window.location.href);
    cleanUrl.searchParams.delete('success');
    history.replaceState({}, "", cleanUrl.toString());
} else if (urlParams.has('error')) {
    const error = urlParams.get('error');
    if (error === 'date_exists') {
        showToast("This date already has memories for the selected day.");
    } else if (error === 'no_files') {
        showToast("Please select at least one photo to upload.");
    } else if (error === 'upload_errors' && urlParams.has('details')) {
        const details = decodeURIComponent(urlParams.get('details'));
        showToast("Upload error. Please try again.");
    } else if (error === 'tag_mismatch') {
        const existingTag = decodeURIComponent(urlParams.get('existing_tag'));
        const newTag = decodeURIComponent(urlParams.get('new_tag'));
        showToast(`This date already has "${existingTag}" tag.`);
    } else if (error === 'invalid_date') {
        showToast("Please select a valid date before saving.");
    }
}

// If a date was just saved, jump to its month and highlight it
if (urlParams.has('date')) {
    const savedDate = urlParams.get('date');
    const parts = savedDate.split('-').map(Number);
    if (parts.length === 3 && !Number.isNaN(parts[0]) && !Number.isNaN(parts[1]) && !Number.isNaN(parts[2])) {
        currentYear = parts[0];
        currentMonth = parts[1] - 1;
        updateCalendarHeader();
        generateCalendar();
        // Auto-select the saved day after highlights render
        setTimeout(() => {
            const dayEls = document.querySelectorAll(".day");
            dayEls.forEach(el => {
                if (parseInt(el.innerText, 10) === parts[2]) {
                    selectDate(parts[2], el);
                }
            });
            fetchMemories(savedDate, currentReactionFilter);
        }, 0);
    }
}

// Fetch dates with memories and highlight them
function highlightMemoryDates(){
    if (activeTagFilter !== "all" || activeReactionFilter !== "all") return;
    const requestId = ++highlightRequestId;
    // First, remove all existing highlights and tags
    document.querySelectorAll(".day").forEach(day => {
        day.classList.remove("hasMemory");
        const existingTags = day.querySelector(".tag-indicators");
        if (existingTags) {
            existingTags.remove();
        }
        const existingReaction = day.querySelector(".reaction-indicator");
        if (existingReaction) {
            existingReaction.remove();
        }
    });

    const cacheBust = Date.now();
    fetch(`get_memory_tags.php?year=${currentYear}&month=${currentMonth + 1}&_=${cacheBust}`, { cache: "no-store" })
    .then(res=>res.json())
    .then(memories=>{
        if (requestId !== highlightRequestId) return;
        Object.keys(memories).forEach(day => {
            const dayNum = parseInt(day);
            const entry = memories[day];
            const primaryTag = Array.isArray(entry) ? entry[0] : (entry && entry.tag ? entry.tag : "");
            const primaryReaction = entry && entry.reaction ? entry.reaction : "";
            const dayElements = document.querySelectorAll(".day");
            dayElements.forEach(el=>{
                if(el.innerText == dayNum){
                    el.classList.add("hasMemory");
                    
                    // Add tag indicators
                    const tagContainer = document.createElement("div");
                    tagContainer.className = "tag-indicators";
                    
                    // Show only the primary tag (first one in the array)
                    const tagEmoji = getTagEmoji(primaryTag);
                    const tagSpan = document.createElement("span");
                    tagSpan.className = "tag-emoji";
                    tagSpan.innerText = tagEmoji;
                    tagContainer.appendChild(tagSpan);
                    
                    el.appendChild(tagContainer);

                    if (primaryReaction) {
                        const reactSpan = document.createElement("span");
                        reactSpan.className = "reaction-indicator";
                        reactSpan.innerText = primaryReaction;
                        el.appendChild(reactSpan);
                    }
                }
            });
        });
    });
}

function updateDayReactionForDate(dateStr, reaction) {
    if (!dateStr) return;
    const parts = dateStr.split('-').map(Number);
    if (parts.length !== 3 || parts.some(n => Number.isNaN(n))) return;
    const [y, m, d] = parts;
    if (y !== currentYear || (m - 1) !== currentMonth) return;
    const dayEls = document.querySelectorAll(".day");
    dayEls.forEach(el => {
        if (parseInt(el.innerText, 10) === d) {
            const existingReaction = el.querySelector(".reaction-indicator");
            if (existingReaction) existingReaction.remove();
            if (reaction) {
                const reactSpan = document.createElement("span");
                reactSpan.className = "reaction-indicator";
                reactSpan.innerText = reaction;
                el.appendChild(reactSpan);
            }
        }
    });
}

function loadFilteredDateList(tag, reaction) {
    if (!calendarListEl) return;
    calendarListEl.innerHTML = "<div class='calendar-list-empty'>Loading...</div>";
    const tagParam = tag || "all";
    const reactionParam = reaction || "all";
    fetch(`get_filtered_dates.php?tag=${encodeURIComponent(tagParam)}&reaction=${encodeURIComponent(reactionParam)}`, { cache: "no-store" })
    .then(res => res.json())
    .then(dates => {
        if (!Array.isArray(dates) || dates.length === 0) {
            calendarListEl.innerHTML = "<div class='calendar-list-empty'>No dates found for this filter.</div>";
            return;
        }
        const items = dates
        .filter(item => {
            if (reactionParam !== "all") {
                if (!item.reaction || item.reaction !== reactionParam) return false;
            }
            if (tagParam !== "all") {
                if (!item.tag || item.tag !== tagParam) return false;
            }
            return true;
        })
        .map(item => {
            const dateStr = item.date || "";
            const label = formatDateLongFull(dateStr);
            const tagLabel = item.tag ? item.tag : tagParam;
            const reactLabel = item.reaction ? item.reaction : "";
            return `
                <div class="calendar-list-item" data-date="${dateStr}" data-reaction="${reactLabel}">
                    <div class="calendar-list-date">${label}</div>
                    <div class="calendar-list-meta">
                        <span class="calendar-list-tag">${tagLabel}</span>
                        ${reactLabel ? `<span class="calendar-list-reaction">${reactLabel}</span>` : ""}
                    </div>
                </div>
            `;
        }).join("");
        calendarListEl.innerHTML = `<div class="calendar-list-grid">${items}</div>`;

        calendarListEl.querySelectorAll(".calendar-list-item").forEach(item => {
            item.addEventListener("click", () => {
                const dateStr = item.getAttribute("data-date");
                const reactionValue = item.getAttribute("data-reaction") || "";
                if (!dateStr) return;
                const parts = dateStr.split("-").map(Number);
                if (parts.length === 3 && !parts.some(n => Number.isNaN(n))) {
                    currentYear = parts[0];
                    currentMonth = parts[1] - 1;
                    updateCalendarHeader();
                    generateCalendar();
                }
                currentReactionFilter = reactionValue;
                selectedDateEl.innerText = dateStr;
                memoryDateInput.value = dateStr;
                fetchMemories(dateStr, reactionValue);
            });
        });
    })
    .catch(() => {
        calendarListEl.innerHTML = "<div class='calendar-list-empty'>Failed to load dates.</div>";
    });
}

function getTagEmoji(tag) {
    const tagEmojis = {
        'general': 'General',
        'birthday': 'Birthday',
        'anniversary': 'Anniversary',
        'holiday': 'Holiday',
        'travel': 'Travel',
        'food': 'Food',
        'family': 'Family',
        'friends': 'Friends',
        'work': 'Work',
        'special': 'Special'
    };
    // Return the tag as-is for custom tags, or the predefined name for standard tags
    return tagEmojis[tag] || tag;
}

function selectDate(day, element){
    if (calendarSelectionMode) {
        if (!element.classList.contains("hasMemory")) {
            showToast("No memories on this date.");
            return;
        }
        const dateKey = formatCalendarDate(currentYear, currentMonth, day);
        if (selectedCalendarDates.has(dateKey)) {
            selectedCalendarDates.delete(dateKey);
            element.classList.remove("multi-selected");
        } else {
            selectedCalendarDates.add(dateKey);
            element.classList.add("multi-selected");
        }
        updateCalendarSelectionUI();
        return;
    }
    document.querySelectorAll(".day").forEach(d=>d.classList.remove("selected"));
    element.classList.add("selected");

    const dateStr = formatCalendarDate(currentYear, currentMonth, day);
    selectedDateEl.innerText = dateStr;
    memoryDateInput.value = dateStr;

    // Only fetch memories if the date has highlights (has memories)
    if (element.classList.contains("hasMemory")) {
                fetchMemories(dateStr, currentReactionFilter);
    } else {
        // For dates without highlights, clear any existing modal content and hide modal
        document.getElementById("modalMemories").innerHTML = "";
        const modalHide = document.getElementById("memoryModal");
        if (modalHide) {
            modalHide.classList.remove("show");
            modalHide.style.display = "none";
        }
        document.body.classList.remove("modal-open");
        document.getElementById("deleteMemoriesBtn").style.display = "none";
    }
    
    // Auto-set tag based on existing memories for this date
    if (element.classList.contains("hasMemory")) {
        // Get the tag indicator from the calendar day
        const tagIndicator = element.querySelector(".tag-emoji");
        if (tagIndicator) {
            const existingTag = tagIndicator.innerText;
            // Set the tag select to match the existing tag
            setTagForExistingDate(existingTag);
        }
    } else {
        // Reset to default for new dates
        document.getElementById("tag").value = "general";
        document.getElementById("customTagContainer").style.display = "none";
        document.getElementById("customTagInput").value = "";
        document.getElementById("customTagInput").disabled = false;
        // Re-enable the tag select for new dates
        document.getElementById("tag").disabled = false;
    }

}

function setTagForExistingDate(existingTag) {
    // Map the tag emoji back to the select option value
    const tagMap = {
        'General': 'general',
        'Birthday': 'birthday', 
        'Anniversary': 'anniversary',
        'Holiday': 'holiday',
        'Travel': 'travel',
        'Food': 'food',
        'Family': 'family',
        'Friends': 'friends',
        'Work': 'work',
        'Special': 'special'
    };
    
    const selectValue = tagMap[existingTag] || 'custom';
    
    if (selectValue === 'custom') {
        // For custom tags, set to custom and fill the input
        document.getElementById("tag").value = "custom";
        document.getElementById("customTagContainer").style.display = "block";
        document.getElementById("customTagInput").value = existingTag;
        // Disable the custom tag input to prevent changes
        document.getElementById("customTagInput").disabled = true;
    } else {
        // For standard tags, set the select value
        document.getElementById("tag").value = selectValue;
        document.getElementById("customTagContainer").style.display = "none";
        document.getElementById("customTagInput").value = "";
    }
    
    // Disable the tag select dropdown to prevent changes
    document.getElementById("tag").disabled = true;
}

function fetchMemories(date, reactionFilter = ""){
    const reactionParam = reactionFilter ? `&reaction=${encodeURIComponent(reactionFilter)}` : "";
    fetch("fetch_memories.php?date="+date+reactionParam, { cache: "no-store" })
    .then(res=>res.text())
    .then(data=>{
        if(data.includes("No memories found")){
            // Don't show modal when there are no memories - just update the selected date display
            document.getElementById("selectedDate").innerText = date;
            document.getElementById("memoryDateInput").value = date;
            // Clear any existing modal content and hide modal
            document.getElementById("modalMemories").innerHTML = "";
            const modal = document.getElementById("memoryModal");
            modal.classList.remove("show");
            modal.classList.remove("show");
            modal.style.display = "none";
            document.body.classList.remove("modal-open");
            document.getElementById("deleteMemoriesBtn").style.display = "none";
        } else {
            currentModalDateRaw = date;
            document.getElementById("modalTitle").innerText = `Memories for ${formatDateLong(date)}`;
            document.getElementById("modalMemories").innerHTML = data;
            const modal = document.getElementById("memoryModal");
            const modalContent = modal.querySelector(".modal-content");
            if (modalContent) modalContent.classList.remove("delete-confirmation");
            modal.style.display = "flex";
            modal.classList.add("show");
            modal.classList.add("show");
            document.body.classList.add("modal-open");
            initModalStars();
            // Show delete button only when there are memories
            document.getElementById("deleteMemoriesBtn").style.display = "block";
            modal.classList.remove("selection-mode");
            applySelectionMode(false);
            bindMemoryCardScroll();
            bindVideoReactions();
            bindPicReactions();
            bindPinButtons();
            bindSelectionControls();
            // Set saved reactions on load (map by data-index between columns)
            document.querySelectorAll('.memory-data-card').forEach(card => {
                const msg = card.querySelector('.memory-message');
                const index = card.getAttribute('data-index');
                const picCard = document.querySelector(`.memory-pic-card[data-index="${index}"]`);
                const videoTrigger = picCard ? picCard.querySelector('.video-reaction-trigger') : null;
                const videoButtons = picCard ? picCard.querySelectorAll('.video-react-btn') : [];
                const picTrigger = picCard ? picCard.querySelector('.pic-reaction-trigger') : null;
                const picButtons = picCard ? picCard.querySelectorAll('.pic-react-btn') : [];
                if (msg && videoTrigger) {
                    const savedReaction = msg.dataset.reaction || "";
                    if (savedReaction) {
                        videoTrigger.textContent = savedReaction;
                        videoButtons.forEach(b => {
                            b.classList.toggle('active', b.textContent === savedReaction);
                        });
                    } else {
                        videoTrigger.textContent = 'React';
                        videoButtons.forEach(b => b.classList.remove('active'));
                    }
                    videoButtons.forEach(b => {
                        b.style.display = "";
                    });
                }
                if (msg && picTrigger) {
                    const savedReaction = msg.dataset.reaction || "";
                    if (savedReaction) {
                        picTrigger.textContent = savedReaction;
                        picButtons.forEach(b => {
                            b.classList.toggle('active', b.textContent === savedReaction);
                        });
                    } else {
                        picTrigger.textContent = 'React';
                        picButtons.forEach(b => b.classList.remove('active'));
                    }
                }
            });
        }
    });
}

function bindPinButtons() {
    const buttons = document.querySelectorAll(".pin-btn");
    buttons.forEach(btn => {
        if (btn.dataset.bound === "true") return;
        btn.dataset.bound = "true";
        btn.addEventListener("click", (e) => {
            e.stopPropagation();
            const memoryId = btn.getAttribute("data-id");
            if (!memoryId) return;
            fetch("pin_memory.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `memory_id=${encodeURIComponent(memoryId)}`
            })
            .then(res => res.json())
            .then(data => {
                if (!data.success) {
                    alert("Error: " + (data.error || "Unable to pin"));
                    return;
                }
                const pinned = data.pinned;
                btn.dataset.pinned = pinned ? "1" : "0";
                btn.textContent = pinned ? "Pinned" : "Pin";
                const card = btn.closest(".memory-data-card");
                if (card) {
                    card.classList.toggle("is-pinned", !!pinned);
                }
                loadPinnedMemories();
            })
            .catch(err => {
                alert("Error: " + err);
            });
        });
    });
}

function loadPinnedMemories() {
    const list = document.getElementById("pinnedList");
    if (!list) return;
    list.innerHTML = "<div class='pinned-empty'>Loading...</div>";
    fetch("fetch_pins.php", { cache: "no-store" })
    .then(res => res.json())
    .then(data => {
        if (!data.success) {
            list.innerHTML = "<div class='pinned-empty'>No pinned memories yet.</div>";
            return;
        }
        const pins = data.pins || [];
        if (!pins.length) {
            list.innerHTML = "<div class='pinned-empty'>No pinned memories yet.</div>";
            return;
        }
        list.innerHTML = pins.map(p => {
            const label = formatDateLongFull(p.date);
            const tag = p.tag || "General";
            return `
                <div class="pinned-item" data-date="${p.date}">
                    <div class="pinned-date">${label}</div>
                    <div class="pinned-tag">${tag}</div>
                </div>
            `;
        }).join("");

        list.querySelectorAll(".pinned-item").forEach(item => {
            item.addEventListener("click", () => {
                const dateStr = item.getAttribute("data-date");
                if (!dateStr) return;
                const parts = dateStr.split("-").map(Number);
                if (parts.length === 3 && !parts.some(n => Number.isNaN(n))) {
                    currentYear = parts[0];
                    currentMonth = parts[1] - 1;
                    updateCalendarHeader();
                    generateCalendar();
                }
                selectedDateEl.innerText = dateStr;
                memoryDateInput.value = dateStr;
                fetchMemories(dateStr, currentReactionFilter);
            });
        });
    })
    .catch(() => {
        list.innerHTML = "<div class='pinned-empty'>No pinned memories yet.</div>";
    });
}

let selectionModeActive = false;

function applySelectionMode(isActive) {
    selectionModeActive = isActive;
    const modal = document.getElementById("memoryModal");
    if (!modal) return;
    const modalContent = modal.querySelector(".modal-content");
    const modalGrid = document.querySelector(".modal-memories-grid");
    if (isActive) {
        modal.classList.add("selection-mode");
        if (modalContent) modalContent.classList.add("selection-mode");
        if (modalGrid) modalGrid.classList.add("selection-mode");
        document.querySelectorAll('.pic-select-wrap').forEach(el => {
            el.style.display = "inline-flex";
        });
    } else {
        modal.classList.remove("selection-mode");
        if (modalContent) modalContent.classList.remove("selection-mode");
        if (modalGrid) modalGrid.classList.remove("selection-mode");
        document.querySelectorAll('.pic-select-wrap').forEach(el => {
            el.style.display = "";
        });
    }
}

function deleteMemory(memoryId, date) {
    openConfirm("Are you sure you want to delete this memory?", () => {
        fetch("delete_memories.php?id=" + memoryId)
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                alert("Memory deleted successfully!");
                // Refresh the memories display
                fetchMemories(date, currentReactionFilter);
                // Refresh calendar highlights to update the date indicators
                highlightMemoryDates();
                loadPinnedMemories();
            } else {
                alert("Error deleting memory: " + data.error);
            }
        })
        .catch(error => {
            alert("Error: " + error);
        });
    });
}

function deletePicture(memoryId, date) {
    openConfirm("Delete this picture only?", () => {
        fetch("delete_picture.php?id=" + memoryId)
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                alert("Picture deleted successfully!");
                fetchMemories(date, currentReactionFilter);
                loadPinnedMemories();
            } else {
                alert("Error deleting picture: " + data.error);
            }
        })
        .catch(error => {
            alert("Error: " + error);
        });
    });
}

function deleteVideo(memoryId, date) {
    openConfirm("Delete this video only?", () => {
        fetch("delete_video.php?id=" + memoryId)
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                alert("Video deleted successfully!");
                fetchMemories(date, currentReactionFilter);
                loadPinnedMemories();
            } else {
                alert("Error deleting video: " + data.error);
            }
        })
        .catch(error => {
            alert("Error: " + error);
        });
    });
}

// EDIT FUNCTIONALITY
let editingMemoryId = null;
let originalMessage = "";

function toggleEdit(memoryId) {
    const messageEl = document.getElementById(`message-${memoryId}`);
    const actionsEl = document.getElementById(`actions-${memoryId}`);
    const existingPinBtn = actionsEl ? actionsEl.querySelector(".pin-btn") : null;
    if (actionsEl) {
        actionsEl.dataset.pinned = existingPinBtn && existingPinBtn.dataset.pinned === "1" ? "1" : "0";
    }
    
    if (editingMemoryId === memoryId) {
        // Save changes
        saveMemory(memoryId);
    } else {
        // Start editing
        if (editingMemoryId) {
            // Cancel any other editing mode first
            cancelEdit(editingMemoryId);
        }
        
        originalMessage = messageEl.innerText;
        editingMemoryId = memoryId;
        
        // Replace paragraph with textarea
        const textarea = document.createElement('textarea');
        textarea.className = 'memory-message-edit';
        textarea.value = originalMessage;
        textarea.rows = 3;
        textarea.onkeydown = function(e) {
            if (e.ctrlKey && e.key === 'Enter') {
                saveMemory(memoryId);
            } else if (e.key === 'Escape') {
                cancelEdit(memoryId);
            }
        };
        
        messageEl.style.display = 'none';
        messageEl.parentNode.insertBefore(textarea, actionsEl);

        // Add image replace input
        const uploadWrap = document.createElement('div');
        uploadWrap.className = 'memory-edit-uploads';

        const imageLabel = document.createElement('label');
        imageLabel.className = 'memory-edit-label';
        const hasMedia = messageEl.dataset.hasImage === '1' || messageEl.dataset.hasVideo === '1';
        imageLabel.textContent = hasMedia ? 'Replace Media' : 'Add Media';
        const imageName = document.createElement('span');
        imageName.className = 'memory-edit-filename';
        imageName.textContent = 'No file';
        const imageInput = document.createElement('input');
        imageInput.type = 'file';
        imageInput.accept = 'image/*,video/*';
        imageInput.className = 'memory-image-edit';
        imageInput.addEventListener('change', () => {
            imageName.textContent = imageInput.files && imageInput.files[0] ? imageInput.files[0].name : 'No file';
        });
        imageLabel.appendChild(imageInput);
        imageLabel.appendChild(imageName);

        const audioLabel = document.createElement('label');
        audioLabel.className = 'memory-edit-label';
        audioLabel.textContent = messageEl.dataset.hasAudio === '1' ? 'Replace Music' : 'Add Music';
        const audioName = document.createElement('span');
        audioName.className = 'memory-edit-filename';
        audioName.textContent = 'No file';
        const audioInput = document.createElement('input');
        audioInput.type = 'file';
        audioInput.accept = 'audio/*';
        audioInput.className = 'memory-audio-edit';
        audioInput.addEventListener('change', () => {
            audioName.textContent = audioInput.files && audioInput.files[0] ? audioInput.files[0].name : 'No file';
        });
        audioLabel.appendChild(audioInput);
        audioLabel.appendChild(audioName);

        uploadWrap.appendChild(imageLabel);
        uploadWrap.appendChild(audioLabel);
        messageEl.parentNode.insertBefore(uploadWrap, actionsEl);

        // Add inline mood selector beside tag/mood pill
        const dataCard = messageEl.closest('.memory-data-card');
        const tagRow = dataCard ? dataCard.querySelector('.memory-tag-row') : null;
        const moodPill = dataCard ? dataCard.querySelector('.memory-mood') : null;
        if (tagRow) {
            const moodSelect = document.createElement('select');
            moodSelect.className = 'tag-select memory-mood-edit-inline';
            const moodOptions = [
                'Happy','Calm','Grateful','Excited','Sad','Angry','Anxious','Tired','Stressed','Proud','Loved',
                'Peaceful','Hopeful','Bored','Lonely','Motivated','Overwhelmed','Inspired','Confident','Sleepy','Other'
            ];
            const currentMood = (messageEl.dataset.mood || '').toLowerCase();
            moodOptions.forEach(opt => {
                const option = document.createElement('option');
                option.value = opt.toLowerCase();
                option.textContent = opt;
                if (currentMood === opt.toLowerCase()) {
                    option.selected = true;
                }
                moodSelect.appendChild(option);
            });
            if (moodPill) moodPill.style.display = 'none';
            tagRow.appendChild(moodSelect);
        }
        
        // Update buttons
        actionsEl.innerHTML = `
            <button class="action-btn save-btn" onclick="saveMemory(${memoryId})" style="padding: 4px 8px; font-size: 11px; background: #4CAF50; color: white; border: none; border-radius: 50px; cursor: pointer; min-width: 60px; height: 26px; display: flex; align-items: center; justify-content: center; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">SAVE</button>
            <button class="action-btn cancel-btn" onclick="cancelEdit(${memoryId})" style="padding: 4px 8px; font-size: 11px; background: #ff69b4; color: white; border: none; border-radius: 50px; cursor: pointer; min-width: 60px; height: 26px; display: flex; align-items: center; justify-content: center; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">CANCEL</button>
            <button class="action-btn" onclick="confirmDownload('${messageEl.dataset.imagePath}')" style="padding: 4px 8px; font-size: 11px; background: #ff69b4; color: white; border: none; border-radius: 50px; cursor: pointer; min-width: 60px; height: 26px; display: flex; align-items: center; justify-content: center;">DOWNLOAD</button>
        `;

        textarea.focus();
        textarea.select();
    }
}

function saveMemory(memoryId) {
    const container = document.querySelector(`#message-${memoryId}`).parentNode;
    const textarea = container.querySelector('textarea');
    const imageInput = container.querySelector('.memory-image-edit');
    const audioInput = container.querySelector('.memory-audio-edit');
    const dataCard = container.closest('.memory-data-card');
    const moodSelect = dataCard ? dataCard.querySelector('.memory-mood-edit-inline') : null;
    const newMessage = textarea.value.trim();
    
    if (newMessage === '') {
        alert('Message cannot be empty!');
        return;
    }

    const hasNewImage = imageInput && imageInput.files && imageInput.files[0];
    const hasNewAudio = audioInput && audioInput.files && audioInput.files[0];
    const newMood = moodSelect ? moodSelect.value : '';
    if (newMessage === originalMessage && !hasNewImage && !hasNewAudio && newMood === (document.querySelector(`#message-${memoryId}`).dataset.mood || '').toLowerCase()) {
        cancelEdit(memoryId);
        return;
    }
    
    // Disable buttons to prevent multiple saves
    const actionsEl = document.getElementById(`actions-${memoryId}`);
    const buttons = actionsEl.querySelectorAll('button');
    buttons.forEach(btn => {
        btn.disabled = true;
        btn.style.opacity = '0.5';
    });
    
    const formData = new FormData();
    formData.append('id', memoryId);
    formData.append('message', newMessage);
    formData.append('mood', newMood);
    if (imageInput && imageInput.files && imageInput.files[0]) {
        formData.append('media', imageInput.files[0]);
    }
    if (audioInput && audioInput.files && audioInput.files[0]) {
        formData.append('audio', audioInput.files[0]);
    }

    fetch('update_memory.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(data => {
        if (data.includes('successfully')) {
            // Update display
            const messageEl = document.getElementById(`message-${memoryId}`);
            messageEl.innerText = newMessage;
            messageEl.style.display = 'block';
            
            // Remove textarea and image input
            textarea.remove();
            const uploadWrap = container.querySelector('.memory-edit-uploads');
            if (uploadWrap) uploadWrap.remove();
            if (moodSelect) moodSelect.remove();
            const dataCard = container.closest('.memory-data-card');
            const moodPill = dataCard ? dataCard.querySelector('.memory-mood') : null;
            if (moodPill) moodPill.style.display = '';
            
            // Restore original buttons
actionsEl.innerHTML = `
                <button class="action-btn edit-btn" onclick="toggleEdit(${memoryId})" style="padding: 4px 8px; font-size: 11px; background: #ff69b4; color: white; border: none; border-radius: 50px; cursor: pointer; min-width: 60px; height: 26px; display: flex; align-items: center; justify-content: center;">Edit</button>
                <button class="action-btn" onclick="confirmDownload('${messageEl.dataset.imagePath}')" style="padding: 4px 8px; font-size: 11px; background: #ff69b4; color: white; border: none; border-radius: 50px; cursor: pointer; min-width: 60px; height: 26px; display: flex; align-items: center; justify-content: center;">DOWNLOAD</button>
            `;
            editingMemoryId = null;
            originalMessage = "";
            
            alert('Memory updated successfully!');
            // Refresh to show updated image if replaced
            if (messageEl.dataset.date) {
                fetchMemories(messageEl.dataset.date, currentReactionFilter);
            }
        } else {
            alert('Error updating memory: ' + data);
            // Re-enable buttons on error
            buttons.forEach(btn => {
                btn.disabled = false;
                btn.style.opacity = '1';
            });
        }
    })
    .catch(error => {
        alert('Error: ' + error);
        // Re-enable buttons on error
        buttons.forEach(btn => {
            btn.disabled = false;
            btn.style.opacity = '1';
        });
    });
}

function cancelEdit(memoryId) {
    const messageEl = document.getElementById(`message-${memoryId}`);
    const textarea = messageEl.parentNode.querySelector('textarea');
    const imageInput = messageEl.parentNode.querySelector('.memory-image-edit');
    const audioInput = messageEl.parentNode.querySelector('.memory-audio-edit');
    const actionsEl = document.getElementById(`actions-${memoryId}`);
    const pinned = actionsEl && actionsEl.dataset.pinned === "1";
    const pinLabel = pinned ? "Pinned" : "Pin";
    
    // Restore original message
    messageEl.innerText = originalMessage;
    messageEl.style.display = 'block';
    
    // Remove textarea
    if (textarea) textarea.remove();
    const uploadWrap = messageEl.parentNode.querySelector('.memory-edit-uploads');
    if (uploadWrap) uploadWrap.remove();
    const dataCard = messageEl.closest('.memory-data-card');
    const moodSelect = dataCard ? dataCard.querySelector('.memory-mood-edit-inline') : null;
    if (moodSelect) moodSelect.remove();
    const moodPill = dataCard ? dataCard.querySelector('.memory-mood') : null;
    if (moodPill) moodPill.style.display = '';
    
// Restore original buttons
actionsEl.innerHTML = `
        <button class="action-btn edit-btn" onclick="toggleEdit(${memoryId})" style="padding: 4px 8px; font-size: 11px; background: #ff69b4; color: white; border: none; border-radius: 50px; cursor: pointer; min-width: 60px; height: 26px; display: flex; align-items: center; justify-content: center;">EDIT</button>
        <button class="action-btn pin-btn" data-id="${memoryId}" data-pinned="${pinned ? "1" : "0"}" style="padding: 4px 8px; font-size: 11px; background: #ffb6d9; color: #b30059; border: none; border-radius: 50px; cursor: pointer; min-width: 60px; height: 26px; display: flex; align-items: center; justify-content: center;">${pinLabel}</button>
        <button class="action-btn" onclick="confirmDownload('${messageEl.dataset.imagePath}')" style="padding: 4px 8px; font-size: 11px; background: #ff69b4; color: white; border: none; border-radius: 50px; cursor: pointer; min-width: 60px; height: 26px; display: flex; align-items: center; justify-content: center;">DOWNLOAD</button>
    `;
    bindPinButtons();
    
    editingMemoryId = null;
    originalMessage = "";
}




function confirmDownload(imagePath) {
    openConfirm("Do you want to download this file?", () => {
        // Create a temporary link to trigger download
        const link = document.createElement('a');
        link.href = imagePath;
        link.download = imagePath.split('/').pop(); // Use the filename from the path
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    });
}

function bindMemoryCardScroll() {
    const picsContainer = document.querySelector(".memory-pics-container");
    const dataContainer = document.querySelector(".modal-right-column");
    const picCards = document.querySelectorAll(".memory-pic-card");
    const dataCards = document.querySelectorAll(".memory-data-card");
    if (!picsContainer || picCards.length === 0 || dataCards.length === 0) return;

    const shouldIgnoreClick = (target) => {
        return !!target.closest("button, a, input, textarea, select, video, audio, .pic-reaction-trigger, .video-reaction-trigger, .pic-reactions, .video-reactions");
    };

    dataCards.forEach(card => {
        if (card.dataset.bound === "true") return;
        card.dataset.bound = "true";
        card.style.cursor = "pointer";
        card.addEventListener("click", (e) => {
            if (shouldIgnoreClick(e.target)) return;
            const index = card.getAttribute("data-index");
            const target = document.querySelector(`.memory-pic-card[data-index="${index}"]`);
            if (!target) return;
            target.scrollIntoView({ behavior: "smooth", block: "center" });
            card.scrollIntoView({ behavior: "smooth", block: "center" });
        });
    });

    picCards.forEach(card => {
        if (card.dataset.bound === "true") return;
        card.dataset.bound = "true";
        card.style.cursor = "pointer";
        card.addEventListener("click", (e) => {
            if (shouldIgnoreClick(e.target)) return;
            const index = card.getAttribute("data-index");
            const target = document.querySelector(`.memory-data-card[data-index="${index}"]`);
            if (target) {
                target.scrollIntoView({ behavior: "smooth", block: "center" });
            }
            card.scrollIntoView({ behavior: "smooth", block: "center" });
        });
    });
}

function initModalStars() {
    const modalStarLayer = document.querySelector(".modal-stars");
    if (!modalStarLayer) return;
    if (modalStarLayer.dataset.ready === "true") return;
    modalStarLayer.dataset.ready = "true";
    const count = 45;
    for (let i = 0; i < count; i++) {
        const star = document.createElement("span");
        star.className = "modal-star";
        const size = 4 + Math.random() * 6;
        star.style.width = `${size}px`;
        star.style.height = `${size}px`;
        star.style.left = `${Math.random() * 100}%`;
        star.style.top = `${Math.random() * 100}%`;
        star.style.animationDelay = `${Math.random() * 4}s`;
        star.style.animationDuration = `${6 + Math.random() * 6}s`;
        modalStarLayer.appendChild(star);
    }

    const emitCluster = () => {
        const baseLeft = 15 + Math.random() * 70;
        const baseTop = 20 + Math.random() * 60;
        const count = 6 + Math.floor(Math.random() * 6);
        for (let i = 0; i < count; i++) {
            const spark = document.createElement("span");
            spark.className = "modal-spark";
            const offsetX = (Math.random() * 2 - 1) * 18;
            const offsetY = (Math.random() * 2 - 1) * 18;
            const size = 3 + Math.random() * 5;
            spark.style.left = `calc(${baseLeft}% + ${offsetX}px)`;
            spark.style.top = `calc(${baseTop}% + ${offsetY}px)`;
            spark.style.width = `${size}px`;
            spark.style.height = `${size}px`;
            modalStarLayer.appendChild(spark);
            spark.addEventListener("animationend", () => spark.remove());
        }
    };

    setInterval(() => {
        emitCluster();
    }, 1200);

}

let confirmCallback = null;

function openConfirm(message, onConfirm) {
    const modal = document.getElementById("confirmModal");
    const messageEl = document.getElementById("confirmMessage");
    const yesBtn = document.getElementById("confirmYes");
    const noBtn = document.getElementById("confirmNo");
    if (!modal || !messageEl || !yesBtn || !noBtn) {
        if (confirm(message)) onConfirm();
        return;
    }
    confirmCallback = onConfirm;
    messageEl.innerText = message;
    modal.style.display = "flex";
    modal.classList.add("show");
    document.body.classList.add("modal-open");
    yesBtn.focus();
}

function closeConfirm() {
    const modal = document.getElementById("confirmModal");
    if (!modal) return;
    modal.classList.remove("show");
    modal.style.display = "none";
    document.body.classList.remove("modal-open");
    confirmCallback = null;
}

document.addEventListener("click", (e) => {
    if (e.target && e.target.id === "confirmYes") {
        if (typeof confirmCallback === "function") confirmCallback();
        closeConfirm();
        return;
    }
    if (e.target && e.target.id === "confirmNo") {
        closeConfirm();
        return;
    }
    if (e.target && e.target.id === "confirmModal") {
        closeConfirm();
    }
});

function showAddMemoryConfirmation(date){
    const modalContent = `
        <div style="text-align: center; padding: 40px 20px;">
            <h3 style="color: #ff1493; margin-bottom: 20px;">Do you want to add memories?</h3>
            <p style="margin-bottom: 30px; color: #666;">No memories found for ${date}</p>
            <div style="display: flex; gap: 15px; justify-content: center;">
                <button onclick="confirmAddMemory('${date}')" style="background: #ff1493; color: white; border: none; padding: 12px 30px; border-radius: 8px; cursor: pointer; font-size: 16px;">Yes</button>
                <button onclick="cancelAddMemory()" style="background: #e0e0e0; color: #666; border: none; padding: 12px 30px; border-radius: 8px; cursor: pointer; font-size: 16px;">No</button>
            </div>
        </div>
    `;
    
    document.getElementById("modalTitle").innerText = `${date}`;
    document.getElementById("modalMemories").innerHTML = modalContent;
    document.getElementById("memoryModal").style.display = "flex";
    document.getElementById("memoryModal").classList.add("show");
    document.body.classList.add("modal-open");
    initModalStars();
    document.body.classList.add("modal-open");
    // Hide delete button when there are no memories
    document.getElementById("deleteMemoriesBtn").style.display = "none";
}

function confirmAddMemory(date){
    closeModal();
    // Display the selected date and fill the form
    document.getElementById("selectedDate").innerText = date;
    document.getElementById("memoryDateInput").value = date;
    document.getElementById("mediaInput").focus();
}

function cancelAddMemory(){
    closeModal();
    // Clear the selected date display
    document.getElementById("selectedDate").innerText = "None";
    document.getElementById("memoryDateInput").value = "";
    // Hide delete button when closing add memory confirmation
    document.getElementById("deleteMemoriesBtn").style.display = "none";
}

function closeModal(){
    const modal = document.getElementById("memoryModal");
    modal.classList.remove("show");
    modal.classList.remove("show");
    modal.style.display = "none";
    document.body.classList.remove("modal-open");
    applySelectionMode(false);
    document.getElementById("modalMemories").innerHTML = "";
    const modalContent = modal.querySelector(".modal-content");
    if (modalContent) modalContent.classList.remove("delete-confirmation");
    // Hide delete button when modal is closed
    document.getElementById("deleteMemoriesBtn").style.display = "none";
    editingMemoryId = null;
    originalMessage = "";
}

function bindSelectionControls() {
    // selection delete removed
}

// selection delete removed

function bindVideoReactions() {
    const triggers = document.querySelectorAll(".video-reaction-trigger");
    triggers.forEach(tr => {
        if (tr.dataset.bound === "true") return;
        tr.dataset.bound = "true";
        tr.addEventListener("click", (e) => {
            e.stopPropagation();
            const underlay = tr.closest(".video-underlay");
            if (!underlay) return;
            const isOpen = underlay.classList.contains("react-open");
            document.querySelectorAll(".video-underlay.react-open").forEach(u => u.classList.remove("react-open"));
            if (!isOpen) underlay.classList.add("react-open");
        });
    });

    const buttons = document.querySelectorAll(".video-react-btn");
    buttons.forEach(btn => {
        if (btn.dataset.bound === "true") return;
        btn.dataset.bound = "true";
        btn.addEventListener("click", (e) => {
            e.stopPropagation();
            const group = btn.closest(".video-reactions");
            if (!group) return;

            const underlay = btn.closest(".video-underlay");
            const trigger = underlay ? underlay.querySelector(".video-reaction-trigger") : null;
            const current = trigger ? trigger.textContent.trim() : "";
            const clicked = btn.textContent;
            const isSame = current === clicked;

            group.querySelectorAll(".video-react-btn").forEach(b => b.classList.remove("active"));
            if (!isSame) btn.classList.add("active");
            if (trigger) {
                trigger.textContent = isSame ? "React" : clicked;
                trigger.classList.remove("pop");
                void trigger.offsetWidth;
                trigger.classList.add("pop");
            }
            if (underlay) underlay.classList.remove("react-open");

            const picCard = btn.closest('.memory-pic-card');
            const index = picCard ? picCard.getAttribute('data-index') : null;
            const dataCard = index !== null ? document.querySelector(`.memory-data-card[data-index="${index}"]`) : null;
            const messageEl = dataCard ? dataCard.querySelector('.memory-message') : null;
            if (messageEl) {
                const memoryId = messageEl.id.replace('message-', '');
                const reaction = isSame ? "" : clicked;
                messageEl.dataset.reaction = reaction;
                fetch('save_reaction.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `id=${encodeURIComponent(memoryId)}&reaction=${encodeURIComponent(reaction)}`
                })
                .then(res => res.json())
                .then(data => {
                    if (!data.success) {
                        alert('Error saving reaction: ' + (data.error || 'Unknown error'));
                    } else if (data.reaction) {
                        messageEl.dataset.reaction = data.reaction;
                        const under = btn.closest(".video-underlay");
                        const trig = under ? under.querySelector(".video-reaction-trigger") : null;
                        if (trig) {
                            trig.textContent = data.reaction;
                            trig.classList.remove("pop");
                            void trig.offsetWidth;
                            trig.classList.add("pop");
                        }
                        updateDayReactionForDate(messageEl.dataset.date, data.reaction);
                    } else {
                        messageEl.dataset.reaction = "";
                        const under = btn.closest(".video-underlay");
                        const trig = under ? under.querySelector(".video-reaction-trigger") : null;
                        if (trig) {
                            trig.textContent = "React";
                            trig.classList.remove("pop");
                            void trig.offsetWidth;
                            trig.classList.add("pop");
                        }
                        updateDayReactionForDate(messageEl.dataset.date, "");
                    }
                })
                .catch(err => {
                    alert('Error saving reaction: ' + err);
                });
            }
        });
    });

    document.addEventListener("click", () => {
        document.querySelectorAll(".video-underlay.react-open").forEach(u => u.classList.remove("react-open"));
    });
}

function bindPicReactions() {
    const triggers = document.querySelectorAll(".pic-reaction-trigger");
    triggers.forEach(tr => {
        if (tr.dataset.bound === "true") return;
        tr.dataset.bound = "true";
        tr.addEventListener("click", (e) => {
            e.stopPropagation();
            const wrap = tr.closest(".memory-image-wrap");
            if (!wrap) return;
            const isOpen = wrap.classList.contains("react-open");
            document.querySelectorAll(".memory-image-wrap.react-open").forEach(w => w.classList.remove("react-open"));
            if (!isOpen) wrap.classList.add("react-open");
        });
    });

    const buttons = document.querySelectorAll(".pic-react-btn");
    buttons.forEach(btn => {
        if (btn.dataset.bound === "true") return;
        btn.dataset.bound = "true";
        btn.addEventListener("click", (e) => {
            e.stopPropagation();
            const wrap = btn.closest(".memory-image-wrap");
            if (!wrap) return;
            const trigger = wrap.querySelector(".pic-reaction-trigger");
            const current = trigger ? trigger.textContent.trim() : "";
            const clicked = btn.textContent;
            const isSame = current === clicked;

            wrap.querySelectorAll(".pic-react-btn").forEach(b => b.classList.remove("active"));
            if (!isSame) btn.classList.add("active");
            if (trigger) {
                trigger.textContent = isSame ? "React" : clicked;
                trigger.classList.remove("pop");
                void trigger.offsetWidth;
                trigger.classList.add("pop");
            }
            wrap.classList.remove("react-open");

            const picCard = btn.closest('.memory-pic-card');
            const index = picCard ? picCard.getAttribute('data-index') : null;
            const dataCard = index !== null ? document.querySelector(`.memory-data-card[data-index="${index}"]`) : null;
            const messageEl = dataCard ? dataCard.querySelector('.memory-message') : null;
            if (messageEl) {
                const memoryId = messageEl.id.replace('message-', '');
                const reaction = isSame ? "" : clicked;
                messageEl.dataset.reaction = reaction;
                fetch('save_reaction.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `id=${encodeURIComponent(memoryId)}&reaction=${encodeURIComponent(reaction)}`
                })
                .then(res => res.json())
                .then(data => {
                    if (!data.success) {
                        alert('Error saving reaction: ' + (data.error || 'Unknown error'));
                    } else if (data.reaction) {
                        messageEl.dataset.reaction = data.reaction;
                        if (trigger) trigger.textContent = data.reaction;
                        if (trigger) {
                            trigger.classList.remove("pop");
                            void trigger.offsetWidth;
                            trigger.classList.add("pop");
                        }
                        updateDayReactionForDate(messageEl.dataset.date, data.reaction);
                    } else {
                        messageEl.dataset.reaction = "";
                        if (trigger) trigger.textContent = "React";
                        if (trigger) {
                            trigger.classList.remove("pop");
                            void trigger.offsetWidth;
                            trigger.classList.add("pop");
                        }
                        updateDayReactionForDate(messageEl.dataset.date, "");
                    }
                })
                .catch(err => {
                    alert('Error saving reaction: ' + err);
                });
            }
        });
    });
}


// DELETE FUNCTIONALITY
let currentDeleteDate = "";

function showDeleteConfirmation() {
    const date = currentModalDateRaw || "";
    currentDeleteDate = date;
    
    const modalContent = `
        <div style="text-align: center; padding: 40px 20px;">
            <h3 style="color: #ff1493; margin-bottom: 20px;">Delete All Memories</h3>
            <p style="margin-bottom: 30px; color: #666;">Are you sure you want to delete all memories for <strong>${formatDateLong(date)}</strong>?</p>
            <p style="margin-bottom: 30px; color: #ff1493; font-weight: 600;">This action cannot be undone!</p>
            <div style="display: flex; gap: 15px; justify-content: center;">
                <button onclick="confirmDelete()" style="background: #ff1493; color: white; border: none; padding: 12px 30px; border-radius: 8px; cursor: pointer; font-size: 16px;">Yes, Delete</button>
                <button onclick="cancelDelete()" style="background: #e0e0e0; color: #666; border: none; padding: 12px 30px; border-radius: 8px; cursor: pointer; font-size: 16px;">No, Cancel</button>
            </div>
        </div>
    `;
    
    document.getElementById("modalTitle").innerText = `Delete Memories for ${formatDateLong(date)}`;
    document.getElementById("modalMemories").innerHTML = modalContent;
    document.getElementById("deleteMemoriesBtn").style.display = "none";
    const modalEl = document.getElementById("memoryModal");
    const modalContentEl = modalEl ? modalEl.querySelector(".modal-content") : null;
    if (modalContentEl) modalContentEl.classList.add("delete-confirmation");
}

function confirmDelete() {
    fetch("delete_memories.php?date=" + encodeURIComponent(currentDeleteDate))
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            alert("Memories deleted successfully!");
            closeModal();
            // Refresh calendar highlights to remove deleted date indicators
            highlightMemoryDates();
        } else {
            alert("Error deleting memories: " + data.error);
        }
    })
    .catch(error => {
        alert("Error: " + error);
    });
}

function cancelDelete() {
    // Reload the original memories for this date
    fetchMemories(currentDeleteDate, currentReactionFilter);
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById("memoryModal");
    if (event.target == modal) {
        closeModal();
    }
}

// Update file label when file is selected
document.getElementById("mediaInput").addEventListener("change", function() {
    const file = this.files[0];
    const fileLabelSpan = document.getElementById("fileLabel");
    const imagePreview = document.getElementById("imagePreview");
    const fileContent = document.querySelector(".file-input-content");
    const fileLabelDiv = document.querySelector(".file-input-label");
    
    if (file) {
        // Show filename
        fileLabelSpan.textContent = file.name.length > 20 
            ? file.name.substring(0, 20) + "..." 
            : file.name;

        if (file.type && file.type.startsWith("video/")) {
            // For video, just show filename (no image preview)
            imagePreview.style.display = "none";
            fileContent.classList.remove("hidden");
            fileLabelDiv.style.minHeight = "80px";
            fileLabelDiv.style.maxHeight = "";
        } else {
            // Show image preview
            const reader = new FileReader();
            reader.onload = function(e) {
                imagePreview.src = e.target.result;
                imagePreview.style.display = "block";
                fileContent.classList.add("hidden");
                
                // Adjust height based on image aspect ratio
                imagePreview.onload = function() {
                    const img = imagePreview;
                    const aspectRatio = img.naturalWidth / img.naturalHeight;
                    
                    if (aspectRatio > 1.5) {
                        // Wide image - make it taller but within limits
                        fileLabelDiv.style.minHeight = "120px";
                        fileLabelDiv.style.maxHeight = "180px";
                    } else if (aspectRatio < 0.7) {
                        // Tall image - make it taller but within limits
                        fileLabelDiv.style.minHeight = "150px";
                        fileLabelDiv.style.maxHeight = "180px";
                    } else {
                        // Normal image
                        fileLabelDiv.style.minHeight = "100px";
                        fileLabelDiv.style.maxHeight = "150px";
                    }
                };
            };
            reader.readAsDataURL(file);
        }
    } else {
        // Reset to original state
        fileLabelSpan.textContent = "Choose Photo or Video";
        imagePreview.style.display = "none";
        fileContent.classList.remove("hidden");
        fileLabelDiv.style.minHeight = "80px";
    }
});

// Update audio label when file is selected
document.getElementById("audioInput").addEventListener("change", function() {
    const file = this.files[0];
    const audioLabelSpan = document.getElementById("audioLabel");
    if (file) {
        audioLabelSpan.textContent = file.name.length > 20
            ? file.name.substring(0, 20) + "..."
            : file.name;
    } else {
        audioLabelSpan.textContent = "Choose Music";
    }
});

// Update video label when file is selected
// Video handled by media input

// EMOJI PICKER FUNCTIONALITY
const emojiBtn = document.getElementById("emojiBtn");
const emojiPicker = document.getElementById("emojiPicker");
const messageTextarea = document.getElementById("messageTextarea");

// Toggle emoji picker
emojiBtn.addEventListener("click", function(e) {
    e.preventDefault();
    emojiPicker.classList.toggle("show");
});

// delete selected button removed

// Add emoji to textarea when clicked
document.querySelectorAll(".emoji").forEach(emoji => {
    emoji.addEventListener("click", function(e) {
        e.stopPropagation();
        const emojiText = this.textContent;
        const cursorPos = messageTextarea.selectionStart;
        const textBefore = messageTextarea.value.substring(0, cursorPos);
        const textAfter = messageTextarea.value.substring(cursorPos);
        
        messageTextarea.value = textBefore + emojiText + textAfter;
        messageTextarea.focus();
        messageTextarea.setSelectionRange(cursorPos + emojiText.length, cursorPos + emojiText.length);
        
        // Keep picker open for multiple selections
        // emojiPicker.classList.remove("show");
    });
});

// Close emoji picker when clicking outside
document.addEventListener("click", function(e) {
    if (!emojiBtn.contains(e.target) && !emojiPicker.contains(e.target)) {
        emojiPicker.classList.remove("show");
    }
});

// CUSTOM TAG FUNCTIONALITY
const tagSelect = document.getElementById("tag");
const customTagContainer = document.getElementById("customTagContainer");
const customTagInput = document.getElementById("customTagInput");

// Show/hide custom tag input based on selection
tagSelect.addEventListener("change", function() {
    if (this.value === "custom") {
        customTagContainer.style.display = "block";
        customTagInput.focus();
    } else {
        customTagContainer.style.display = "none";
        customTagInput.value = "";
    }
});

// Handle form submission with custom tag
const form = document.querySelector("form");
form.addEventListener("submit", function(e) {
    const tagValue = tagSelect.value;
    if (tagValue === "custom" && customTagInput.value.trim() === "") {
        e.preventDefault();
        alert("Please enter a custom tag name");
        customTagInput.focus();
        return;
    }
    
    // If custom tag is selected, set the tag value to the custom input
    if (tagValue === "custom") {
        const hiddenTagInput = document.createElement("input");
        hiddenTagInput.type = "hidden";
        hiddenTagInput.name = "tag";
        hiddenTagInput.value = customTagInput.value.trim();
        form.appendChild(hiddenTagInput);
    }
});

// Cursor sparkle effect
(() => {
    const layer = document.getElementById("cursorSparkleLayer");
    if (!layer) return;
    const prefersReducedMotion = window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches;
    const canHover = window.matchMedia && window.matchMedia("(hover: hover)").matches;
    if (prefersReducedMotion || !canHover) return;

    let lastTime = 0;
    const cooldownMs = 22;
    const maxSparks = 80;
    let activeSparks = 0;

    function spawnSpark(x, y) {
        if (activeSparks >= maxSparks) return;
        activeSparks++;

        const spark = document.createElement("span");
        spark.className = "cursor-sparkle";
        const size = 4 + Math.random() * 6;
        const rotate = Math.random() * 360;
        const driftX = (Math.random() * 2 - 1) * 14;
        const driftY = (Math.random() * 2 - 1) * 14 - 6;
        spark.style.width = `${size}px`;
        spark.style.height = `${size}px`;
        spark.style.transform = `translate(-50%, -50%) rotate(${rotate}deg)`;
        spark.style.left = `${x}px`;
        spark.style.top = `${y}px`;
        spark.style.setProperty("--spark-dx", `${driftX}px`);
        spark.style.setProperty("--spark-dy", `${driftY}px`);

        spark.addEventListener("animationend", () => {
            spark.remove();
            activeSparks = Math.max(0, activeSparks - 1);
        });

        layer.appendChild(spark);
    }

    window.addEventListener("pointermove", (e) => {
        if (e.pointerType && e.pointerType !== "mouse") return;
        const now = performance.now();
        if (now - lastTime < cooldownMs) return;
        lastTime = now;
        spawnSpark(e.clientX, e.clientY);
    });
})();

// Floating background sparks
(() => {
    const layer = document.getElementById("floatingSparkLayer");
    if (!layer) return;
    const prefersReducedMotion = window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches;
    if (prefersReducedMotion) return;

    const starCount = 80;
    const constellationCount = 3;

    for (let i = 0; i < starCount; i++) {
        const star = document.createElement("span");
        star.className = "floating-spark star-spark";
        const size = 2 + Math.random() * 4;
        const left = Math.random() * 100;
        const top = Math.random() * 100;
        const delay = Math.random() * 8;
        const duration = 4 + Math.random() * 6;
        const hues = [330, 315, 200, 140, 30, 350];
        const hue = hues[Math.floor(Math.random() * hues.length)];

        star.style.width = `${size}px`;
        star.style.height = `${size}px`;
        star.style.left = `${left}%`;
        star.style.top = `${top}%`;
        star.style.setProperty("--spark-delay", `${delay}s`);
        star.style.setProperty("--spark-duration", `${duration}s`);
        star.style.setProperty("--spark-hue", `${hue}`);
        layer.appendChild(star);
    }

    layer.addEventListener("pointerover", (e) => {
        const star = e.target.closest(".star-spark");
        if (!star) return;
        if (star.dataset.jumping === "true") return;
        star.dataset.jumping = "true";
        star.style.transitionDuration = "0.15s";
        const left = Math.random() * 100;
        const top = Math.random() * 100;
        star.style.left = `${left}%`;
        star.style.top = `${top}%`;
        setTimeout(() => {
            star.dataset.jumping = "false";
            star.style.transitionDuration = "";
        }, 200);
    });

    // Slow roaming movement for stars (galaxy-like)
    setInterval(() => {
        const stars = layer.querySelectorAll(".star-spark");
        if (stars.length === 0) return;
        const moves = Math.min(6, stars.length);
        for (let i = 0; i < moves; i++) {
            const star = stars[Math.floor(Math.random() * stars.length)];
            if (!star) continue;
            const left = Math.random() * 100;
            const top = Math.random() * 100;
            star.style.left = `${left}%`;
            star.style.top = `${top}%`;
        }
    }, 4200);

    for (let i = 0; i < constellationCount; i++) {
        const constellation = document.createElement("div");
        constellation.className = "constellation";
        const left = 10 + Math.random() * 80;
        const top = 10 + Math.random() * 60;
        const scale = 0.6 + Math.random() * 0.6;
        constellation.style.left = `${left}%`;
        constellation.style.top = `${top}%`;
        constellation.style.transform = `scale(${scale})`;

        const points = 4 + Math.floor(Math.random() * 3);
        for (let p = 0; p < points; p++) {
            const dot = document.createElement("span");
            dot.className = "constellation-star";
            dot.style.left = `${Math.random() * 120}px`;
            dot.style.top = `${Math.random() * 80}px`;
            constellation.appendChild(dot);
        }

        for (let l = 0; l < points - 1; l++) {
            const line = document.createElement("span");
            line.className = "constellation-line";
            line.style.left = `${Math.random() * 120}px`;
            line.style.top = `${Math.random() * 80}px`;
            line.style.width = `${40 + Math.random() * 70}px`;
            line.style.transform = `rotate(${Math.random() * 60 - 30}deg)`;
            constellation.appendChild(line);
        }

        layer.appendChild(constellation);
    }

})();

// Logout modal
(() => {
    const logoutBtn = document.getElementById("logoutBtn");
    const logoutModal = document.getElementById("logoutModal");
    const logoutClose = document.getElementById("logoutCloseBtn");
    const logoutCancel = document.getElementById("logoutCancelBtn");
    if (!logoutBtn || !logoutModal) return;

    const openModal = (e) => {
        if (e) e.preventDefault();
        logoutModal.style.display = "flex";
        logoutModal.classList.add("show");
        document.body.classList.add("modal-open");
    };
    const closeModal = () => {
        logoutModal.classList.remove("show");
        logoutModal.style.display = "none";
        document.body.classList.remove("modal-open");
    };

    logoutBtn.addEventListener("click", openModal);
    if (logoutClose) logoutClose.addEventListener("click", closeModal);
    if (logoutCancel) logoutCancel.addEventListener("click", closeModal);
    logoutModal.addEventListener("click", (e) => {
        if (e.target === logoutModal) closeModal();
    });
})();

// Top bar stars
(() => {
    const layer = document.querySelector(".topbar-stars");
    if (!layer) return;
    const count = 16;
    for (let i = 0; i < count; i++) {
        const star = document.createElement("span");
        star.className = "topbar-star";
        const size = 2 + Math.random() * 3;
        star.style.width = `${size}px`;
        star.style.height = `${size}px`;
        star.style.left = `${Math.random() * 100}%`;
        star.style.top = `${Math.random() * 100}%`;
        star.style.animationDelay = `${Math.random() * 4}s`;
        star.style.animationDuration = `${6 + Math.random() * 6}s`;
        layer.appendChild(star);
    }
})();
</script>

</body>
</html>






