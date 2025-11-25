/**
 * Suradnici JavaScript
 * (c) 2025 8Core Association
 */

class SuradniciManager {
    constructor() {
        this.currentSuradnikId = null;
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.setupOIBValidation();
    }

    setupEventListeners() {
        // Export buttons
        this.setupExportHandlers();
        
        // Modal functionality
        this.setupModalHandlers();
        
        // Search functionality
        this.setupSearchHandlers();
    }

    setupExportHandlers() {
        const exportCSVBtn = document.getElementById('exportCSVBtn');
        const exportExcelBtn = document.getElementById('exportExcelBtn');

        if (exportCSVBtn) {
            exportCSVBtn.addEventListener('click', () => this.exportData('csv'));
        }

        if (exportExcelBtn) {
            exportExcelBtn.addEventListener('click', () => this.exportData('excel'));
        }
    }

    setupModalHandlers() {
        // Close modal events
        const closeModalBtn = document.getElementById('closeDetailsModal');
        const closeDetailsBtn = document.getElementById('closeDetailsBtn');
        const modal = document.getElementById('detailsModal');

        if (closeModalBtn) {
            closeModalBtn.addEventListener('click', () => this.closeDetailsModal());
        }

        if (closeDetailsBtn) {
            closeDetailsBtn.addEventListener('click', () => this.closeDetailsModal());
        }

        // Close when clicking outside
        if (modal) {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    this.closeDetailsModal();
                }
            });
        }

        // Clickable names
        document.querySelectorAll('.clickable-name').forEach(nameCell => {
            nameCell.addEventListener('click', (e) => {
                const id = e.target.closest('.clickable-name').dataset.id;
                this.openDetailsModal(id);
            });
        });

        // View buttons
        document.querySelectorAll('.seup-btn-view').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const id = e.target.closest('.seup-btn-view').dataset.id;
                this.openDetailsModal(id);
            });
        });
        
        // VCF buttons
        document.querySelectorAll('.seup-btn-vcf').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const id = e.target.closest('.seup-btn-vcf').dataset.id;
                this.exportVCF(id, e.target.closest('.seup-btn-vcf'));
            });
        });
    }

    setupSearchHandlers() {
        const searchNaziv = document.getElementById('searchNaziv');
        const searchOIB = document.getElementById('searchOIB');

        if (searchNaziv) {
            searchNaziv.addEventListener('input', this.debounce(() => this.filterTable(), 300));
        }

        if (searchOIB) {
            searchOIB.addEventListener('input', this.debounce(() => this.filterTable(), 300));
        }
    }

    setupOIBValidation() {
        const searchOIB = document.getElementById('searchOIB');
        
        if (searchOIB) {
            searchOIB.addEventListener('input', function() {
                // Allow only numbers
                this.value = this.value.replace(/[^0-9]/g, '');
                
                // Limit to 11 characters
                if (this.value.length > 11) {
                    this.value = this.value.substring(0, 11);
                }
            });
        }
    }

    async exportData(format) {
        try {
            const btn = document.getElementById(`export${format.toUpperCase()}Btn`);
            btn.classList.add('seup-loading');

            const formData = new FormData();
            formData.append('action', `export_${format}`);

            const response = await fetch('', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                // Create download link
                const link = document.createElement('a');
                link.href = data.download_url;
                link.download = data.filename;
                link.style.display = 'none';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);

                this.showMessage(`${format.toUpperCase()} datoteka je pripremljena za preuzimanje (${data.records_count} zapisa)`, 'success');
            } else {
                this.showMessage(`Greška pri kreiranju ${format.toUpperCase()} datoteke: ` + data.error, 'error');
            }

        } catch (error) {
            console.error('Export error:', error);
            this.showMessage('Došlo je do greške pri izvozu', 'error');
        } finally {
            const btn = document.getElementById(`export${format.toUpperCase()}Btn`);
            btn.classList.remove('seup-loading');
        }
    }

    async openDetailsModal(suradnikId) {
        try {
            this.currentSuradnikId = suradnikId;
            
            // Show modal
            const modal = document.getElementById('detailsModal');
            modal.classList.add('show');
            
            // Load details
            await this.loadSuradnikDetails(suradnikId);

        } catch (error) {
            console.error('Error opening details modal:', error);
            this.showMessage('Greška pri učitavanju detalja', 'error');
        }
    }

    closeDetailsModal() {
        const modal = document.getElementById('detailsModal');
        modal.classList.remove('show');
        this.currentSuradnikId = null;
    }

    async loadSuradnikDetails(suradnikId) {
        const content = document.getElementById('suradnikDetailsContent');
        content.innerHTML = '<div class="seup-loading-message"><i class="fas fa-spinner fa-spin"></i> Učitavam detalje...</div>';
        
        try {
            const formData = new FormData();
            formData.append('action', 'get_suradnik_details');
            formData.append('rowid', suradnikId);
            
            const response = await fetch('', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                this.renderSuradnikDetails(data.suradnik);
            } else {
                content.innerHTML = '<div class="seup-alert seup-alert-error"><i class="fas fa-exclamation-triangle me-2"></i>' + data.error + '</div>';
            }

        } catch (error) {
            console.error('Error loading details:', error);
            content.innerHTML = '<div class="seup-alert seup-alert-error"><i class="fas fa-exclamation-triangle me-2"></i>Greška pri učitavanju detalja</div>';
        }
    }

    renderSuradnikDetails(suradnik) {
        const content = document.getElementById('suradnikDetailsContent');
        
        let html = '<div class="seup-suradnik-details">';
        
        // Header with name and basic info
        html += '<div class="seup-details-header">';
        html += '<div class="seup-details-avatar"><i class="fas fa-user"></i></div>';
        html += '<div class="seup-details-basic">';
        html += '<h4>' + this.escapeHtml(suradnik.naziv) + '</h4>';
        if (suradnik.kontakt_osoba) {
            html += '<p class="seup-contact-person">Kontakt: ' + this.escapeHtml(suradnik.kontakt_osoba) + '</p>';
        }
        html += '</div>';
        html += '</div>';
        
        // Details grid
        html += '<div class="seup-details-grid">';
        
        // OIB
        html += '<div class="seup-detail-item">';
        html += '<div class="seup-detail-label"><i class="fas fa-id-card me-2"></i>OIB</div>';
        html += '<div class="seup-detail-value">' + (suradnik.oib || '—') + '</div>';
        html += '</div>';
        
        // Telefon
        html += '<div class="seup-detail-item">';
        html += '<div class="seup-detail-label"><i class="fas fa-phone me-2"></i>Telefon</div>';
        html += '<div class="seup-detail-value">';
        if (suradnik.telefon) {
            html += '<a href="tel:' + this.escapeHtml(suradnik.telefon) + '">' + this.escapeHtml(suradnik.telefon) + '</a>';
        } else {
            html += '—';
        }
        html += '</div>';
        html += '</div>';
        
        // Email
        html += '<div class="seup-detail-item">';
        html += '<div class="seup-detail-label"><i class="fas fa-envelope me-2"></i>Email</div>';
        html += '<div class="seup-detail-value">';
        if (suradnik.email) {
            html += '<a href="mailto:' + this.escapeHtml(suradnik.email) + '">' + this.escapeHtml(suradnik.email) + '</a>';
        } else {
            html += '—';
        }
        html += '</div>';
        html += '</div>';
        
        // Datum kreiranja
        html += '<div class="seup-detail-item">';
        html += '<div class="seup-detail-label"><i class="fas fa-calendar me-2"></i>Datum kreiranja</div>';
        html += '<div class="seup-detail-value">' + this.escapeHtml(suradnik.datum_kreiranja) + '</div>';
        html += '</div>';
        
        // Adresa (wide)
        html += '<div class="seup-detail-item seup-detail-wide">';
        html += '<div class="seup-detail-label"><i class="fas fa-map-marker-alt me-2"></i>Adresa</div>';
        html += '<div class="seup-detail-value">' + (suradnik.adresa || '—') + '</div>';
        html += '</div>';
        
        html += '</div>'; // seup-details-grid
        html += '</div>'; // seup-suradnik-details
        
        content.innerHTML = html;
        
        // Update edit button
        const editBtn = document.getElementById('editSuradnikBtn');
        if (editBtn) {
            editBtn.onclick = () => {
                window.location.href = `postavke.php?edit=${this.currentSuradnikId}#trece_osobe`;
            };
        }
    }

    filterTable() {
        const searchNaziv = document.getElementById('searchNaziv');
        const searchOIB = document.getElementById('searchOIB');
        const tableRows = document.querySelectorAll('.seup-table-row[data-id]');
        const visibleCountSpan = document.getElementById('visibleCount');

        const nazivTerm = searchNaziv ? searchNaziv.value.toLowerCase() : '';
        const oibTerm = searchOIB ? searchOIB.value.toLowerCase() : '';
        let visibleCount = 0;

        tableRows.forEach(row => {
            const cells = row.querySelectorAll('.seup-table-td');
            const nazivText = cells[1] ? cells[1].textContent.toLowerCase() : '';
            const oibText = cells[2] ? cells[2].textContent.toLowerCase() : '';
            
            // Check search terms
            const matchesNaziv = !nazivTerm || nazivText.includes(nazivTerm);
            const matchesOIB = !oibTerm || oibText.includes(oibTerm);

            if (matchesNaziv && matchesOIB) {
                row.style.display = '';
                visibleCount++;
                // Add staggered animation
                row.style.animationDelay = `${visibleCount * 50}ms`;
                row.classList.add('filtered-in');
            } else {
                row.style.display = 'none';
                row.classList.remove('filtered-in');
            }
        });

        // Update visible count
        if (visibleCountSpan) {
            visibleCountSpan.textContent = visibleCount;
        }
    }

    // Utility methods
    debounce(func, wait) {
        let timeout;
        return function(...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
    }

    escapeHtml(text) {
        if (!text) return '';
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

    // Static method for external use
    static validateOIB(oib) {
        // Remove spaces and non-numeric characters
        oib = oib.replace(/[^0-9]/g, '');
        
        // Check length
        if (oib.length !== 11) {
            return false;
        }

        // Check if all digits are the same
        if (/^(\d)\1{10}$/.test(oib)) {
            return false;
        }

        // Calculate control digit using ISO 7064, MOD 11-10
        let sum = 0;
        for (let i = 0; i < 10; i++) {
            sum += parseInt(oib[i]) * (10 - i);
        }
        
        const controlDigit = (11 - (sum % 11)) % 10;
        
        return controlDigit == parseInt(oib[10]);
    
    async exportVCF(rowid, button) {
        try {
            button.classList.add('seup-loading');
            
            const formData = new FormData();
            formData.append('action', 'export_vcf');
            formData.append('rowid', rowid);
            
            const response = await fetch('', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                // Create download link
                const link = document.createElement('a');
                link.href = data.download_url;
                link.download = data.filename;
                link.style.display = 'none';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);

                this.showMessage(`VCF kontakt za ${data.contact_name} je preuzet`, 'success');
            } else {
                this.showMessage('Greška pri kreiranju VCF kontakta: ' + data.error, 'error');
            }
    }
        } catch (error) {
            console.error('VCF export error:', error);
            this.showMessage('Došlo je do greške pri kreiranju kontakta', 'error');
        } finally {
            button.classList.remove('seup-loading');
        }
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    new SuradniciManager();
});

// Export for potential external use
window.SuradniciManager = SuradniciManager;