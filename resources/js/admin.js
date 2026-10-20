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
function initCharts() {
    const colors = getChartColors();

    // 1. Line Chart (Grafik Pemesanan)
    const ctxLine = document.getElementById("bookingChart");
    if (ctxLine) {
        lineChart = new Chart(ctxLine, {
            type: "line",
            data: {
                labels: [
                    "Jan",
                    "Feb",
                    "Mar",
                    "Apr",
                    "Mei",
                    "Jun",
                    "Jul",
                    "Agu",
                    "Sep",
                ],
                datasets: [
                    {
                        label: "Reguler",
                        data: [25, 30, 40, 35, 50, 60, 45, 65, 55],
                        borderColor: colors.regulerLine,
                        backgroundColor: colors.regulerLine,
                        tension: 0.4,
                        borderWidth: 2,
                        pointRadius: 4,
                        pointBackgroundColor: colors.regulerLine,
                    },
                    {
                        label: "Event",
                        data: [15, 20, 25, 20, 30, 30, 25, 35, 30],
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
                        max: 80,
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
                        data: [748, 94, 34],
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

/* =====================================================
   REGULAR / SLOT HARIAN MODAL
===================================================== */

const regularModal = document.getElementById("regularModal");
const regularModalForm = document.getElementById("regularModalForm");
const regularModalDate = document.getElementById("regularModalDate");
const regularQuota = document.getElementById("regularQuota");
const regularUsedInfo = document.getElementById("regularUsedInfo");

const statusActive = document.getElementById("statusActive");
const statusInactive = document.getElementById("statusInactive");

function openRegularModal(button) {
    if (!regularModal) return;

    const date = button.dataset.date;
    const dateLabel = button.dataset.dateLabel;
    const quota = button.dataset.quota;
    const used = button.dataset.used;
    const status = button.dataset.status;

    /*
     * Isi data ke modal
     */

    regularModalDate.textContent = dateLabel;

    regularQuota.value = quota;

    regularUsedInfo.textContent = `Sudah terpakai: ${Number(used).toLocaleString("id-ID")} pengguna`;

    /*
     * Tentukan status
     */

    if (status === "active") {
        statusActive.checked = true;
    } else {
        statusInactive.checked = true;
    }

    /*
     * Tentukan action form
     */

    regularModalForm.action = `/admin/reguler/${date}`;

    /*
     * Tampilkan modal
     */

    regularModal.classList.add("show");

    regularModal.setAttribute("aria-hidden", "false");

    /*
     * Lock body
     */

    document.body.style.overflow = "hidden";

    /*
     * Fokus input
     */

    setTimeout(() => {
        regularQuota.focus();

        regularQuota.select();
    }, 100);
}

function closeRegularModal() {
    if (!regularModal) return;

    regularModal.classList.remove("show");

    regularModal.setAttribute("aria-hidden", "true");

    document.body.style.overflow = "";
}

/*
 * Tombol + / -
 */

function changeRegularQuota(amount) {
    if (!regularQuota) return;

    let current = parseInt(regularQuota.value) || 0;

    current += amount;

    if (current < 1) {
        current = 1;
    }

    regularQuota.value = current;
}

/*
 * ESC untuk menutup modal
 */

document.addEventListener("keydown", function (event) {
    if (event.key === "Escape") {
        closeRegularModal();
    }
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
