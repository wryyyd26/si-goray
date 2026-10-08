/* ===== SIDEBAR ===== */
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

/* ===== DARK MODE & CHARTS INITIALIZATION ===== */
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
