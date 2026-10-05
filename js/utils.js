/**
 * Utility functions untuk Sistem Kasir Warung Hanisa
 */

export function formatRupiah(nominal) {
    const number = Number(nominal) || 0;
    return new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(number);
}

export function parseNumber(value) {
    if (typeof value === "number") return value;
    if (!value) return 0;
    const clean = String(value).replace(/[^0-9]/g, "");
    return parseInt(clean, 10) || 0;
}

export function getTanggalHariIni() {
    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, "0");
    const day = String(now.getDate()).padStart(2, "0");
    return `${year}-${month}-${day}`;
}

export function formatTanggal(tanggalStr) {
    if (!tanggalStr) return "-";
    const date = new Date(tanggalStr);
    if (isNaN(date.getTime())) return tanggalStr;
    return date.toLocaleDateString("id-ID", {
        weekday: "long",
        day: "numeric",
        month: "long",
        year: "numeric"
    });
}

export function formatTanggalPendek(dateObj = new Date()) {
    const date = new Date(dateObj);
    return date.toLocaleDateString("id-ID", {
        day: "2-digit",
        month: "2-digit",
        year: "numeric"
    });
}

export function formatWaktu(dateObj = new Date()) {
    const date = new Date(dateObj);
    return date.toLocaleTimeString("id-ID", {
        hour: "2-digit",
        minute: "2-digit",
        second: "2-digit"
    }) + " WIB";
}

export function generateInvoiceNumber(sequence = 1) {
    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, "0");
    const day = String(now.getDate()).padStart(2, "0");
    const randomSuffix = Math.floor(1000 + Math.random() * 9000);
    return `INV-${year}${month}${day}-${randomSuffix}`;
}

export function generateId(prefix = "ID") {
    const random = Math.floor(100 + Math.random() * 900);
    return `${prefix}-${Date.now().toString().slice(-4)}${random}`;
}

export function showToast(message, type = "info", duration = 3000) {
    const container = document.getElementById("toast-container");
    if (!container) return;

    const toast = document.createElement("div");
    toast.className = `toast-item toast-${type} animate-slide-in`;

    let icon = "ℹ️";
    if (type === "success") icon = "✅";
    if (type === "error") icon = "⚠️";
    if (type === "warning") icon = "🔔";

    toast.innerHTML = `
        <span class="toast-icon">${icon}</span>
        <span class="toast-message">${message}</span>
        <button class="toast-close" type="button" aria-label="Tutup">&times;</button>
    `;

    const closeBtn = toast.querySelector(".toast-close");
    closeBtn.addEventListener("click", () => {
        toast.classList.add("animate-fade-out");
        setTimeout(() => toast.remove(), 250);
    });

    container.appendChild(toast);

    setTimeout(() => {
        if (toast.parentElement) {
            toast.classList.add("animate-fade-out");
            setTimeout(() => toast.remove(), 250);
        }
    }, duration);
}

/**
 * Efek Suara Cash Register Ding menggunakan Web Audio API murni
 */
export function playCashChime() {
    try {
        const AudioContext = window.AudioContext || window.webkitAudioContext;
        if (!AudioContext) return;
        const ctx = new AudioContext();

        // Nada 1: Bell tinggi
        const osc1 = ctx.createOscillator();
        const gain1 = ctx.createGain();
        osc1.type = "sine";
        osc1.frequency.setValueAtTime(987.77, ctx.currentTime); // B5
        osc1.frequency.exponentialRampToValueAtTime(1318.51, ctx.currentTime + 0.12); // E6
        gain1.gain.setValueAtTime(0.3, ctx.currentTime);
        gain1.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.5);

        osc1.connect(gain1);
        gain1.connect(ctx.destination);
        osc1.start(ctx.currentTime);
        osc1.stop(ctx.currentTime + 0.5);

        // Nada 2: Chime harmoni (khas kasir sukses)
        const osc2 = ctx.createOscillator();
        const gain2 = ctx.createGain();
        osc2.type = "triangle";
        osc2.frequency.setValueAtTime(1760.00, ctx.currentTime + 0.08); // A6
        gain2.gain.setValueAtTime(0.25, ctx.currentTime + 0.08);
        gain2.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.6);

        osc2.connect(gain2);
        gain2.connect(ctx.destination);
        osc2.start(ctx.currentTime + 0.08);
        osc2.stop(ctx.currentTime + 0.6);
    } catch (e) {
        // Fallback jika browser block audio autoplay
    }
}

export function exportToCSV(filename, rows) {
    if (!rows || !rows.length) return;
    const processRow = row => row.map(val => {
        if (val === null || val === undefined) return '""';
        let result = String(val).replace(/"/g, '""');
        if (result.search(/("|,|\n)/g) >= 0) result = `"${result}"`;
        return result;
    }).join(",");

    const csvContent = "data:text/csv;charset=utf-8,\uFEFF" + rows.map(processRow).join("\n");
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", filename);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}