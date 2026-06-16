/**
 * SISKA BCA - Global JavaScript
 * Versi: 2.0
 */

document.addEventListener('DOMContentLoaded', function() {
    initSidebar();
    initAutoHideAlerts();
    initUploadZone();
    initRupiahFormat();
    initPasswordToggle();
    initFormValidation();
});

// ─── Sidebar ──────────────────────────────────────────────────────────────
function initSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const openBtn = document.getElementById('sidebarOpen');
    const closeBtn = document.getElementById('sidebarClose');

    if (openBtn) {
        openBtn.addEventListener('click', function() {
            sidebar.classList.add('open');
            overlay.classList.add('show');
            document.body.style.overflow = 'hidden';
        });
    }

    if (closeBtn) {
        closeBtn.addEventListener('click', closeSidebar);
    }

    if (overlay) {
        overlay.addEventListener('click', closeSidebar);
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeSidebar();
    });

    function closeSidebar() {
        sidebar.classList.remove('open');
        overlay.classList.remove('show');
        document.body.style.overflow = '';
    }
}

// ─── Auto Hide Alerts ────────────────────────────────────────────────────
function initAutoHideAlerts() {
    const alerts = document.querySelectorAll('.alert-auto-hide');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity 0.5s ease';
            setTimeout(function() {
                alert.style.display = 'none';
            }, 500);
        }, 5000);
    });
}

// ─── Upload Zone ─────────────────────────────────────────────────────────
function initUploadZone() {
    const zones = document.querySelectorAll('.upload-zone');
    
    zones.forEach(function(zone) {
        const input = zone.querySelector('input[type="file"]');
        const preview = zone.querySelector('.upload-preview');
        const placeholder = zone.querySelector('.upload-placeholder');

        if (!input) return;

        // Drag & Drop
        zone.addEventListener('dragover', function(e) {
            e.preventDefault();
            zone.classList.add('drag-over');
        });

        zone.addEventListener('dragleave', function() {
            zone.classList.remove('drag-over');
        });

        zone.addEventListener('drop', function(e) {
            e.preventDefault();
            zone.classList.remove('drag-over');
            if (e.dataTransfer.files.length > 0) {
                input.files = e.dataTransfer.files;
                handleFileSelect(input, preview, placeholder, zone);
            }
        });

        // File change
        input.addEventListener('change', function() {
            handleFileSelect(input, preview, placeholder, zone);
        });
    });
}

function handleFileSelect(input, preview, placeholder, zone) {
    const file = input.files[0];
    if (!file) return;

    // Validasi ukuran (10MB)
    if (file.size > 10 * 1024 * 1024) {
        alert('File terlalu besar! Maksimal 10 MB.');
        input.value = '';
        return;
    }

    // Preview gambar
    if (file.type.match(/^image\//)) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const img = preview.querySelector('img');
            if (img) {
                img.src = e.target.result;
                img.style.display = 'block';
            }
            preview.style.display = 'block';
            placeholder.style.display = 'none';
            zone.classList.add('has-file');
        };
        reader.readAsDataURL(file);
    } else {
        // File non-gambar
        placeholder.innerHTML = '<div class="upload-icon"><i class="bi bi-file-earmark-check-fill"></i></div>' +
            '<div class="upload-text" style="color:green;">File: ' + file.name + '</div>' +
            '<div class="upload-hint">' + (file.size / 1024).toFixed(1) + ' KB</div>';
        preview.style.display = 'none';
        zone.classList.add('has-file');
    }
}

// ─── Rupiah Format ──────────────────────────────────────────────────────
function initRupiahFormat() {
    document.querySelectorAll('[data-rupiah]').forEach(function(el) {
        el.addEventListener('input', function() {
            var raw = this.value.replace(/\D/g, '');
            this.value = raw ? Number(raw).toLocaleString('id-ID') : '';
        });
        el.addEventListener('focus', function() {
            this.select();
        });
    });
}

// ─── Password Toggle ────────────────────────────────────────────────────
function initPasswordToggle() {
    const toggleBtns = document.querySelectorAll('[data-toggle-password]');
    
    toggleBtns.forEach(function(btn) {
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

// ─── Form Validation ────────────────────────────────────────────────────
function initFormValidation() {
    const forms = document.querySelectorAll('form[novalidate]');
    
    forms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
            const requiredFields = this.querySelectorAll('input[required], select[required], textarea[required]');
            let isValid = true;

            requiredFields.forEach(function(field) {
                if (!field.value.trim()) {
                    field.classList.add('is-invalid');
                    isValid = false;
                } else {
                    field.classList.remove('is-invalid');
                }
            });

            if (!isValid) {
                e.preventDefault();
                // Scroll ke error pertama
                const firstError = this.querySelector('.is-invalid');
                if (firstError) {
                    firstError.focus();
                }
            }
        });

        // Real-time validation
        form.querySelectorAll('input[required], select[required], textarea[required]').forEach(function(field) {
            field.addEventListener('blur', function() {
                if (!this.value.trim()) {
                    this.classList.add('is-invalid');
                } else {
                    this.classList.remove('is-invalid');
                }
            });
            field.addEventListener('input', function() {
                if (this.value.trim()) {
                    this.classList.remove('is-invalid');
                }
            });
        });
    });
}