// ─── Global Configuration ────────────────────────────────────────────────
const CONFIG = {
    sidebarId: 'sidebar',
    overlayId: 'sidebarOverlay',
    sidebarOpenId: 'sidebarOpen',
    sidebarCloseId: 'sidebarClose',
    autoHideAlert: 5000, // 5 sec
};

// ─── DOM Ready ───────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    initSidebar();
    initFlashMessages();
    initFormValidation();
    initUploadPreview();
    initRupiahFormat();
    initDateValidation();
    initPasswordToggle();
    initAutoHideAlerts();
    initConfirmDialogs();
    initTooltips();
});

// ─── Sidebar Management ──────────────────────────────────────────────────
function initSidebar() {
    const sidebar = document.getElementById(CONFIG.sidebarId);
    const overlay = document.getElementById(CONFIG.overlayId);
    const btnOpen = document.getElementById(CONFIG.sidebarOpenId);
    const btnClose = document.getElementById(CONFIG.sidebarCloseId);

    if (!sidebar) return;

    if (btnOpen) {
        btnOpen.addEventListener('click', () => openSidebar(sidebar, overlay));
    }

    if (btnClose) {
        btnClose.addEventListener('click', () => closeSidebar(sidebar, overlay));
    }

    if (overlay) {
        overlay.addEventListener('click', () => closeSidebar(sidebar, overlay));
    }

    // Close sidebar on Escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && sidebar.classList.contains('open')) {
            closeSidebar(sidebar, overlay);
        }
    });

    // Close sidebar on window resize (if desktop)
    window.addEventListener('resize', () => {
        if (window.innerWidth > 1024) {
            closeSidebar(sidebar, overlay);
        }
    });
}

function openSidebar(sidebar, overlay) {
    sidebar.classList.add('open');
    if (overlay) overlay.classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeSidebar(sidebar, overlay) {
    sidebar.classList.remove('open');
    if (overlay) overlay.classList.remove('show');
    document.body.style.overflow = '';
}

// ─── Flash Messages ──────────────────────────────────────────────────────
function initFlashMessages() {
    const params = new URLSearchParams(location.search);
    const status = params.get('status');
    const msg = params.get('msg');

    const messages = {
        success: { icon: 'success', title: 'Berhasil!', text: 'Data berhasil disimpan.' },
        saved: { icon: 'success', title: 'Tersimpan!', text: 'Perubahan berhasil disimpan.' },
        deleted: { icon: 'success', title: 'Terhapus!', text: 'Data berhasil dihapus.' },
        error: { icon: 'error', title: 'Gagal!', text: msg || 'Terjadi kesalahan.' },
        warning: { icon: 'warning', title: 'Perhatian!', text: msg || 'Periksa kembali data Anda.' },
        info: { icon: 'info', title: 'Informasi', text: msg || '' },
        session_expired: { icon: 'warning', title: 'Sesi Berakhir', text: 'Silakan login kembali.' }
    };

    if (status && messages[status]) {
        const m = messages[status];
        Swal.fire({
            icon: m.icon,
            title: m.title,
            text: m.text,
            timer: 3000,
            showConfirmButton: false,
            timerProgressBar: true,
            toast: false,
            position: 'center',
        });

        // Clean URL
        const url = new URL(location);
        url.searchParams.delete('status');
        url.searchParams.delete('msg');
        url.searchParams.delete('error');
        history.replaceState({}, '', url);
    }
}

// ─── Form Validation ─────────────────────────────────────────────────────
function initFormValidation() {
    const forms = document.querySelectorAll('form[novalidate]');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(this)) {
                e.preventDefault();
                e.stopPropagation();
            } else {
                // Disable submit button to prevent double submission
                const submitBtn = this.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<span class="spinner"></span> Memproses...';
                }
            }
        });

        // Real-time validation
        const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
        inputs.forEach(input => {
            input.addEventListener('blur', () => validateField(input));
            input.addEventListener('input', () => {
                if (input.classList.contains('is-invalid')) {
                    validateField(input);
                }
            });
        });
    });
}

function validateForm(form) {
    let isValid = true;
    const requiredFields = form.querySelectorAll('input[required], select[required], textarea[required]');
    
    requiredFields.forEach(field => {
        if (!validateField(field)) {
            isValid = false;
        }
    });

    return isValid;
}

function validateField(field) {
    const value = field.value.trim();
    let isValid = true;
    let errorMsg = '';

    // Remove previous validation
    field.classList.remove('is-invalid');
    const existingFeedback = field.parentElement.querySelector('.invalid-feedback');
    if (existingFeedback) existingFeedback.remove();

    // Required check
    if (field.hasAttribute('required') && value === '') {
        isValid = false;
        errorMsg = 'Field ini wajib diisi.';
    }

    // Email validation
    if (field.type === 'email' && value !== '') {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(value)) {
            isValid = false;
            errorMsg = 'Format email tidak valid.';
        }
    }

    // Phone validation
    if (field.dataset.phone === 'true' && value !== '') {
        const phoneRegex = /^(\+62|62|0)8[1-9][0-9]{6,10}$/;
        if (!phoneRegex.test(value.replace(/[^0-9+]/g, ''))) {
            isValid = false;
            errorMsg = 'Format nomor telepon tidak valid.';
        }
    }

    // NIK validation
    if (field.dataset.nik === 'true' && value !== '') {
        const nik = value.replace(/[^0-9]/g, '');
        if (nik.length !== 16) {
            isValid = false;
            errorMsg = 'NIK harus 16 digit angka.';
        }
    }

    // Min length
    if (field.dataset.minLength && value.length < parseInt(field.dataset.minLength)) {
        isValid = false;
        errorMsg = `Minimal ${field.dataset.minLength} karakter.`;
    }

    if (!isValid) {
        field.classList.add('is-invalid');
        const feedback = document.createElement('div');
        feedback.className = 'invalid-feedback';
        feedback.innerHTML = `<i class="bi bi-exclamation-circle"></i> ${errorMsg}`;
        field.parentElement.appendChild(feedback);
    }

    return isValid;
}

// ─── Upload Preview ──────────────────────────────────────────────────────
function initUploadPreview() {
    const uploadZones = document.querySelectorAll('.upload-zone');
    
    uploadZones.forEach(zone => {
        const input = zone.querySelector('input[type="file"]');
        const preview = zone.querySelector('.upload-preview');
        const placeholder = zone.querySelector('.upload-placeholder');
        
        if (!input) return;

        // Drag and drop
        zone.addEventListener('dragover', (e) => {
            e.preventDefault();
            zone.classList.add('drag-over');
        });

        zone.addEventListener('dragleave', () => {
            zone.classList.remove('drag-over');
        });

        zone.addEventListener('drop', (e) => {
            e.preventDefault();
            zone.classList.remove('drag-over');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                input.files = files;
                handleFileSelect(input, preview, placeholder, zone);
            }
        });

        // File select
        input.addEventListener('change', () => {
            handleFileSelect(input, preview, placeholder, zone);
        });
    });
}

function handleFileSelect(input, preview, placeholder, zone) {
    const file = input.files[0];
    if (!file) return;

    // Validate size
    const maxSize = 5 * 1024 * 1024; // 5MB
    if (file.size > maxSize) {
        Swal.fire('File Terlalu Besar', 'Ukuran maksimal file adalah 5 MB.', 'warning');
        input.value = '';
        return;
    }

    // Validate type
    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
    if (!allowedTypes.includes(file.type)) {
        Swal.fire('Format Tidak Didukung', 'Gunakan file JPG, PNG, atau WEBP.', 'warning');
        input.value = '';
        return;
    }

    // Preview
    const reader = new FileReader();
    reader.onload = (e) => {
        if (preview && preview.querySelector('img')) {
            preview.querySelector('img').src = e.target.result;
            preview.style.display = 'block';
        }
        if (placeholder) {
            placeholder.style.display = 'none';
        }
        if (zone) {
            zone.classList.add('has-file');
        }
    };
    reader.readAsDataURL(file);
}

// ─── Rupiah Format ───────────────────────────────────────────────────────
function initRupiahFormat() {
    document.querySelectorAll('[data-rupiah]').forEach(el => {
        el.addEventListener('input', function() {
            const raw = this.value.replace(/\D/g, '');
            this.value = raw ? Number(raw).toLocaleString('id-ID') : '';
        });

        el.addEventListener('focus', function() {
            this.select();
        });

        // Format initial value
        if (el.value) {
            const raw = el.value.replace(/\D/g, '');
            el.value = raw ? Number(raw).toLocaleString('id-ID') : '';
        }
    });
}

// ─── Date Validation ─────────────────────────────────────────────────────
function initDateValidation() {
    const dateInputs = document.querySelectorAll('input[type="date"]');
    
    dateInputs.forEach(input => {
        if (input.dataset.minToday === 'true') {
            const today = new Date().toISOString().split('T')[0];
            input.setAttribute('min', today);
        }
    });
}

// ─── Password Toggle ─────────────────────────────────────────────────────
function initPasswordToggle() {
    const toggleBtns = document.querySelectorAll('[data-toggle-password]');
    
    toggleBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const targetId = this.dataset.target;
            const input = document.getElementById(targetId);
            
            if (!input) return;
            
            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            
            const icon = this.querySelector('i');
            if (icon) {
                icon.className = isPassword ? 'bi bi-eye-slash' : 'bi bi-eye';
            }
        });
    });
}

// ─── Auto Hide Alerts ────────────────────────────────────────────────────
function initAutoHideAlerts() {
    const alerts = document.querySelectorAll('.alert-auto-hide');
    
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity 0.3s ease';
            setTimeout(() => alert.remove(), 300);
        }, CONFIG.autoHideAlert);
    });
}

// ─── Confirm Dialogs ─────────────────────────────────────────────────────
function initConfirmDialogs() {
    // Global confirm delete handler
    window.confirmDelete = function(id, name, type = 'surat') {
        const title = type === 'surat' ? 'Hapus Surat Kuasa?' : 'Hapus Pengguna?';
        const text = type === 'surat' 
            ? `Data atas nama <strong>${name}</strong> akan dihapus permanen.`
            : `Akses login untuk <strong>${name}</strong> akan dihapus.`;
        const url = type === 'surat' ? `hapus.php?id=${id}` : `hapus_user.php?id=${id}`;
        
        Swal.fire({
            title: title,
            html: text,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#DC2626',
            cancelButtonColor: '#64748B',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal',
            reverseButtons: true,
        }).then(result => {
            if (result.isConfirmed) {
                window.location.href = `${url}&_csrf=${getCsrfToken()}`;
            }
        });
    };

    // Global image viewer
    window.viewImage = function(src, title) {
        Swal.fire({
            title: title || 'Preview',
            imageUrl: src,
            imageAlt: title || 'Image',
            showCloseButton: true,
            showConfirmButton: false,
            width: 'auto',
            padding: '1.5rem',
            backdrop: 'rgba(0,0,0,.85)',
        });
    };
}

// ─── Tooltips ────────────────────────────────────────────────────────────
function initTooltips() {
    const tooltips = document.querySelectorAll('[data-tooltip]');
    
    tooltips.forEach(el => {
        el.addEventListener('mouseenter', function(e) {
            const tooltip = document.createElement('div');
            tooltip.className = 'custom-tooltip';
            tooltip.textContent = this.dataset.tooltip;
            tooltip.style.cssText = `
                position: absolute;
                background: var(--gray-900);
                color: #fff;
                padding: .4rem .8rem;
                border-radius: 6px;
                font-size: .75rem;
                z-index: 9999;
                pointer-events: none;
                white-space: nowrap;
            `;
            
            document.body.appendChild(tooltip);
            
            const rect = this.getBoundingClientRect();
            tooltip.style.top = `${rect.top - tooltip.offsetHeight - 5}px`;
            tooltip.style.left = `${rect.left + rect.width / 2 - tooltip.offsetWidth / 2}px`;
            
            this._tooltip = tooltip;
        });
        
        el.addEventListener('mouseleave', function() {
            if (this._tooltip) {
                this._tooltip.remove();
                this._tooltip = null;
            }
        });
    });
}

// ─── Utility Functions ───────────────────────────────────────────────────
function getCsrfToken() {
    const tokenInput = document.querySelector('input[name="_csrf"]');
    return tokenInput ? tokenInput.value : '';
}

function formatDate(dateString) {
    if (!dateString) return '-';
    const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 
                    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    const date = new Date(dateString);
    return `${date.getDate()} ${months[date.getMonth()]} ${date.getFullYear()}`;
}

function formatRupiah(angka) {
    return 'Rp ' + Number(angka).toLocaleString('id-ID');
}

function showLoading(show = true) {
    const existing = document.querySelector('.global-loading');
    if (show && !existing) {
        const loader = document.createElement('div');
        loader.className = 'global-loading';
        loader.innerHTML = '<div class="spinner"></div>';
        loader.style.cssText = `
            position: fixed;
            inset: 0;
            background: rgba(255,255,255,.8);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        `;
        document.body.appendChild(loader);
    } else if (!show && existing) {
        existing.remove();
    }
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// ─── Export to CSV ───────────────────────────────────────────────────────
function exportToCSV(data, filename = 'export.csv') {
    if (!data || data.length === 0) {
        Swal.fire('Tidak Ada Data', 'Tidak ada data yang bisa diexport.', 'info');
        return;
    }

    const csvContent = convertToCSV(data);
    const blob = new Blob(['\ufeff' + csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    
    link.setAttribute('href', url);
    link.setAttribute('download', filename);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

function convertToCSV(data) {
    const header = Object.keys(data[0]).join(',');
    const rows = data.map(row => 
        Object.values(row).map(value => 
            `"${String(value).replace(/"/g, '""')}"`
        ).join(',')
    );
    return [header, ...rows].join('\n');
}

// ─── Keyboard Shortcuts ──────────────────────────────────────────────────
document.addEventListener('keydown', (e) => {
    // Ctrl+K or / to focus search
    if ((e.ctrlKey && e.key === 'k') || (e.key === '/' && !e.ctrlKey && !e.metaKey)) {
        e.preventDefault();
        const searchInput = document.querySelector('.search-input');
        if (searchInput) searchInput.focus();
    }
    
    // Escape to close modals
    if (e.key === 'Escape') {
        const modals = document.querySelectorAll('.modal-overlay[style*="display: flex"]');
        modals.forEach(modal => {
            modal.style.display = 'none';
        });
    }
});