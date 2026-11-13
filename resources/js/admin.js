/* =====================================================
SIDEBAR
===================================================== */
const sidebar = document.getElementById("sidebar");
const sidebarToggle = document.getElementById("sidebarToggle");
const sidebarClose = document.getElementById("sidebarClose");
const sidebarOverlay = document.getElementById("sidebarOverlay");

if (sidebarToggle) {
    sidebarToggle.addEventListener("click", function () {
        sidebar.classList.add("open");
        sidebarOverlay.classList.add("show");
    });
}

if (sidebarClose) {
    sidebarClose.addEventListener("click", function () {
        sidebar.classList.remove("open");
        sidebarOverlay.classList.remove("show");
    });
}

if (sidebarOverlay) {
    sidebarOverlay.addEventListener("click", function () {
        sidebar.classList.remove("open");
        sidebarOverlay.classList.remove("show");
    });
}

/* =====================================================
   DARK MODE & CHARTS INITIALIZATION
===================================================== */
const themeToggle = document.getElementById("themeToggle");
const themeIcon = document.getElementById("themeIcon");
let lineChart, donutChart;

// Fungsi untuk mendapatkan warna berdasarkan tema saat ini
function getChartColors() {
    const isDark = document.body.classList.contains("dark");
    return {
        textColor: isDark ? "#9ca3af" : "#6b7280",
        gridColor: isDark ? "#293241" : "#e5e7eb",
        regulerLine: isDark ? "#d5d9e2" : "#172033",
        eventLine: "#f4c430", // Gold
        success: "#009432",
        warning: "#f4c430",
        danger: "#d71920",
        donutBg: isDark ? "#18202d" : "#ffffff",
    };
}

// Inisialisasi Chart.js
// SIGORAY_DASHBOARD_BINDINGS_V1: data dibaca dari Blade; halaman lain aman.
function initCharts() {
    const dashboard = window.SIGORAY_DASHBOARD || {};
    const booking = dashboard.booking || {};
    const payment = dashboard.payment || {};
    const colors = getChartColors();

    // 1. Line Chart (Grafik Pemesanan)
    const ctxLine = document.getElementById("bookingChart");
    if (ctxLine) {
        lineChart = new Chart(ctxLine, {
            type: "line",
            data: {
                labels: booking.labels || [
                    "Jan",
                    "Feb",
                    "Mar",
                    "Apr",
                    "Mei",
                    "Jun",
                    "Jul",
                    "Agu",
                    "Sep",
                    "Okt",
                    "Nov",
                    "Des",
                ],
                datasets: [
                    {
                        label: "Reguler",
                        data: booking.regular || Array(12).fill(0),
                        borderColor: colors.regulerLine,
                        backgroundColor: colors.regulerLine,
                        tension: 0.4,
                        borderWidth: 2,
                        pointRadius: 4,
                        pointBackgroundColor: colors.regulerLine,
                    },
                    {
                        label: "Event",
                        data: booking.event || Array(12).fill(0),
                        borderColor: colors.eventLine,
                        backgroundColor: colors.eventLine,
                        tension: 0.4,
                        borderWidth: 2,
                        pointRadius: 4,
                        pointBackgroundColor: colors.eventLine,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }, // Sembunyikan legend bawaan, pakai custom HTML
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grace: "10%",
                        grid: { color: colors.gridColor },
                        ticks: { color: colors.textColor },
                    },
                    x: {
                        grid: { display: false },
                        ticks: { color: colors.textColor },
                    },
                },
            },
        });
    }

    // 2. Donut Chart (Status Pembayaran)
    const ctxDonut = document.getElementById("paymentChart");
    if (ctxDonut) {
        donutChart = new Chart(ctxDonut, {
            type: "doughnut",
            data: {
                labels: ["Selesai", "Menunggu", "Ditolak"],
                datasets: [
                    {
                        data: [
                            payment.success || 0,
                            payment.pending || 0,
                            payment.failed || 0,
                        ],
                        backgroundColor: [
                            colors.success,
                            colors.warning,
                            colors.danger,
                        ],
                        borderWidth: 4,
                        borderColor: colors.donutBg,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: "70%",
                plugins: {
                    legend: { display: false },
                },
            },
        });
    }
}

// Update konfigurasi warna chart saat tema berganti
function updateChartsTheme() {
    if (!lineChart || !donutChart) return;
    const colors = getChartColors();

    // Update Line Chart
    lineChart.data.datasets[0].borderColor = colors.regulerLine;
    lineChart.data.datasets[0].backgroundColor = colors.regulerLine;
    lineChart.data.datasets[0].pointBackgroundColor = colors.regulerLine;
    lineChart.options.scales.y.grid.color = colors.gridColor;
    lineChart.options.scales.y.ticks.color = colors.textColor;
    lineChart.options.scales.x.ticks.color = colors.textColor;
    lineChart.update();

    // Update Donut Chart
    donutChart.data.datasets[0].borderColor = colors.donutBg;
    donutChart.update();
}

// Cek Local Storage untuk Theme saat pertama load
const savedTheme = localStorage.getItem("sigoray-theme");
if (savedTheme === "dark") {
    document.body.classList.add("dark");
    if (themeIcon) themeIcon.textContent = "light_mode";
}

// Jalankan Chart setelah DOM siap
document.addEventListener("DOMContentLoaded", initCharts);

// Toggle Event
if (themeToggle) {
    themeToggle.addEventListener("click", function () {
        document.body.classList.toggle("dark");
        const isDark = document.body.classList.contains("dark");

        if (isDark) {
            localStorage.setItem("sigoray-theme", "dark");
            themeIcon.textContent = "light_mode";
        } else {
            localStorage.setItem("sigoray-theme", "light");
            themeIcon.textContent = "dark_mode";
        }

        // Panggil fungsi update chart agar warnanya selaras
        updateChartsTheme();
    });
}

/* TIKET REGULER — popup detail dan navigasi halaman Atur Slot */
/* =====================================================
   TIKET REGULER — TAHAP 1: POPUP DETAIL SLOT
   Ganti blok JavaScript Tiket Reguler lama dengan blok ini.
===================================================== */
document.addEventListener("DOMContentLoaded", () => {
    const calendarGrid = document.querySelector(".regular-calendar-grid");
    const modal = document.getElementById("regularDetailModal");

    if (!calendarGrid || !modal) return;

    const dateLabel = document.getElementById("regularDetailDate");
    const sessionCount = document.getElementById("regularDetailSessionCount");
    const quotaTotal = document.getElementById("regularDetailQuota");
    const usedTotal = document.getElementById("regularDetailUsed");
    const percentageLabel = document.getElementById("regularDetailPercentage");
    const progressBar = document.getElementById("regularDetailProgressBar");
    const remainingLabel = document.getElementById("regularDetailRemaining");
    const sessionList = document.getElementById("regularDetailSessionList");
    const eventWarning = document.getElementById("regularDetailEventWarning");
    const eventSection = document.getElementById("regularDetailEventSection");
    const eventList = document.getElementById("regularDetailEventList");
    const configureButton = document.getElementById("regularDetailConfigure");
    const closeButtons = [
        document.getElementById("closeRegularDetailModal"),
        document.getElementById("closeRegularDetailFooter"),
    ].filter(Boolean);

    const numberFormatter = new Intl.NumberFormat("id-ID");
    let lastTrigger = null;
    let activeDate = null;

    const toNumber = (value) => {
        const parsed = Number(value);
        return Number.isFinite(parsed) ? parsed : 0;
    };

    const parseArray = (value, attributeName) => {
        try {
            const parsed = JSON.parse(value || "[]");
            return Array.isArray(parsed) ? parsed : [];
        } catch (error) {
            console.error(`Gagal membaca ${attributeName} kalender:`, error);
            return [];
        }
    };

    const makeElement = (tag, className, text) => {
        const element = document.createElement(tag);
        if (className) element.className = className;
        if (text !== undefined && text !== null)
            element.textContent = String(text);
        return element;
    };

    const getSessionStatus = (session) => {
        if ((session.status || "active") === "inactive") {
            return { label: "Nonaktif", className: "is-inactive" };
        }

        const quota = toNumber(session.quota);
        const used = toNumber(session.used);
        const remaining = Math.max(quota - used, 0);

        if (remaining <= 0) return { label: "Habis", className: "is-full" };
        if (quota > 0 && used / quota >= 0.8) {
            return { label: "Hampir habis", className: "is-warning" };
        }
        return { label: "Aktif", className: "is-active" };
    };

    const renderSessions = (sessions) => {
        sessionList.replaceChildren();

        if (sessions.length === 0) {
            sessionList.appendChild(
                makeElement(
                    "div",
                    "regular-detail-empty",
                    "Belum ada sesi reguler yang dikonfigurasi pada tanggal ini.",
                ),
            );
            return;
        }

        // Pertahankan urutan session_number (urutan penambahan), bukan urutan jam.
        const orderedSessions = [...sessions].sort(
            (a, b) => toNumber(a.session_number) - toNumber(b.session_number),
        );

        orderedSessions.forEach((session, index) => {
            const quota = toNumber(session.quota);
            const used = toNumber(session.used);
            const remaining = Math.max(quota - used, 0);
            const status = getSessionStatus(session);
            const card = makeElement(
                "article",
                `regular-detail-session-item ${status.className}`,
            );
            const info = makeElement("div", "regular-detail-session-info");
            const heading = makeElement(
                "div",
                "regular-detail-session-heading",
            );
            const title = makeElement(
                "strong",
                "",
                `Sesi ${session.session_number ?? index + 1}`,
            );
            const badge = makeElement(
                "span",
                `regular-detail-status ${status.className}`,
                status.label,
            );
            const time = makeElement(
                "span",
                "regular-detail-session-time",
                `${session.start_time || "--:--"}–${session.end_time || "--:--"} WIB`,
            );
            const stats = makeElement(
                "small",
                "regular-detail-session-stats",
                `Terjual ${numberFormatter.format(used)} · Kuota ${numberFormatter.format(quota)} · Tersisa ${numberFormatter.format(remaining)}`,
            );

            heading.append(title, badge);
            info.append(heading, time, stats);
            card.appendChild(info);
            sessionList.appendChild(card);
        });
    };

    const appendEventInfo = (container, label, value) => {
        const item = makeElement("div", "regular-detail-event-info");
        item.append(
            makeElement("span", "regular-detail-event-label", label),
            makeElement(
                "strong",
                "regular-detail-event-value",
                value !== undefined && value !== null && value !== ""
                    ? value
                    : "Belum tersedia",
            ),
        );
        container.appendChild(item);
    };

    const renderEvents = (events) => {
        eventList.replaceChildren();
        eventSection.hidden = events.length === 0;
        eventWarning.hidden = events.length === 0;

        events.forEach((event, index) => {
            const card = makeElement("article", "regular-detail-event-card");
            const name =
                event.nama_event ||
                event.name ||
                event.judul ||
                `Event ${index + 1}`;
            card.appendChild(makeElement("h4", "", name));

            if (event.deskripsi || event.description) {
                card.appendChild(
                    makeElement(
                        "p",
                        "regular-detail-event-description",
                        event.deskripsi || event.description,
                    ),
                );
            }

            const grid = makeElement("div", "regular-detail-event-grid");
            const start = event.jam_mulai || event.start_time;
            const end = event.jam_selesai || event.end_time;
            appendEventInfo(
                grid,
                "Waktu pelaksanaan",
                start
                    ? `${start} – ${end || "Belum tersedia"}`
                    : "Belum tersedia",
            );

            const tariff = event.tariff;
            appendEventInfo(
                grid,
                "Tarif event",
                tariff !== undefined && tariff !== null && tariff !== ""
                    ? `Rp ${numberFormatter.format(toNumber(tariff))}`
                    : "Belum diatur",
            );
            appendEventInfo(grid, "Kuota event", event.kuota ?? event.quota);
            appendEventInfo(grid, "Tiket terjual", event.terjual ?? event.sold);

            const status =
                event.status_label || event.status || "Status belum tersedia";
            card.append(
                grid,
                makeElement("span", "regular-detail-event-status", status),
            );
            eventList.appendChild(card);
        });
    };

    const openModal = (trigger) => {
        const date = trigger.dataset.date;
        if (!date) return;

        const slots = parseArray(trigger.dataset.slots, "data-slots");
        const events = parseArray(trigger.dataset.events, "data-events");
        const hasEvent = trigger.dataset.hasEvent === "1" || events.length > 0;
        activeDate = date;

        if (configureButton) {
            configureButton.disabled = hasEvent;
            configureButton.title = hasEvent
                ? "Tanggal Event tidak menyediakan pengaturan tiket reguler."
                : "Buka halaman pengaturan sesi dan tarif untuk tanggal ini.";
        }

        const totalQuota = slots.reduce(
            (sum, item) => sum + toNumber(item.quota),
            0,
        );
        const totalUsed = slots.reduce(
            (sum, item) => sum + toNumber(item.used),
            0,
        );
        const totalRemaining = Math.max(totalQuota - totalUsed, 0);
        const percentage =
            totalQuota > 0
                ? Math.min(Math.round((totalUsed / totalQuota) * 100), 100)
                : 0;

        dateLabel.textContent = trigger.dataset.dateLabel || date;
        sessionCount.textContent = numberFormatter.format(slots.length);
        quotaTotal.textContent = numberFormatter.format(totalQuota);
        usedTotal.textContent = numberFormatter.format(totalUsed);
        percentageLabel.textContent = `${percentage}%`;
        progressBar.style.width = `${percentage}%`;
        remainingLabel.textContent = `Sisa kuota: ${numberFormatter.format(totalRemaining)}`;

        renderSessions(slots);
        renderEvents(events);

        // Event menutup seluruh penjualan reguler pada tanggal tersebut.
        if (hasEvent && events.length === 0) {
            eventWarning.hidden = false;
        }

        modal.classList.add("is-visible");
        modal.setAttribute("aria-hidden", "false");
        document.body.classList.add("regular-modal-open");
        lastTrigger = trigger;
        document.getElementById("closeRegularDetailModal")?.focus();
    };

    const closeModal = () => {
        modal.classList.remove("is-visible");
        modal.setAttribute("aria-hidden", "true");
        document.body.classList.remove("regular-modal-open");
        lastTrigger?.focus();
    };

    calendarGrid.addEventListener("click", (event) => {
        const day = event.target.closest(".regular-calendar-day");
        if (!day || !calendarGrid.contains(day)) return;

        event.preventDefault();
        calendarGrid
            .querySelectorAll(".regular-calendar-day.is-selected")
            .forEach((item) => {
                item.classList.remove("is-selected");
                item.removeAttribute("aria-current");
            });
        day.classList.add("is-selected");
        day.setAttribute("aria-current", "date");
        openModal(day);
    });

    configureButton?.addEventListener("click", () => {
        if (!activeDate || configureButton.disabled) return;
        const template = configureButton.dataset.routeTemplate || "";
        if (!template.includes("__DATE__")) return;
        window.location.assign(
            template.replace("__DATE__", encodeURIComponent(activeDate)),
        );
    });

    closeButtons.forEach((button) =>
        button.addEventListener("click", closeModal),
    );

    modal.addEventListener("click", (event) => {
        if (event.target === modal) closeModal();
    });

    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape" && modal.classList.contains("is-visible")) {
            closeModal();
        }
    });
});

/* =====================================================
   SI-GORAY — HALAMAN ATUR SLOT REGULER
   Tambahkan blok ini ke resources/js/admin.js.
===================================================== */
document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById("regularConfigurationForm");
    const sessionList = document.getElementById("regularConfigSessionList");
    const addButton = document.getElementById("addRegularSession");

    if (!form || !sessionList || !addButton) return;

    let nextIndex = sessionList.querySelectorAll("[data-session-card]").length;
    const numberFormatter = new Intl.NumberFormat("id-ID");

    const timeToMinutes = (value) => {
        if (!/^\d{2}:\d{2}$/.test(value || "")) return null;
        const [hours, minutes] = value.split(":").map(Number);
        return hours * 60 + minutes;
    };

    const minutesToTime = (value) => {
        if (value < 0 || value >= 24 * 60) return "";
        const hours = Math.floor(value / 60)
            .toString()
            .padStart(2, "0");
        const minutes = (value % 60).toString().padStart(2, "0");
        return `${hours}:${minutes}`;
    };

    const getNextSessionTime = () => {
        const endInputs = [
            ...sessionList.querySelectorAll('input[name$="[end_time]"]'),
        ];
        const latestEnd = endInputs
            .map((input) => timeToMinutes(input.value))
            .filter((value) => value !== null)
            .reduce((max, value) => Math.max(max, value), 20 * 60);

        const start = latestEnd;
        const end = latestEnd + 120;
        return {
            start: minutesToTime(start),
            end: minutesToTime(end),
        };
    };

    const getNextSessionNumber = () => {
        const numbers = [
            ...sessionList.querySelectorAll("[data-session-number]"),
        ].map((input) => Number(input.value) || 0);
        return Math.max(0, ...numbers) + 1;
    };

    const createSessionCard = ({ index, number, start, end }) => {
        const card = document.createElement("article");
        card.className = "regular-config-session-card is-new-session";
        card.dataset.sessionCard = "";

        card.innerHTML = `
            <div class="regular-config-session-card-header">
                <div>
                    <span class="regular-config-session-kicker">SESI ${number}</span>
                    <h3 data-session-title>Sesi tambahan</h3>
                </div>
                <span class="regular-config-session-used">
                    <span class="material-symbols-rounded">confirmation_number</span>
                    Terpakai: ${numberFormatter.format(0)}
                </span>
            </div>

            <input type="hidden" name="sessions[${index}][id]" value="">
            <input type="hidden" name="sessions[${index}][is_new]" value="1">
            <input type="hidden" name="sessions[${index}][session_number]" value="${number}" data-session-number>
            <input type="hidden" value="0" data-session-used>

            <div class="regular-config-session-fields">
                <div class="regular-config-field">
                    <label for="session_${index}_start">Jam Mulai</label>
                    <input type="time" id="session_${index}_start"
                        name="sessions[${index}][start_time]" value="${start}" required>
                </div>
                <div class="regular-config-field">
                    <label for="session_${index}_end">Jam Selesai</label>
                    <input type="time" id="session_${index}_end"
                        name="sessions[${index}][end_time]" value="${end}" required>
                </div>
                <div class="regular-config-field">
                    <label for="session_${index}_quota">Kuota Sesi</label>
                    <input type="number" id="session_${index}_quota"
                        name="sessions[${index}][quota]" min="0" value="" placeholder="Masukkan kuota" required>
                    <small>Kuota diisi oleh admin.</small>
                </div>
                <div class="regular-config-field">
                    <label for="session_${index}_status">Status Sesi</label>
                    <select id="session_${index}_status" name="sessions[${index}][status]" required>
                        <option value="active" selected>Aktif</option>
                        <option value="inactive">Nonaktif</option>
                    </select>
                </div>
            </div>
        `;

        return card;
    };

    addButton.addEventListener("click", () => {
        const number = getNextSessionNumber();
        const times = getNextSessionTime();
        const card = createSessionCard({
            index: nextIndex,
            number,
            start: times.start,
            end: times.end,
        });

        sessionList.appendChild(card);
        nextIndex += 1;
        card.scrollIntoView({ behavior: "smooth", block: "nearest" });
        card.querySelector('input[type="time"]')?.focus({
            preventScroll: true,
        });
    });

    // Mencegah kuota sesi yang sudah terpakai diturunkan dari sisi browser.
    sessionList.addEventListener("input", (event) => {
        const quotaInput = event.target.closest('input[name$="[quota]"]');
        if (!quotaInput) return;

        const card = quotaInput.closest("[data-session-card]");
        const usedInput = card?.querySelector("[data-session-used]");
        if (!usedInput) return;

        const used = Number(usedInput.value) || 0;
        quotaInput.min = String(used);
    });

    form.addEventListener("submit", (event) => {
        const cards = [...sessionList.querySelectorAll("[data-session-card]")];
        if (cards.length === 0) {
            event.preventDefault();
            window.alert("Minimal harus ada satu sesi reguler.");
            return;
        }

        const intervals = cards
            .map((card) => ({
                start:
                    card.querySelector('input[name$="[start_time]"]')?.value ||
                    "",
                end:
                    card.querySelector('input[name$="[end_time]"]')?.value ||
                    "",
            }))
            .sort((a, b) => a.start.localeCompare(b.start));

        for (let index = 0; index < intervals.length; index += 1) {
            const current = intervals[index];
            if (
                !current.start ||
                !current.end ||
                current.end <= current.start
            ) {
                event.preventDefault();
                window.alert(
                    "Periksa jam mulai dan jam selesai. Jam selesai harus setelah jam mulai.",
                );
                return;
            }
            if (index > 0 && current.start < intervals[index - 1].end) {
                event.preventDefault();
                window.alert(
                    "Terdapat sesi dengan jadwal yang saling bertumpuk. Periksa kembali jam setiap sesi.",
                );
                return;
            }
        }
    });
});

// SEARCH USER

document.addEventListener("DOMContentLoaded", function () {
    const searchInput = document.getElementById("userSearch");
    const tableBody = document.getElementById("userTableBody");

    if (!searchInput || !tableBody) {
        return;
    }

    searchInput.addEventListener("input", function () {
        const keyword = this.value.toLowerCase().trim();
        const rows = tableBody.querySelectorAll("tr");

        rows.forEach(function (row) {
            const text = row.textContent.toLowerCase();

            if (text.includes(keyword)) {
                row.style.display = "";
            } else {
                row.style.display = "none";
            }
        });
    });
});

/* =========================================================
    MAJA-TIX - KELOLA PETUGAS GERBANG
    Modal, Edit, Status, Search, dan Filter
    Frontend Preview - Dummy Data
========================================================= */

(() => {
    function initOfficerPage() {
        const modal = document.getElementById("officerModal");
        const editModal = document.getElementById("officerEditModal");

        const openButton = document.getElementById("openOfficerModal");
        const closeButton = document.getElementById("closeOfficerModal");
        const cancelButton = document.getElementById("cancelOfficerModal");

        const closeEditButton = document.getElementById(
            "closeOfficerEditModal",
        );
        const cancelEditButton = document.getElementById(
            "cancelOfficerEditModal",
        );
        const saveEditButton = document.getElementById("saveOfficerEdit");

        const searchInput = document.getElementById("officerSearch");
        const statusFilter = document.getElementById("officerStatusFilter");
        const tableBody = document.getElementById("officerTableBody");
        const emptyState = document.getElementById("officerEmptyState");

        if (!modal || !tableBody) return;

        const editFields = {
            id: document.getElementById("editOfficerId"),
            name: document.getElementById("editOfficerName"),
            username: document.getElementById("editOfficerUsername"),
            email: document.getElementById("editOfficerEmail"),
            phone: document.getElementById("editOfficerPhone"),
        };

        const totalCount = document.getElementById("officerTotalCount");
        const activeCount = document.getElementById("officerActiveCount");
        const inactiveCount = document.getElementById("officerInactiveCount");
        const tableSummary = document.getElementById("officerTableSummary");

        /* -----------------------------------------
           MODAL TAMBAH
        ----------------------------------------- */

        function openModal(target) {
            target.classList.add("show");
            target.setAttribute("aria-hidden", "false");
            document.body.style.overflow = "hidden";

            const firstInput = target.querySelector(
                "input:not([type='hidden'])",
            );

            if (firstInput) {
                setTimeout(() => firstInput.focus(), 100);
            }
        }

        function closeModal(target) {
            target.classList.remove("show");
            target.setAttribute("aria-hidden", "true");

            const anotherModalOpen =
                modal.classList.contains("show") ||
                editModal?.classList.contains("show");

            if (!anotherModalOpen) {
                document.body.style.overflow = "";
            }
        }

        openButton?.addEventListener("click", () => openModal(modal));
        closeButton?.addEventListener("click", () => closeModal(modal));
        cancelButton?.addEventListener("click", () => closeModal(modal));

        modal.addEventListener("click", (event) => {
            if (event.target === modal) closeModal(modal);
        });

        /* -----------------------------------------
           MODAL EDIT
        ----------------------------------------- */

        function openEditModal(button) {
            editFields.id.value = button.dataset.id;
            editFields.name.value = button.dataset.name;
            editFields.username.value = button.dataset.username;
            editFields.email.value = button.dataset.email;
            editFields.phone.value = button.dataset.phone;

            openModal(editModal);
        }

        function closeEditModal() {
            closeModal(editModal);
        }

        closeEditButton?.addEventListener("click", closeEditModal);
        cancelEditButton?.addEventListener("click", closeEditModal);

        editModal?.addEventListener("click", (event) => {
            if (event.target === editModal) closeEditModal();
        });

        /* -----------------------------------------
           SIMPAN EDIT - DUMMY
        ----------------------------------------- */

        saveEditButton?.addEventListener("click", () => {
            const id = editFields.id.value;
            const name = editFields.name.value.trim();
            const username = editFields.username.value.trim();
            const email = editFields.email.value.trim();
            const phone = editFields.phone.value.trim();

            if (!name || !username || !email || !phone) {
                alert("Semua kolom harus diisi.");
                return;
            }

            if (!editFields.email.checkValidity()) {
                alert("Format email tidak valid.");
                editFields.email.focus();
                return;
            }

            const row = tableBody.querySelector(
                `tr.officer-row[data-id="${CSS.escape(id)}"]`,
            );

            if (!row) return;

            row.querySelector(".officer-name").textContent = name;
            row.querySelector(".officer-username").textContent = `@${username}`;
            row.querySelector(".officer-email").textContent = email;
            row.querySelector(".officer-phone").textContent = phone;

            const avatar = row.querySelector(".officer-avatar");
            if (avatar) {
                avatar.textContent = name.charAt(0).toUpperCase();
            }

            const editButton = row.querySelector(".officer-edit-btn");

            if (editButton) {
                editButton.dataset.name = name;
                editButton.dataset.username = username;
                editButton.dataset.email = email;
                editButton.dataset.phone = phone;
            }

            closeEditModal();
            filterOfficers();
        });

        /* -----------------------------------------
           AKTIF / NONAKTIF
        ----------------------------------------- */

        function updateOfficerStatus(row, isActive) {
            row.dataset.status = isActive ? "active" : "inactive";

            const statusElement = row.querySelector(".officer-status");

            if (statusElement) {
                statusElement.classList.toggle("active", isActive);
                statusElement.classList.toggle("inactive", !isActive);

                statusElement.innerHTML = `
                    <span></span>
                    ${isActive ? "Aktif" : "Nonaktif"}
                `;
            }

            const toggleButton = row.querySelector(".officer-toggle-btn");

            if (toggleButton) {
                const title = isActive ? "Nonaktifkan akun" : "Aktifkan akun";

                toggleButton.title = title;
                toggleButton.setAttribute("aria-label", title);

                toggleButton.innerHTML = `
                    <span class="material-symbols-rounded">
                        ${isActive ? "person_off" : "person_check"}
                    </span>
                `;
            }

            updateStatistics();
            filterOfficers();
        }

        /* -----------------------------------------
        CUSTOM CONFIRMATION PETUGAS
        ----------------------------------------- */

        const officerStatusDialog = document.getElementById(
            "officerStatusDialog",
        );

        const officerConfirmIcon =
            document.getElementById("officerConfirmIcon");

        const officerConfirmTitle = document.getElementById(
            "officerConfirmTitle",
        );

        const officerConfirmDescription = document.getElementById(
            "officerConfirmDescription",
        );

        const officerConfirmCancel = document.getElementById(
            "officerConfirmCancel",
        );

        const officerConfirmSubmit = document.getElementById(
            "officerConfirmSubmit",
        );

        let pendingOfficerRow = null;
        let pendingOfficerStatus = null;

        function openOfficerStatusDialog(row, nextStatus, officerName) {
            if (!officerStatusDialog) return;

            pendingOfficerRow = row;
            pendingOfficerStatus = nextStatus;

            if (nextStatus) {
                officerConfirmIcon.className = "officer-confirm-icon activate";

                officerConfirmIcon.innerHTML = `
            <span class="material-symbols-rounded">
                person_check
            </span>
        `;

                officerConfirmTitle.textContent = "Aktifkan Petugas?";

                officerConfirmDescription.textContent = `Apakah kamu yakin ingin mengaktifkan kembali akun ${officerName}?`;

                officerConfirmSubmit.textContent = "Ya, Aktifkan";

                officerConfirmSubmit.className =
                    "officer-confirm-submit activate";
            } else {
                officerConfirmIcon.className =
                    "officer-confirm-icon deactivate";

                officerConfirmIcon.innerHTML = `
            <span class="material-symbols-rounded">
                person_off
            </span>
        `;

                officerConfirmTitle.textContent = "Nonaktifkan Petugas?";

                officerConfirmDescription.textContent = `Apakah kamu yakin ingin menonaktifkan akun ${officerName}?`;

                officerConfirmSubmit.textContent = "Ya, Nonaktifkan";

                officerConfirmSubmit.className =
                    "officer-confirm-submit deactivate";
            }

            officerStatusDialog.showModal();
        }

        /* Tombol Batal */
        officerConfirmCancel.addEventListener("click", () => {
            officerStatusDialog.close();
        });

        /* Klik tombol konfirmasi */
        officerConfirmSubmit.addEventListener("click", () => {
            if (!pendingOfficerRow || pendingOfficerStatus === null) {
                return;
            }

            updateOfficerStatus(pendingOfficerRow, pendingOfficerStatus);

            officerStatusDialog.close();
        });

        /* Klik area luar dialog */
        officerStatusDialog.addEventListener("click", (event) => {
            if (event.target === officerStatusDialog) {
                officerStatusDialog.close();
            }
        });

        /* Bersihkan data setelah dialog ditutup */
        officerStatusDialog.addEventListener("close", () => {
            pendingOfficerRow = null;
            pendingOfficerStatus = null;
        });

        /* -----------------------------------------
        EVENT LISTENER TABEL PETUGAS
        ----------------------------------------- */

        tableBody.addEventListener("click", (event) => {
            const editButton = event.target.closest(".officer-edit-btn");
            const toggleButton = event.target.closest(".officer-toggle-btn");

            if (editButton) {
                openEditModal(editButton);
                return;
            }

            if (toggleButton) {
                const row = toggleButton.closest(".officer-row");
                if (!row) return;

                const isCurrentlyActive = row.dataset.status === "active";
                const nextStatus = !isCurrentlyActive;

                const officerName =
                    row.querySelector(".officer-name")?.textContent.trim() ||
                    "Petugas";

                openOfficerStatusDialog(row, nextStatus, officerName);
            }
        });

        /* -----------------------------------------
           STATISTIK
        ----------------------------------------- */

        function updateStatistics() {
            const rows = [...tableBody.querySelectorAll("tr.officer-row")];

            const total = rows.length;
            const active = rows.filter(
                (row) => row.dataset.status === "active",
            ).length;
            const inactive = total - active;

            if (totalCount) totalCount.textContent = total;
            if (activeCount) activeCount.textContent = active;
            if (inactiveCount) inactiveCount.textContent = inactive;

            if (tableSummary) {
                tableSummary.textContent = `Menampilkan ${total} petugas`;
            }
        }

        /* -----------------------------------------
           SEARCH DAN FILTER
        ----------------------------------------- */

        function filterOfficers() {
            const keyword = searchInput?.value.trim().toLowerCase() || "";
            const selectedStatus = statusFilter?.value || "all";

            const rows = tableBody.querySelectorAll("tr.officer-row");
            let visibleCount = 0;

            rows.forEach((row) => {
                const rowText = row.textContent.toLowerCase();
                const rowStatus = row.dataset.status;

                const matchesKeyword = rowText.includes(keyword);
                const matchesStatus =
                    selectedStatus === "all" || rowStatus === selectedStatus;

                const isVisible = matchesKeyword && matchesStatus;

                row.style.display = isVisible ? "" : "none";

                if (isVisible) visibleCount++;
            });

            if (emptyState) {
                emptyState.style.display = visibleCount === 0 ? "" : "none";
            }
        }

        searchInput?.addEventListener("input", filterOfficers);
        statusFilter?.addEventListener("change", filterOfficers);

        /* -----------------------------------------
        ESCAPE
        ----------------------------------------- */

        document.addEventListener("keydown", (event) => {
            if (event.key !== "Escape") return;

            if (editModal?.classList.contains("show")) {
                closeEditModal();
            } else if (modal.classList.contains("show")) {
                closeModal(modal);
            }
        });

        updateStatistics();
        filterOfficers();
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", initOfficerPage);
    } else {
        initOfficerPage();
    }
})();

/* =====================================================
   GLOBAL SEARCH - SI-GORAY ADMIN
   Tahap 1: UI dan interaksi
===================================================== */

document.addEventListener("DOMContentLoaded", function () {
    const searchToggle = document.getElementById("globalSearchToggle");
    const searchOverlay = document.getElementById("globalSearchOverlay");
    const searchPanel = document.getElementById("globalSearchPanel");
    const searchClose = document.getElementById("globalSearchClose");
    const searchForm = document.getElementById("globalSearchForm");
    const searchInput = document.getElementById("globalSearchInput");
    const searchClear = document.getElementById("globalSearchClear");
    const searchResults = document.getElementById("globalSearchResults");

    if (
        !searchToggle ||
        !searchOverlay ||
        !searchPanel ||
        !searchClose ||
        !searchForm ||
        !searchInput ||
        !searchClear ||
        !searchResults
    ) {
        return;
    }

    let previousFocus = null;

    function openGlobalSearch() {
        previousFocus = document.activeElement;

        searchOverlay.classList.add("is-open");
        searchOverlay.setAttribute("aria-hidden", "false");
        searchPanel.setAttribute("aria-hidden", "false");

        document.body.style.overflow = "hidden";

        window.setTimeout(function () {
            searchInput.focus();
        }, 50);
    }

    function closeGlobalSearch() {
        searchOverlay.classList.remove("is-open");
        searchOverlay.setAttribute("aria-hidden", "true");
        searchPanel.setAttribute("aria-hidden", "true");

        document.body.style.overflow = "";

        searchToggle.focus();

        if (previousFocus && previousFocus !== searchToggle) {
            // Fokus dikembalikan ke tombol pencarian navbar.
            searchToggle.focus();
        }
    }

    function renderSearchMessage(title, message) {
        const wrapper = document.createElement("div");
        wrapper.className = "global-search-welcome";

        const iconBox = document.createElement("div");
        iconBox.className = "global-search-welcome-icon";

        const icon = document.createElement("span");
        icon.className = "material-symbols-rounded";
        icon.textContent = "info";

        const heading = document.createElement("h3");
        heading.textContent = title;

        const description = document.createElement("p");
        description.textContent = message;

        iconBox.appendChild(icon);
        wrapper.append(iconBox, heading, description);

        searchResults.replaceChildren(wrapper);
    }

    function updateClearButton() {
        searchClear.hidden = searchInput.value.length === 0;
    }

    searchToggle.addEventListener("click", openGlobalSearch);

    searchClose.addEventListener("click", closeGlobalSearch);

    searchOverlay.addEventListener("click", function (event) {
        if (event.target === searchOverlay) {
            closeGlobalSearch();
        }
    });

    searchClear.addEventListener("click", function () {
        searchInput.value = "";
        updateClearButton();

        renderSearchMessage(
            "Cari informasi dengan cepat",
            "Masukkan nama event, kode pemesanan, atau informasi tiket yang ingin kamu temukan.",
        );

        searchInput.focus();
    });

    searchInput.addEventListener("input", function () {
        updateClearButton();

        const keyword = searchInput.value.trim();

        if (keyword.length === 0) {
            renderSearchMessage(
                "Cari informasi dengan cepat",
                "Masukkan nama event, kode pemesanan, atau informasi tiket yang ingin kamu temukan.",
            );
            return;
        }

        renderSearchMessage(
            "Pencarian siap",
            'Kata kunci "' +
                keyword +
                '" sudah dimasukkan. Pencarian data akan aktif setelah endpoint backend diintegrasikan.',
        );
    });

    searchForm.addEventListener("submit", function (event) {
        event.preventDefault();

        const keyword = searchInput.value.trim();

        if (!keyword) {
            searchInput.focus();
            return;
        }

        renderSearchMessage(
            "Backend pencarian belum terhubung",
            "Antarmuka pencarian sudah siap, tetapi hasil event, pemesanan, dan tiket reguler belum diambil dari Laravel.",
        );
    });

    document.addEventListener("keydown", function (event) {
        const isSearchShortcut =
            (event.ctrlKey || event.metaKey) && event.key.toLowerCase() === "k";

        if (isSearchShortcut) {
            event.preventDefault();

            if (!searchOverlay.classList.contains("is-open")) {
                openGlobalSearch();
            } else {
                searchInput.focus();
            }

            return;
        }

        if (
            event.key === "Escape" &&
            searchOverlay.classList.contains("is-open")
        ) {
            closeGlobalSearch();
        }
    });

    updateClearButton();
});

// Script Filter Pencarian TIKET REGULER Instan (Client-Side)
document.addEventListener("DOMContentLoaded", function () {
    const searchInput = document.getElementById("regularSearchInput");
    const tableRows = document.querySelectorAll(
        "#regularTable tbody tr.regular-table-row",
    );
    const emptyRow = document.getElementById("regularSearchEmptyRow");

    if (!searchInput || tableRows.length === 0) return;

    searchInput.addEventListener("input", function () {
        const keyword = this.value.toLowerCase().trim();
        let visibleCount = 0;

        tableRows.forEach(function (row) {
            const rowText = row.innerText.toLowerCase();
            const isMatch = rowText.includes(keyword);
            row.style.display = isMatch ? "" : "none";
            if (isMatch) visibleCount++;
        });

        if (emptyRow) {
            emptyRow.style.display = visibleCount === 0 ? "" : "none";
        }
    });
});

/* =====================================================
   NOTIFICATION DROPDOWN - DUMMY DATA
   Tahap 1: UI dan interaksi
===================================================== */

document.addEventListener("DOMContentLoaded", function () {
    const wrapper = document.getElementById("notificationWrapper");
    const toggle = document.getElementById("notificationToggle");
    const dropdown = document.getElementById("notificationDropdown");
    const countElement = document.getElementById("notificationCount");
    const summaryElement = document.getElementById("notificationSummary");
    const listElement = document.getElementById("notificationList");
    const markAllButton = document.getElementById("notificationMarkAll");

    if (
        !wrapper ||
        !toggle ||
        !dropdown ||
        !countElement ||
        !summaryElement ||
        !listElement ||
        !markAllButton
    ) {
        return;
    }

    // Data contoh untuk menguji UI sebelum backend tersedia.
    const notifications = [
        {
            id: 1,
            icon: "receipt_long",
            title: "Pemesanan baru",
            message: "Pemesanan BKG-0125 telah dibuat.",
            time: "Baru saja",
            url: "#",
            isRead: false,
        },
        {
            id: 2,
            icon: "payments",
            title: "Pembayaran berhasil",
            message: "Pembayaran tiket telah dikonfirmasi.",
            time: "5 menit lalu",
            url: "#",
            isRead: false,
        },
        {
            id: 3,
            icon: "qr_code_scanner",
            title: "Tiket telah digunakan",
            message: "QR tiket pengunjung telah dipindai.",
            time: "15 menit lalu",
            url: "#",
            isRead: false,
        },
        {
            id: 4,
            icon: "event",
            title: "Informasi event",
            message: "Periksa jadwal event yang akan berlangsung.",
            time: "1 jam lalu",
            url: "#",
            isRead: true,
        },
    ];

    function getUnreadCount() {
        return notifications.filter(function (item) {
            return !item.isRead;
        }).length;
    }

    function updateSummary() {
        const unread = getUnreadCount();

        countElement.textContent = unread > 99 ? "99+" : unread;
        countElement.hidden = unread === 0;

        summaryElement.textContent = unread + " belum dibaca";

        markAllButton.disabled = unread === 0;
        markAllButton.style.opacity = unread === 0 ? "0.5" : "1";
        markAllButton.style.cursor = unread === 0 ? "default" : "pointer";
    }

    function renderNotifications() {
        listElement.replaceChildren();

        if (notifications.length === 0) {
            const empty = document.createElement("div");
            empty.className = "notification-empty";

            const icon = document.createElement("span");
            icon.className = "material-symbols-rounded";
            icon.textContent = "notifications_off";

            const message = document.createElement("p");
            message.textContent = "Belum ada notifikasi.";

            empty.append(icon, message);
            listElement.appendChild(empty);

            updateSummary();
            return;
        }

        notifications.forEach(function (item) {
            const row = document.createElement("div");
            row.className = "notification-item";

            if (!item.isRead) {
                row.classList.add("is-unread");
            }

            row.tabIndex = 0;
            row.setAttribute("role", "link");
            row.setAttribute("aria-label", item.title + ". " + item.message);

            const iconBox = document.createElement("div");
            iconBox.className = "notification-item-icon";

            const icon = document.createElement("span");
            icon.className = "material-symbols-rounded";
            icon.textContent = item.icon;

            iconBox.appendChild(icon);

            const content = document.createElement("div");
            content.className = "notification-item-content";

            const title = document.createElement("h4");
            title.className = "notification-item-title";
            title.textContent = item.title;

            const message = document.createElement("p");
            message.className = "notification-item-message";
            message.textContent = item.message;

            const time = document.createElement("span");
            time.className = "notification-item-time";
            time.textContent = item.time;

            content.append(title, message, time);
            row.append(iconBox, content);

            if (!item.isRead) {
                const indicator = document.createElement("span");
                indicator.className = "notification-unread-indicator";
                indicator.setAttribute("aria-label", "Belum dibaca");
                row.appendChild(indicator);
            }

            function openNotification() {
                item.isRead = true;
                updateSummary();
                renderNotifications();

                // Navigasi hanya dilakukan jika URL sudah ditentukan.
                if (item.url && item.url !== "#") {
                    window.location.href = item.url;
                }
            }

            row.addEventListener("click", openNotification);

            row.addEventListener("keydown", function (event) {
                if (event.key === "Enter" || event.key === " ") {
                    event.preventDefault();
                    openNotification();
                }
            });

            listElement.appendChild(row);
        });

        updateSummary();
    }

    function openDropdown() {
        dropdown.hidden = false;
        toggle.setAttribute("aria-expanded", "true");
    }

    function closeDropdown() {
        dropdown.hidden = true;
        toggle.setAttribute("aria-expanded", "false");
    }

    toggle.addEventListener("click", function (event) {
        event.stopPropagation();

        if (dropdown.hidden) {
            openDropdown();
        } else {
            closeDropdown();
        }
    });

    dropdown.addEventListener("click", function (event) {
        event.stopPropagation();
    });

    document.addEventListener("click", function (event) {
        if (!wrapper.contains(event.target)) {
            closeDropdown();
        }
    });

    document.addEventListener("keydown", function (event) {
        if (event.key === "Escape" && !dropdown.hidden) {
            closeDropdown();
            toggle.focus();
        }
    });

    markAllButton.addEventListener("click", function () {
        notifications.forEach(function (item) {
            item.isRead = true;
        });

        renderNotifications();
    });

    renderNotifications();
});

// LAPORAN
document
    .querySelectorAll(".report-export-btn[data-export-url]")
    .forEach((btn) => {
        btn.addEventListener("click", async () => {
            const url = btn.dataset.exportUrl;
            if (!url) {
                alert("Endpoint ekspor belum tersedia.");
                return;
            }

            btn.disabled = true;
            try {
                const res = await fetch(url, {
                    credentials: "same-origin",
                    headers: {
                        "X-Requested-With": "XMLHttpRequest",
                        Accept: "application/json, */*;q=0.8",
                    },
                });

                if (!res.ok) {
                    let message = "Gagal mengekspor laporan.";
                    try {
                        message = (await res.json()).message || message;
                    } catch {}
                    throw new Error(message);
                }

                const blob = await res.blob();
                const match = /filename\*?=(?:UTF-8'')?"?([^";]+)"?/i.exec(
                    res.headers.get("Content-Disposition") || "",
                );
                const ext = btn.classList.contains("pdf") ? "pdf" : "xlsx";
                const link = Object.assign(document.createElement("a"), {
                    href: URL.createObjectURL(blob),
                    download: match
                        ? decodeURIComponent(match[1])
                        : `laporan.${ext}`,
                });
                document.body.append(link);
                link.click();
                link.remove();
                setTimeout(() => URL.revokeObjectURL(link.href), 1000);
            } catch (err) {
                alert(err.message);
            } finally {
                btn.disabled = false;
            }
        });
    });
