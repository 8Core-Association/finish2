// Modern Archive Management System
class ArchiveManager {
    constructor() {
        this.currentAction = null;
        this.currentId = null;
        this.init();
    }

    init() {
        this.bindEvents();
        this.setupModal();
        this.setupToast();
        this.setupFilters();
        this.setupSearch();
    }

    bindEvents() {
        // Restore buttons
        document.querySelectorAll('.restore-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const id = e.currentTarget.dataset.id;
                this.showConfirmModal(
                    'Vrati iz arhive',
                    'Jeste li sigurni da želite vratiti ovaj predmet iz arhive? Predmet će biti uklonjen iz arhive.',
                    () => this.restoreItem(id),
                    'success'
                );
            });
        });

        // Delete buttons (mark as deleted)
        document.querySelectorAll('.delete-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const id = e.currentTarget.dataset.id;
                this.showConfirmModal(
                    'Sakrij iz arhive',
                    'Jeste li sigurni da želite sakriti ovaj arhivski zapis? Zapis će biti označen kao obrisan.',
                    () => this.deleteItem(id),
                    'warning'
                );
            });
        });

        // Permanent delete buttons
        document.querySelectorAll('.permanent-delete-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const id = e.currentTarget.dataset.id;
                this.showConfirmModal(
                    'Trajno brisanje',
                    'Jeste li sigurni da želite trajno obrisati ovaj arhivski zapis? Ova akcija se ne može poništiti!',
                    () => this.permanentDeleteItem(id),
                    'danger'
                );
            });
        });

        // Refresh button
        const refreshBtn = document.getElementById('refreshBtn');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', () => {
                this.refreshPage();
            });
        }

        // Export button
        const exportBtn = document.getElementById('exportBtn');
        if (exportBtn) {
            exportBtn.addEventListener('click', () => {
                this.exportData();
            });
        }
    }

    setupModal() {
        const modal = document.getElementById('confirmModal');
        const closeBtn = modal.querySelector('.modal-close');
        const cancelBtn = document.getElementById('modalCancel');
        const confirmBtn = document.getElementById('modalConfirm');

        // Close modal events
        [closeBtn, cancelBtn].forEach(btn => {
            btn.addEventListener('click', () => this.hideModal());
        });

        // Confirm button
        confirmBtn.addEventListener('click', () => {
            if (this.currentAction) {
                this.currentAction();
                this.hideModal();
            }
        });

        // Close on backdrop click
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                this.hideModal();
            }
        });

        // Close on Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && modal.style.display === 'flex') {
                this.hideModal();
            }
        });
    }

    setupToast() {
        const toast = document.getElementById('toast');
        const closeBtn = toast.querySelector('.toast-close');
        
        if (closeBtn) {
            closeBtn.addEventListener('click', () => {
                this.hideToast();
            });
        }

        // Auto hide toast after 5 seconds
        this.toastTimeout = null;
    }

    setupFilters() {
        const postupakFilter = document.getElementById('postupakFilter');
        const sortBy = document.getElementById('sortBy');

        if (postupakFilter) {
            postupakFilter.addEventListener('change', () => {
                this.applyFilters();
            });
        }

        if (sortBy) {
            sortBy.addEventListener('change', () => {
                this.applySorting();
            });
        }
    }

    setupSearch() {
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            let searchTimeout;
            searchInput.addEventListener('input', (e) => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    this.performSearch(e.target.value);
                }, 300);
            });
        }
    }

    showConfirmModal(title, message, action, type = 'primary') {
        const modal = document.getElementById('confirmModal');
        const modalTitle = document.getElementById('modalTitle');
        const modalMessage = document.getElementById('modalMessage');
        const modalIcon = document.getElementById('modalIcon');
        const confirmBtn = document.getElementById('modalConfirm');

        modalTitle.textContent = title;
        modalMessage.textContent = message;
        this.currentAction = action;

        // Set icon and button style based on type
        const iconClass = {
            'success': 'fas fa-check-circle',
            'warning': 'fas fa-exclamation-triangle',
            'danger': 'fas fa-trash-alt',
            'primary': 'fas fa-question-circle'
        };

        const buttonClass = {
            'success': 'btn-success',
            'warning': 'btn-warning',
            'danger': 'btn-danger',
            'primary': 'btn-primary'
        };

        modalIcon.innerHTML = `<i class="${iconClass[type]}"></i>`;
        
        // Reset button classes
        confirmBtn.className = 'btn';
        confirmBtn.classList.add(buttonClass[type]);

        modal.style.display = 'flex';
        modal.classList.add('show');
        
        // Focus on confirm button
        setTimeout(() => confirmBtn.focus(), 100);
    }

    hideModal() {
        const modal = document.getElementById('confirmModal');
        modal.classList.remove('show');
        setTimeout(() => {
            modal.style.display = 'none';
            this.currentAction = null;
        }, 300);
    }

    showToast(title, message, type = 'success') {
        const toast = document.getElementById('toast');
        const toastTitle = toast.querySelector('.toast-title');
        const toastMessage = toast.querySelector('.toast-message');
        const toastIcon = toast.querySelector('.toast-icon i');

        toastTitle.textContent = title;
        toastMessage.textContent = message;

        // Reset classes
        toast.className = 'toast';
        toast.classList.add(type);

        // Set icon
        const iconClass = {
            'success': 'fas fa-check-circle',
            'error': 'fas fa-exclamation-circle',
            'warning': 'fas fa-exclamation-triangle',
            'info': 'fas fa-info-circle'
        };

        toastIcon.className = iconClass[type] || iconClass.success;

        // Show toast
        toast.classList.add('show');

        // Auto hide after 5 seconds
        clearTimeout(this.toastTimeout);
        this.toastTimeout = setTimeout(() => {
            this.hideToast();
        }, 5000);
    }

    hideToast() {
        const toast = document.getElementById('toast');
        toast.classList.remove('show');
        clearTimeout(this.toastTimeout);
    }

    showLoading() {
        const overlay = document.getElementById('loadingOverlay');
        overlay.style.display = 'flex';
    }

    hideLoading() {
        const overlay = document.getElementById('loadingOverlay');
        overlay.style.display = 'none';
    }

    async restoreItem(id) {
        this.showLoading();
        
        try {
            const formData = new FormData();
            formData.append('action', 'restore');
            formData.append('id', id);

            const response = await fetch(window.location.href, {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                this.showToast('Uspjeh', result.message, 'success');
                // Remove row from table
                this.removeTableRow(id);
                this.updateStats();
            } else {
                this.showToast('Greška', result.message, 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            this.showToast('Greška', 'Dogodila se neočekivana greška.', 'error');
        } finally {
            this.hideLoading();
        }
    }

    async deleteItem(id) {
        this.showLoading();
        
        try {
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id', id);

            const response = await fetch(window.location.href, {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                this.showToast('Uspjeh', result.message, 'success');
                // Remove row from table
                this.removeTableRow(id);
                this.updateStats();
            } else {
                this.showToast('Greška', result.message, 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            this.showToast('Greška', 'Dogodila se neočekivana greška.', 'error');
        } finally {
            this.hideLoading();
        }
    }

    async permanentDeleteItem(id) {
        this.showLoading();
        
        try {
            const formData = new FormData();
            formData.append('action', 'permanent_delete');
            formData.append('id', id);

            const response = await fetch(window.location.href, {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                this.showToast('Uspjeh', result.message, 'success');
                // Remove row from table
                this.removeTableRow(id);
                this.updateStats();
            } else {
                this.showToast('Greška', result.message, 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            this.showToast('Greška', 'Dogodila se neočekivana greška.', 'error');
        } finally {
            this.hideLoading();
        }
    }

    removeTableRow(id) {
        const row = document.querySelector(`tr[data-id="${id}"]`);
        if (row) {
            row.style.transition = 'all 0.3s ease';
            row.style.opacity = '0';
            row.style.transform = 'translateX(-100%)';
            
            setTimeout(() => {
                row.remove();
                this.checkEmptyState();
            }, 300);
        }
    }

    updateStats() {
        // Update statistics - this would need to be implemented based on your needs
        // You might want to make another AJAX call to get updated stats
        const activeCount = document.querySelectorAll('.table-row').length - 1; // -1 for the removed row
        const statNumber = document.querySelector('.stat-card.primary .stat-number');
        if (statNumber) {
            statNumber.textContent = Math.max(0, activeCount);
        }
    }

    checkEmptyState() {
        const tbody = document.querySelector('.modern-table tbody');
        const mainContent = document.querySelector('.main-content');
        
        if (tbody && tbody.children.length === 0) {
            mainContent.innerHTML = `
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-archive"></i>
                    </div>
                    <h3>Nema arhiviranih predmeta</h3>
                    <p>Trenutno nema predmeta u arhivi. Arhivirani predmeti će se prikazati ovdje.</p>
                </div>
            `;
        }
    }

    refreshPage() {
        this.showLoading();
        setTimeout(() => {
            window.location.reload();
        }, 500);
    }

    exportData() {
        this.showToast('Info', 'Funkcija izvoza će biti implementirana uskoro.', 'info');
    }

    performSearch(query) {
        const rows = document.querySelectorAll('.table-row');
        const searchTerm = query.toLowerCase().trim();

        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            const shouldShow = searchTerm === '' || text.includes(searchTerm);
            
            row.style.display = shouldShow ? '' : 'none';
            
            if (shouldShow && searchTerm !== '') {
                row.classList.add('fade-in');
                setTimeout(() => row.classList.remove('fade-in'), 500);
            }
        });

        this.updateVisibleCount();
    }

    applyFilters() {
        const postupakFilter = document.getElementById('postupakFilter');
        const rows = document.querySelectorAll('.table-row');
        const selectedPostupak = postupakFilter.value;

        rows.forEach(row => {
            const postupakBadge = row.querySelector('.procedure-badge');
            const postupakValue = postupakBadge ? postupakBadge.className.split(' ').find(cls => cls.startsWith('procedure-')) : '';
            
            const shouldShow = selectedPostupak === '' || postupakValue.includes(selectedPostupak);
            row.style.display = shouldShow ? '' : 'none';
        });

        this.updateVisibleCount();
    }

    applySorting() {
        const sortBy = document.getElementById('sortBy');
        const tbody = document.querySelector('.modern-table tbody');
        const rows = Array.from(tbody.querySelectorAll('.table-row'));
        const sortValue = sortBy.value;

        rows.sort((a, b) => {
            switch (sortValue) {
                case 'datum_asc':
                    return new Date(a.querySelector('.date-main').textContent.split('.').reverse().join('-')) - 
                           new Date(b.querySelector('.date-main').textContent.split('.').reverse().join('-'));
                case 'datum_desc':
                    return new Date(b.querySelector('.date-main').textContent.split('.').reverse().join('-')) - 
                           new Date(a.querySelector('.date-main').textContent.split('.').reverse().join('-'));
                case 'naziv_asc':
                    return a.querySelector('.subject-name').textContent.localeCompare(b.querySelector('.subject-name').textContent);
                case 'naziv_desc':
                    return b.querySelector('.subject-name').textContent.localeCompare(a.querySelector('.subject-name').textContent);
                default:
                    return 0;
            }
        });

        // Re-append sorted rows
        rows.forEach(row => tbody.appendChild(row));
    }

    updateVisibleCount() {
        const visibleRows = document.querySelectorAll('.table-row:not([style*="display: none"])');
        const tableHeader = document.querySelector('.table-header h3');
        
        if (tableHeader) {
            const originalText = tableHeader.textContent.split('(')[0].trim();
            tableHeader.innerHTML = `
                <i class="fas fa-list"></i>
                ${originalText} (${visibleRows.length})
            `;
        }
    }

    // Utility methods
    formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('hr-HR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        });
    }

    formatTime(dateString) {
        const date = new Date(dateString);
        return date.toLocaleTimeString('hr-HR', {
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    truncateText(text, maxLength) {
        return text.length > maxLength ? text.substring(0, maxLength) + '...' : text;
    }
}

// Initialize the archive manager when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new ArchiveManager();
});

// Add some nice animations on page load
window.addEventListener('load', () => {
    const elements = document.querySelectorAll('.stat-card, .table-row');
    elements.forEach((el, index) => {
        setTimeout(() => {
            el.classList.add('fade-in');
        }, index * 50);
    });
});