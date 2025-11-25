/**
 * Predmet Sortiranje JavaScript
 * (c) 2025 8Core Association
 */

class PredmetSortiranje {
    constructor() {
        this.selectedDocuments = new Set();
        this.nedodjeljeneDokumenti = [];
        this.availableAkti = [];
        this.init();
    }

    init() {
        this.setupEventListeners();
    }

    setupEventListeners() {
        // Main sortiranje button
        const sortirajBtn = document.getElementById('sortirajNedodjeljenoBtn');
        if (sortirajBtn) {
            sortirajBtn.addEventListener('click', () => this.openSortiranjeModal());
        }
    }

    async openSortiranjeModal() {
        try {
            // Show loading state
            const sortirajBtn = document.getElementById('sortirajNedodjeljenoBtn');
            sortirajBtn.classList.add('seup-loading');

            // Create modal if it doesn't exist
            this.createSortiranjeModal();
            
            // Load unassigned documents
            await this.loadNedodjeljeneDokumente();
            
            // Show modal
            const modal = document.getElementById('sortiranjeModal');
            modal.classList.add('show');

        } catch (error) {
            console.error('Error opening sortiranje modal:', error);
            this.showMessage('Greška pri učitavanju nedodjeljenih dokumenata', 'error');
        } finally {
            const sortirajBtn = document.getElementById('sortirajNedodjeljenoBtn');
            sortirajBtn.classList.remove('seup-loading');
        }
    }

    createSortiranjeModal() {
        // Check if modal already exists
        if (document.getElementById('sortiranjeModal')) {
            return;
        }

        const modalHTML = `
            <div class="seup-modal seup-sortiranje-modal" id="sortiranjeModal">
                <div class="seup-modal-content">
                    <div class="seup-modal-header">
                        <h5 class="seup-modal-title">
                            <i class="fas fa-sort me-2"></i>Sortiranje Nedodjeljenih Dokumenata
                        </h5>
                        <button type="button" class="seup-modal-close" id="closeSortiranjeModal">&times;</button>
                    </div>
                    <div class="seup-modal-body">
                        <div id="sortiranjeContent">
                            <div class="seup-sortiranje-loading">
                                <i class="fas fa-spinner seup-loading-spinner"></i>
                                <div class="seup-loading-text">Učitavam nedodjeljene dokumente...</div>
                            </div>
                        </div>
                    </div>
                    <div class="seup-modal-footer">
                        <button type="button" class="seup-btn seup-btn-secondary" id="cancelSortiranjeBtn">
                            Odustani
                        </button>
                        <button type="button" class="seup-btn seup-btn-primary" id="applySortiranjeBtn">
                            <i class="fas fa-check me-2"></i>Primijeni Sortiranje
                        </button>
                    </div>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', modalHTML);
        this.setupModalEventListeners();
    }

    setupModalEventListeners() {
        // Close modal events
        document.getElementById('closeSortiranjeModal').addEventListener('click', () => this.closeSortiranjeModal());
        document.getElementById('cancelSortiranjeBtn').addEventListener('click', () => this.closeSortiranjeModal());
        
        // Apply sortiranje
        document.getElementById('applySortiranjeBtn').addEventListener('click', () => this.applySortiranje());
        
        // Close when clicking outside
        document.getElementById('sortiranjeModal').addEventListener('click', (e) => {
            if (e.target.id === 'sortiranjeModal') {
                this.closeSortiranjeModal();
            }
        });
    }

    async loadNedodjeljeneDokumente() {
        try {
            const predmetId = this.getPredmetId();
            
            const formData = new FormData();
            formData.append('action', 'get_nedodjeljeni');
            formData.append('predmet_id', predmetId);

            const response = await fetch('', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();
            
            if (data.success) {
                this.nedodjeljeneDokumenti = data.documents;
                this.availableAkti = data.available_akti || [];
                this.renderNedodjeljeneDokumente();
            } else {
                throw new Error(data.error || 'Unknown error');
            }

        } catch (error) {
            console.error('Error loading documents:', error);
            this.renderError('Greška pri učitavanju dokumenata: ' + error.message);
        }
    }

    renderNedodjeljeneDokumente() {
        const content = document.getElementById('sortiranjeContent');
        
        if (this.nedodjeljeneDokumenti.length === 0) {
            content.innerHTML = this.renderEmptyState();
            return;
        }

        let html = `
            <div class="seup-sortiranje-header">
                <div class="seup-sortiranje-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="seup-sortiranje-info">
                    <h4>Pronađeno ${this.nedodjeljeneDokumenti.length} nedodjeljenih dokumenata</h4>
                    <p>Odaberite kako želite sortirati ove dokumente u akti ili prilozi</p>
                </div>
            </div>

            <div class="seup-nedodjeljeni-list">
                <div class="seup-nedodjeljeni-header">
                    <div class="seup-nedodjeljeni-title">
                        <i class="fas fa-file-alt"></i>
                        Nedodjeljeni Dokumenti
                    </div>
                    <div class="seup-select-all-container">
                        <input type="checkbox" id="selectAllDocs" class="seup-select-all-checkbox">
                        <label for="selectAllDocs" class="seup-select-all-label">Odaberi sve</label>
                    </div>
                </div>
        `;

        this.nedodjeljeneDokumenti.forEach((doc, index) => {
            html += this.renderDocumentItem(doc, index);
        });

        html += `
            </div>
            
            <div class="seup-sortiranje-actions">
                <div class="seup-selection-summary">
                    <span class="seup-selection-count" id="selectionCount">0</span>
                    <span>dokumenata odabrano</span>
                </div>
                <div class="seup-bulk-actions">
                    <button type="button" class="seup-btn-bulk seup-btn-auto-akt" id="bulkAssignAkt">
                        <i class="fas fa-file-alt me-2"></i>Sve kao Akti
                    </button>
                    <button type="button" class="seup-btn-bulk seup-btn-auto-prilog" id="bulkAssignPrilog">
                        <i class="fas fa-paperclip me-2"></i>Sve kao Prilozi
                    </button>
                </div>
            </div>
        `;

        content.innerHTML = html;
        this.setupDocumentEventListeners();
    }

    renderDocumentItem(doc, index) {
        const fileExtension = this.getFileExtension(doc.filename);
        const fileIcon = this.getFileIcon(fileExtension);
        const fileSize = this.formatFileSize(doc.file_size || 0);
        const dateFormatted = this.formatDate(doc.date_c);

        return `
            <div class="seup-document-item" data-doc-id="${doc.rowid}">
                <input type="checkbox" class="seup-document-checkbox" data-doc-id="${doc.rowid}">
                
                <div class="seup-document-info">
                    <div class="seup-document-file-icon ${fileExtension}">
                        <i class="${fileIcon}"></i>
                    </div>
                    <div class="seup-document-details">
                        <div class="seup-document-filename">${this.escapeHtml(doc.filename)}</div>
                        <div class="seup-document-meta">
                            <div class="seup-document-meta-item">
                                <i class="fas fa-weight"></i>
                                <span>${fileSize}</span>
                            </div>
                            <div class="seup-document-meta-item">
                                <i class="fas fa-calendar"></i>
                                <span>${dateFormatted}</span>
                            </div>
                            <div class="seup-document-meta-item">
                                <i class="fas fa-user"></i>
                                <span>${this.escapeHtml(doc.created_by || 'N/A')}</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="seup-assignment-controls">
                    <select class="seup-assignment-select" data-doc-id="${doc.rowid}">
                        <option value="skip">Preskoči</option>
                        <option value="akt">Dodijeli kao Akt</option>
                        <option value="prilog">Dodijeli kao Prilog</option>
                    </select>
                    <select class="seup-akt-select" data-doc-id="${doc.rowid}" style="display: none;">
                        <option value="">-- Odaberi akt --</option>
                        ${this.renderAktOptions()}
                    </select>
                </div>
            </div>
        `;
    }

    renderAktOptions() {
        return this.availableAkti.map(akt => 
            `<option value="${akt.ID_akta}">Akt ${akt.urb_broj} - ${this.escapeHtml(akt.filename)}</option>`
        ).join('');
    }

    renderEmptyState() {
        return `
            <div class="seup-nedodjeljeni-empty">
                <i class="fas fa-check-circle seup-empty-success-icon"></i>
                <h4 class="seup-empty-success-title">Svi dokumenti su sortirani!</h4>
                <p class="seup-empty-success-description">
                    Nema nedodjeljenih dokumenata. Svi dokumenti su dodijeljeni kao akti ili prilozi.
                </p>
            </div>
        `;
    }

    renderError(errorMessage) {
        const content = document.getElementById('sortiranjeContent');
        content.innerHTML = `
            <div class="seup-alert seup-alert-error">
                <i class="fas fa-exclamation-triangle me-2"></i>
                ${errorMessage}
            </div>
        `;
    }

    setupDocumentEventListeners() {
        // Select all checkbox
        const selectAllCheckbox = document.getElementById('selectAllDocs');
        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', (e) => {
                this.toggleSelectAll(e.target.checked);
            });
        }

        // Individual document checkboxes
        document.querySelectorAll('.seup-document-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', (e) => {
                this.toggleDocumentSelection(e.target.dataset.docId, e.target.checked);
            });
        });

        // Assignment selects
        document.querySelectorAll('.seup-assignment-select').forEach(select => {
            select.addEventListener('change', (e) => {
                this.handleAssignmentChange(e.target.dataset.docId, e.target.value);
            });
        });

        // Bulk action buttons
        const bulkAktBtn = document.getElementById('bulkAssignAkt');
        const bulkPrilogBtn = document.getElementById('bulkAssignPrilog');

        if (bulkAktBtn) {
            bulkAktBtn.addEventListener('click', () => this.bulkAssignAsAkt());
        }

        if (bulkPrilogBtn) {
            bulkPrilogBtn.addEventListener('click', () => this.bulkAssignAsPrilog());
        }
    }

    toggleSelectAll(checked) {
        document.querySelectorAll('.seup-document-checkbox').forEach(checkbox => {
            checkbox.checked = checked;
            this.toggleDocumentSelection(checkbox.dataset.docId, checked);
        });
    }

    toggleDocumentSelection(docId, selected) {
        const item = document.querySelector(`[data-doc-id="${docId}"].seup-document-item`);
        
        if (selected) {
            this.selectedDocuments.add(docId);
            item.classList.add('selected');
        } else {
            this.selectedDocuments.delete(docId);
            item.classList.remove('selected');
        }

        this.updateSelectionCount();
    }

    updateSelectionCount() {
        const countElement = document.getElementById('selectionCount');
        if (countElement) {
            countElement.textContent = this.selectedDocuments.size;
        }
    }

    handleAssignmentChange(docId, action) {
        const aktSelect = document.querySelector(`[data-doc-id="${docId}"].seup-akt-select`);
        
        if (action === 'prilog') {
            aktSelect.style.display = 'block';
            aktSelect.classList.add('show');
        } else {
            aktSelect.style.display = 'none';
            aktSelect.classList.remove('show');
        }
    }

    bulkAssignAsAkt() {
        if (this.selectedDocuments.size === 0) {
            this.showMessage('Molimo odaberite dokumente', 'error');
            return;
        }

        // Set all selected documents to 'akt'
        this.selectedDocuments.forEach(docId => {
            const select = document.querySelector(`[data-doc-id="${docId}"].seup-assignment-select`);
            if (select) {
                select.value = 'akt';
                this.handleAssignmentChange(docId, 'akt');
            }
        });

        this.showMessage(`${this.selectedDocuments.size} dokumenata će biti dodijeljeno kao akti`, 'success');
    }

    bulkAssignAsPrilog() {
        if (this.selectedDocuments.size === 0) {
            this.showMessage('Molimo odaberite dokumente', 'error');
            return;
        }

        if (this.availableAkti.length === 0) {
            this.showMessage('Nema dostupnih akata za dodjeljivanje priloga', 'error');
            return;
        }

        // Set all selected documents to 'prilog' and show akt selects
        this.selectedDocuments.forEach(docId => {
            const select = document.querySelector(`[data-doc-id="${docId}"].seup-assignment-select`);
            if (select) {
                select.value = 'prilog';
                this.handleAssignmentChange(docId, 'prilog');
            }
        });

        this.showMessage(`${this.selectedDocuments.size} dokumenata će biti dodijeljeno kao prilozi`, 'success');
    }

    async applySortiranje() {
        try {
            const assignments = this.collectAssignments();
            
            if (assignments.length === 0) {
                this.showMessage('Nema dokumenata za sortiranje', 'error');
                return;
            }

            // Show progress
            this.showProgress(true);
            const applyBtn = document.getElementById('applySortiranjeBtn');
            applyBtn.classList.add('seup-loading');

            const formData = new FormData();
            formData.append('action', 'bulk_assign_documents');
            formData.append('predmet_id', this.getPredmetId());
            formData.append('assignments', JSON.stringify(assignments));

            const response = await fetch('', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                this.showMessage(data.message, 'success');
                this.closeSortiranjeModal();
                
                // Reload page to show updated documents
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            } else {
                this.showMessage('Greška pri sortiranju: ' + data.error, 'error');
            }

        } catch (error) {
            console.error('Error applying sortiranje:', error);
            this.showMessage('Došlo je do greške pri sortiranju', 'error');
        } finally {
            this.showProgress(false);
            const applyBtn = document.getElementById('applySortiranjeBtn');
            applyBtn.classList.remove('seup-loading');
        }
    }

    collectAssignments() {
        const assignments = [];

        document.querySelectorAll('.seup-assignment-select').forEach(select => {
            const docId = select.dataset.docId;
            const action = select.value;
            
            if (action !== 'skip') {
                const assignment = {
                    ecm_file_id: docId,
                    action: action
                };

                if (action === 'prilog') {
                    const aktSelect = document.querySelector(`[data-doc-id="${docId}"].seup-akt-select`);
                    assignment.akt_id = aktSelect ? aktSelect.value : null;
                    
                    if (!assignment.akt_id) {
                        this.showMessage(`Molimo odaberite akt za prilog: ${this.getDocumentName(docId)}`, 'error');
                        return [];
                    }
                }

                assignments.push(assignment);
            }
        });

        return assignments;
    }

    showProgress(show) {
        let progressDiv = document.getElementById('sortiranjeProgress');
        
        if (show && !progressDiv) {
            const progressHTML = `
                <div class="seup-sortiranje-progress" id="sortiranjeProgress">
                    <div class="seup-progress-bar">
                        <div class="seup-progress-fill" id="sortiranjeProgressFill"></div>
                    </div>
                    <div class="seup-progress-text" id="sortiranjeProgressText">Obrađujem sortiranje...</div>
                </div>
            `;
            
            const content = document.getElementById('sortiranjeContent');
            content.insertAdjacentHTML('afterbegin', progressHTML);
        } else if (!show && progressDiv) {
            progressDiv.remove();
        }
    }

    closeSortiranjeModal() {
        const modal = document.getElementById('sortiranjeModal');
        modal.classList.remove('show');
        
        // Reset state
        this.selectedDocuments.clear();
        this.nedodjeljeneDokumenti = [];
        this.availableAkti = [];
    }

    // Utility methods
    getPredmetId() {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get('id') || '0';
    }

    getDocumentName(docId) {
        const doc = this.nedodjeljeneDokumenti.find(d => d.rowid == docId);
        return doc ? doc.filename : 'Unknown';
    }

    getFileExtension(filename) {
        const ext = filename.split('.').pop().toLowerCase();
        const extMap = {
            'pdf': 'pdf',
            'doc': 'doc', 'docx': 'doc',
            'xls': 'xls', 'xlsx': 'xls',
            'jpg': 'img', 'jpeg': 'img', 'png': 'img', 'gif': 'img'
        };
        return extMap[ext] || 'default';
    }

    getFileIcon(extension) {
        const iconMap = {
            'pdf': 'fas fa-file-pdf',
            'doc': 'fas fa-file-word',
            'xls': 'fas fa-file-excel',
            'img': 'fas fa-file-image',
            'default': 'fas fa-file'
        };
        return iconMap[extension] || iconMap['default'];
    }

    formatFileSize(bytes) {
        if (bytes === 0) return '0 B';
        const units = ['B', 'KB', 'MB', 'GB'];
        const factor = Math.floor(Math.log(bytes) / Math.log(1024));
        return (bytes / Math.pow(1024, factor)).toFixed(1) + ' ' + units[factor];
    }

    formatDate(timestamp) {
        if (!timestamp) return 'N/A';
        const date = new Date(timestamp * 1000);
        return date.toLocaleDateString('hr-HR') + ' ' + date.toLocaleTimeString('hr-HR', {
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    showMessage(message, type = 'success', duration = 5000) {
        // Use existing showMessage function if available
        if (window.showMessage) {
            window.showMessage(message, type, duration);
            return;
        }

        // Fallback implementation
        let messageEl = document.querySelector('.seup-message-toast');
        if (!messageEl) {
            messageEl = document.createElement('div');
            messageEl.className = 'seup-message-toast';
            document.body.appendChild(messageEl);
        }

        messageEl.className = `seup-message-toast seup-message-${type} show`;
        messageEl.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>
            ${message}
        `;

        setTimeout(() => {
            messageEl.classList.remove('show');
        }, duration);
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    new PredmetSortiranje();
});

// Export for potential external use
window.PredmetSortiranje = PredmetSortiranje;