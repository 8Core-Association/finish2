document.addEventListener("DOMContentLoaded", function() {
    const tabs = document.querySelectorAll('.seup-tab');
    const tabPanes = document.querySelectorAll('.seup-tab-pane');

    tabs.forEach(tab => {
        tab.addEventListener('click', function () {
            const target = this.dataset.tab;
            if (!target) return;

            tabs.forEach(t => t.classList.remove('active'));
            tabPanes.forEach(p => p.classList.remove('active'));
            this.classList.add('active');

            const pane = document.getElementById(target);
            if (pane) pane.classList.add('active');

            if (target === 'prepregled' && typeof openOmotPreview === 'function') {
                openOmotPreview();
            }
        });
    });

    const dodajAktBtn = document.getElementById('dodajAktBtn');
    const dodajPrilogBtn = document.getElementById('dodajPrilogBtn');
    const sortirajNedodjeljenoBtn = document.getElementById('sortirajNedodjeljenoBtn');

    if (dodajAktBtn) {
        dodajAktBtn.addEventListener('click', function() {
            aktUploadModal.classList.add('show');
        });
    }

    if (dodajPrilogBtn) {
        dodajPrilogBtn.addEventListener('click', function() {
            prilogUploadModal.classList.add('show');
        });
    }

    if (sortirajNedodjeljenoBtn) {
        sortirajNedodjeljenoBtn.addEventListener('click', function() {
        });
    }

    const aktUploadModal = document.getElementById('aktUploadModal');
    const aktUploadForm = document.getElementById('aktUploadForm');
    const uploadAktBtn = document.getElementById('uploadAktBtn');
    const aktFileInput = document.getElementById('aktFile');

    function openAktModal() {
        aktUploadModal.classList.add('show');
    }

    function closeAktModal() {
        aktUploadModal.classList.remove('show');
        aktUploadForm.reset();
    }

    function uploadAkt() {
        const file = aktFileInput.files[0];
        if (!file) {
            showMessage('Molimo odaberite datoteku', 'error');
            return;
        }

        if (file.size > 50 * 1024 * 1024) {
            showMessage('Datoteka je prevelika (maksimalno 50MB)', 'error');
            return;
        }

        uploadAktBtn.classList.add('seup-loading');
        const progressDiv = document.getElementById('uploadProgress');
        const progressFill = document.getElementById('progressFill');
        const progressText = document.getElementById('progressText');

        progressDiv.style.display = 'block';
        progressText.textContent = 'Uploading akt...';

        const formData = new FormData(aktUploadForm);

        const xhr = new XMLHttpRequest();

        xhr.upload.addEventListener('progress', function(e) {
            if (e.lengthComputable) {
                const percentComplete = (e.loaded / e.total) * 100;
                progressFill.style.width = percentComplete + '%';
                progressText.textContent = `Uploading... ${Math.round(percentComplete)}%`;
            }
        });

        xhr.addEventListener('load', function() {
            try {
                console.log('HTTP Status:', xhr.status);
                console.log('Server response:', xhr.responseText);
                const response = JSON.parse(xhr.responseText);
                if (response.success) {
                    showMessage(response.message, 'success');
                    closeAktModal();
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    showMessage('Greška pri uploadu: ' + response.error, 'error');
                }
            } catch (e) {
                console.error('JSON parse error:', e);
                console.error('Response text:', xhr.responseText);
                showMessage('Greška pri obradi odgovora: ' + xhr.responseText.substring(0, 200), 'error');
            }
        });

        xhr.addEventListener('error', function() {
            showMessage('Greška pri uploadu datoteke', 'error');
        });

        xhr.addEventListener('loadend', function() {
            uploadAktBtn.classList.remove('seup-loading');
            progressDiv.style.display = 'none';
            progressFill.style.width = '0%';
        });

        xhr.open('POST', '', true);
        xhr.send(formData);
    }

    document.getElementById('closeAktModal').addEventListener('click', closeAktModal);
    document.getElementById('cancelAktBtn').addEventListener('click', closeAktModal);
    document.getElementById('uploadAktBtn').addEventListener('click', uploadAkt);

    aktUploadModal.addEventListener('click', function(e) {
        if (e.target === this) {
            closeAktModal();
        }
    });

    const prilogUploadModal = document.getElementById('prilogUploadModal');
    const prilogUploadForm = document.getElementById('prilogUploadForm');
    const uploadPrilogBtn = document.getElementById('uploadPrilogBtn');
    const prilogFileInput = document.getElementById('prilogFile');
    const aktSelect = document.getElementById('aktSelect');

    function openPrilogModal() {
        prilogUploadModal.classList.add('show');
    }

    function closePrilogModal() {
        prilogUploadModal.classList.remove('show');
        if (prilogUploadForm) {
            prilogUploadForm.reset();
        }
    }

    function uploadPrilog() {
        if (!prilogUploadForm) {
            showMessage('Nema dostupnih akata za dodavanje priloga', 'error');
            return;
        }

        const file = prilogFileInput.files[0];
        const selectedAkt = aktSelect.value;

        if (!selectedAkt) {
            showMessage('Molimo odaberite akt', 'error');
            return;
        }

        if (!file) {
            showMessage('Molimo odaberite datoteku', 'error');
            return;
        }

        if (file.size > 50 * 1024 * 1024) {
            showMessage('Datoteka je prevelika (maksimalno 50MB)', 'error');
            return;
        }

        uploadPrilogBtn.classList.add('seup-loading');
        const progressDiv = document.getElementById('uploadProgress');
        const progressFill = document.getElementById('progressFill');
        const progressText = document.getElementById('progressText');

        progressDiv.style.display = 'block';
        progressText.textContent = 'Uploading prilog...';

        const formData = new FormData(prilogUploadForm);

        const xhr = new XMLHttpRequest();

        xhr.upload.addEventListener('progress', function(e) {
            if (e.lengthComputable) {
                const percentComplete = (e.loaded / e.total) * 100;
                progressFill.style.width = percentComplete + '%';
                progressText.textContent = `Uploading... ${Math.round(percentComplete)}%`;
            }
        });

        xhr.addEventListener('load', function() {
            try {
                console.log('HTTP Status (prilog):', xhr.status);
                console.log('Server response (prilog):', xhr.responseText);
                const response = JSON.parse(xhr.responseText);
                if (response.success) {
                    showMessage(response.message, 'success');
                    closePrilogModal();
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    showMessage('Greška pri uploadu: ' + response.error, 'error');
                }
            } catch (e) {
                console.error('JSON parse error (prilog):', e);
                console.error('Response text:', xhr.responseText);
                showMessage('Greška pri obradi odgovora: ' + xhr.responseText.substring(0, 200), 'error');
            }
        });

        xhr.addEventListener('error', function() {
            showMessage('Greška pri uploadu datoteke', 'error');
        });

        xhr.addEventListener('loadend', function() {
            uploadPrilogBtn.classList.remove('seup-loading');
            progressDiv.style.display = 'none';
            progressFill.style.width = '0%';
        });

        xhr.open('POST', '', true);
        xhr.send(formData);
    }

    if (document.getElementById('closePrilogModal')) {
        document.getElementById('closePrilogModal').addEventListener('click', closePrilogModal);
    }
    if (document.getElementById('cancelPrilogBtn')) {
        document.getElementById('cancelPrilogBtn').addEventListener('click', closePrilogModal);
    }
    if (document.getElementById('uploadPrilogBtn')) {
        document.getElementById('uploadPrilogBtn').addEventListener('click', uploadPrilog);
    }
    if (document.getElementById('dodajAktPrviBtn')) {
        document.getElementById('dodajAktPrviBtn').addEventListener('click', function() {
            closePrilogModal();
            dodajAktBtn.click();
        });
    }

    if (prilogUploadModal) {
        prilogUploadModal.addEventListener('click', function(e) {
            if (e.target === this) {
                closePrilogModal();
            }
        });
    }

    const generateOmotBtn = document.getElementById('generateOmotBtn');
    const previewOmotBtn = document.getElementById('previewOmotBtn');
    const printOmotBtn = document.getElementById('printOmotBtn');

    if (generateOmotBtn) {
        generateOmotBtn.addEventListener('click', function() {
            this.classList.add('seup-loading');

            const formData = new FormData();
            formData.append('action', 'generate_omot');

            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage(data.message, 'success');
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                } else {
                    showMessage('Greška pri generiranju omota: ' + data.error, 'error');
                }
            })
            .catch(error => {
                showMessage('Došlo je do greške pri generiranju omota', 'error');
            })
            .finally(() => {
                this.classList.remove('seup-loading');
            });
        });
    }

    if (previewOmotBtn) {
        previewOmotBtn.addEventListener('click', function() {
            openOmotPreview();
        });
    }

    if (printOmotBtn) {
        printOmotBtn.addEventListener('click', function() {
            openPrintInstructionsModal();
        });
    }

    function openPrintInstructionsModal() {
        const modal = document.getElementById('printInstructionsModal');
        modal.classList.add('show');
    }

    function closePrintInstructionsModal() {
        const modal = document.getElementById('printInstructionsModal');
        modal.classList.remove('show');
    }

    function confirmPrint() {
        closePrintInstructionsModal();

        showMessage('Priprema omot za ispis...', 'success', 2000);

        setTimeout(() => {
            window.print();
        }, 500);
    }

    document.getElementById('closePrintModal').addEventListener('click', closePrintInstructionsModal);
    document.getElementById('cancelPrintBtn').addEventListener('click', closePrintInstructionsModal);
    document.getElementById('confirmPrintBtn').addEventListener('click', confirmPrint);

    document.getElementById('printInstructionsModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closePrintInstructionsModal();
        }
    });

    function openOmotPreview() {
        const modal = document.getElementById('omotPreviewModal');
        const content = document.getElementById('omotPreviewContent');

        modal.classList.add('show');
        content.innerHTML = '<div class="seup-loading-message"><i class="fas fa-spinner fa-spin"></i> Učitavam prepregled...</div>';

        const formData = new FormData();
        formData.append('action', 'preview_omot');

        fetch('', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                content.innerHTML = data.preview_html;
            } else {
                content.innerHTML = '<div class="seup-alert seup-alert-error">Greška pri učitavanju prepregleda: ' + data.error + '</div>';
            }
        })
        .catch(error => {
            content.innerHTML = '<div class="seup-alert seup-alert-error">Došlo je do greške pri učitavanju prepregleda</div>';
        });
    }

    function closeOmotPreview() {
        document.getElementById('omotPreviewModal').classList.remove('show');
    }

    document.getElementById('closeOmotModal').addEventListener('click', closeOmotPreview);
    document.getElementById('closePreviewBtn').addEventListener('click', closeOmotPreview);

    document.getElementById('generateFromPreviewBtn').addEventListener('click', function() {
        closeOmotPreview();
        generateOmotBtn.click();
    });

    document.getElementById('omotPreviewModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeOmotPreview();
        }
    });

    let currentDeleteData = null;

    document.addEventListener('click', function(e) {
        if (e.target.closest('.delete-document-btn')) {
            const btn = e.target.closest('.delete-document-btn');
            const filename = btn.dataset.filename;
            const filepath = btn.dataset.filepath;

            currentDeleteData = { filename, filepath };

            document.getElementById('deleteDocName').textContent = filename;

            document.getElementById('deleteDocModal').classList.add('show');
        }
    });

    function closeDeleteModal() {
        const deleteModal = document.getElementById('deleteDocModal');
        deleteModal.classList.remove('show');
        currentDeleteData = null;

        delete deleteModal.dataset.bulkMode;

        const modalTitle = deleteModal.querySelector('.seup-modal-title');
        if (modalTitle) {
            modalTitle.innerHTML = '<i class="fas fa-trash me-2"></i>Brisanje Dokumenta';
        }

        const deleteDocInfo = deleteModal.querySelector('.seup-delete-doc-info');
        if (deleteDocInfo) {
            deleteDocInfo.innerHTML = `
                <div class="seup-delete-doc-icon"><i class="fas fa-file-alt"></i></div>
                <div class="seup-delete-doc-details">
                    <div class="seup-delete-doc-name" id="deleteDocName">document.pdf</div>
                    <div class="seup-delete-doc-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        Jeste li sigurni da želite obrisati ovaj dokument? Ova akcija je nepovratna.
                    </div>
                </div>
            `;
        }
    }

    function performBulkDelete(docs) {
        executeBulkActionBtn.classList.add('seup-loading');
        executeBulkActionBtn.disabled = true;

        let deletedCount = 0;
        let errorCount = 0;

        docs.forEach((doc, index) => {
            const formData = new FormData();
            formData.append('action', 'delete_document');
            formData.append('filename', doc.filename);
            formData.append('filepath', doc.filepath);

            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    deletedCount++;
                    const row = document.querySelector(`tr[data-doc-id="${doc.id}"]`);
                    if (row) {
                        row.remove();
                    }
                } else {
                    errorCount++;
                }

                if (deletedCount + errorCount === docs.length) {
                    executeBulkActionBtn.classList.remove('seup-loading');
                    executeBulkActionBtn.disabled = false;

                    if (errorCount === 0) {
                        showMessage(`Uspješno obrisano ${deletedCount} dokumenata`, 'success');
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    } else {
                        showMessage(`Obrisano ${deletedCount} dokumenata, greška kod ${errorCount}`, 'warning');
                        setTimeout(() => {
                            window.location.reload();
                        }, 2000);
                    }
                }
            })
            .catch(error => {
                console.error('Error deleting document:', error);
                errorCount++;

                if (deletedCount + errorCount === docs.length) {
                    executeBulkActionBtn.classList.remove('seup-loading');
                    executeBulkActionBtn.disabled = false;
                    showMessage('Greška pri brisanju dokumenata', 'error');
                }
            });
        });
    }

    function confirmDelete() {
        const deleteModal = document.getElementById('deleteDocModal');

        if (deleteModal.dataset.bulkMode === 'true') {
            const docs = window.bulkDeleteDocs;
            if (!docs || docs.length === 0) return;

            closeDeleteModal();
            delete deleteModal.dataset.bulkMode;
            performBulkDelete(docs);
            return;
        }

        if (!currentDeleteData) return;

        const confirmBtn = document.getElementById('confirmDeleteBtn');
        confirmBtn.classList.add('seup-loading');

        const formData = new FormData();
        formData.append('action', 'delete_document');
        formData.append('filename', currentDeleteData.filename);
        formData.append('filepath', currentDeleteData.filepath);

        fetch('', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showMessage(data.message, 'success');
                closeDeleteModal();
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                showMessage('Greška pri brisanju: ' + data.error, 'error');
            }
        })
        .catch(error => {
            showMessage('Došlo je do greške pri brisanju dokumenta', 'error');
        })
        .finally(() => {
            confirmBtn.classList.remove('seup-loading');
        });
    }

    document.getElementById('closeDeleteModal').addEventListener('click', closeDeleteModal);
    document.getElementById('cancelDeleteBtn').addEventListener('click', closeDeleteModal);
    document.getElementById('confirmDeleteBtn').addEventListener('click', confirmDelete);

    document.getElementById('deleteDocModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeDeleteModal();
        }
    });

    window.showMessage = function(message, type = 'success', duration = 5000) {
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
    };

    // ========================================
    // REGISTRACIJA OTPREME
    // ========================================
    const otpremaModal = document.getElementById('registrirajOtpremuModal');
    const otpremaForm = document.getElementById('otpremaForm');
    const closeOtpremaModalBtn = document.querySelector('#registrirajOtpremuModal .seup-modal-close');
    const submitOtpremaBtn = document.getElementById('submitOtpremaBtn');

    // Event delegation for "Registriraj otpremu" buttons
    document.addEventListener('click', function(e) {
        if (e.target.closest('.registriraj-otpremu-btn')) {
            const btn = e.target.closest('.registriraj-otpremu-btn');
            const ecmFileId = btn.getAttribute('data-ecm-file-id');
            const tipDokumenta = btn.getAttribute('data-tip-dokumenta');
            const docName = btn.getAttribute('data-doc-name');

            // Populate hidden fields
            document.getElementById('otprema_fk_ecm_file').value = ecmFileId;
            document.getElementById('otprema_tip_dokumenta').value = tipDokumenta;

            // Show document name
            const docNameEl = document.getElementById('otprema_doc_name');
            if (docNameEl) docNameEl.textContent = docName;


            // Open modal
            otpremaModal.classList.add('show');
        }
    });

    // Close modal
    if (closeOtpremaModalBtn) {
        closeOtpremaModalBtn.addEventListener('click', function() {
            otpremaModal.classList.remove('show');
            otpremaForm.reset();
        });
    }

    // Helper function to cleanup bulk modal
    function cleanupBulkOtpremaModal() {
        if (otpremaModal) {
            otpremaModal.dataset.bulkMode = 'false';
            const docList = otpremaModal.querySelector('.seup-bulk-doc-list');
            if (docList) {
                docList.remove();
            }
            const modalTitle = otpremaModal.querySelector('.seup-modal-title');
            if (modalTitle && modalTitle.dataset.originalTitle) {
                modalTitle.innerHTML = modalTitle.dataset.originalTitle;
            }
            delete window.bulkOtpremaDocIds;
        }
    }

    // Close modal on cancel button
    const cancelOtpremaBtn = document.getElementById('cancelOtpremaBtn');
    if (cancelOtpremaBtn) {
        cancelOtpremaBtn.addEventListener('click', function() {
            otpremaModal.classList.remove('show');
            otpremaForm.reset();
            cleanupBulkOtpremaModal();
        });
    }

    // Close modal on X button
    const closeOtpremaModal = document.getElementById('closeOtpremaModal');
    if (closeOtpremaModal) {
        closeOtpremaModal.addEventListener('click', function() {
            otpremaModal.classList.remove('show');
            otpremaForm.reset();
            cleanupBulkOtpremaModal();
        });
    }

    // Close modal on background click
    if (otpremaModal) {
        otpremaModal.addEventListener('click', function(e) {
            if (e.target === otpremaModal) {
                otpremaModal.classList.remove('show');
                otpremaForm.reset();
                cleanupBulkOtpremaModal();
            }
        });
    }

    // Submit otprema form
    if (submitOtpremaBtn) {
        submitOtpremaBtn.addEventListener('click', function() {
            const primateljNaziv = document.getElementById('primatelj_naziv').value.trim();
            const datumOtpreme = document.getElementById('datum_otpreme').value;
            const nacinOtpreme = document.getElementById('nacin_otpreme').value;

            // Validation
            if (!primateljNaziv) {
                showMessage('Naziv primatelja je obavezan', 'error');
                return;
            }

            if (!datumOtpreme) {
                showMessage('Datum otpreme je obavezan', 'error');
                return;
            }

            if (!nacinOtpreme) {
                showMessage('Način otpreme je obavezan', 'error');
                return;
            }

            submitOtpremaBtn.classList.add('seup-loading');
            submitOtpremaBtn.disabled = true;

            const formData = new FormData(otpremaForm);

            // Check if in bulk mode
            const isBulkMode = otpremaModal.dataset.bulkMode === 'true';
            console.log('Bulk mode check:', isBulkMode, window.bulkOtpremaDocIds);

            if (isBulkMode && window.bulkOtpremaDocIds) {
                formData.set('action', 'bulk_otprema');
                window.bulkOtpremaDocIds.forEach(docId => {
                    formData.append('doc_ids[]', docId);
                });
                console.log('Bulk otprema mode: sending', window.bulkOtpremaDocIds.length, 'documents');
            } else {
                console.log('Single otprema mode');
            }

            // Debug: log form data
            for (let pair of formData.entries()) {
                console.log(pair[0] + ': ' + pair[1]);
            }

            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage(data.message || 'Otprema uspješno registrirana', 'success');
                    otpremaModal.classList.remove('show');
                    otpremaForm.reset();
                    cleanupBulkOtpremaModal();

                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    showMessage(data.error || 'Greška pri registraciji otpreme', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage('Greška pri slanju zahtjeva', 'error');
            })
            .finally(() => {
                submitOtpremaBtn.classList.remove('seup-loading');
                submitOtpremaBtn.disabled = false;
            });
        });
    }

    // ===== BULK ACTIONS =====
    const selectAllCheckbox = document.getElementById('selectAllDocs');
    const bulkActionsToolbar = document.getElementById('bulkActionsToolbar');
    const selectedCountSpan = document.getElementById('selectedCount');
    const bulkActionSelect = document.getElementById('bulkActionSelect');
    const executeBulkActionBtn = document.getElementById('executeBulkAction');
    const cancelBulkActionBtn = document.getElementById('cancelBulkAction');

    function updateBulkActionsUI() {
        const checkboxes = document.querySelectorAll('.doc-checkbox');
        const checkedBoxes = document.querySelectorAll('.doc-checkbox:checked');

        if (checkedBoxes.length > 0) {
            bulkActionsToolbar.style.display = 'flex';
            selectedCountSpan.textContent = checkedBoxes.length;
        } else {
            bulkActionsToolbar.style.display = 'none';
            bulkActionSelect.value = '';
        }

        // Update "select all" checkbox state
        if (selectAllCheckbox) {
            selectAllCheckbox.checked = (checkboxes.length > 0 && checkedBoxes.length === checkboxes.length);
        }
    }

    // Select all checkbox
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.doc-checkbox');
            checkboxes.forEach(cb => {
                cb.checked = this.checked;
            });
            updateBulkActionsUI();
        });
    }

    // Individual checkboxes
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('doc-checkbox')) {
            updateBulkActionsUI();
        }
    });

    // Cancel bulk action
    if (cancelBulkActionBtn) {
        cancelBulkActionBtn.addEventListener('click', function() {
            const checkboxes = document.querySelectorAll('.doc-checkbox');
            checkboxes.forEach(cb => {
                cb.checked = false;
            });
            if (selectAllCheckbox) {
                selectAllCheckbox.checked = false;
            }
            updateBulkActionsUI();
        });
    }

    // Execute bulk action
    if (executeBulkActionBtn) {
        executeBulkActionBtn.addEventListener('click', function() {
            const selectedAction = bulkActionSelect.value;
            const checkedBoxes = document.querySelectorAll('.doc-checkbox:checked');

            if (!selectedAction) {
                showMessage('Odaberite akciju', 'error');
                return;
            }

            if (checkedBoxes.length === 0) {
                showMessage('Nema odabranih dokumenata', 'error');
                return;
            }

            const selectedDocs = Array.from(checkedBoxes).map(cb => ({
                id: cb.value,
                filename: cb.dataset.filename,
                filepath: cb.dataset.filepath
            }));

            switch(selectedAction) {
                case 'otpremi':
                    bulkOtpremi(selectedDocs);
                    break;
                case 'download':
                    bulkDownload(selectedDocs);
                    break;
                case 'delete':
                    bulkDelete(selectedDocs);
                    break;
                default:
                    showMessage('Nepoznata akcija', 'error');
            }
        });
    }

    function bulkOtpremi(docs) {
        showMessage(`Otprema ${docs.length} dokumenata - popunite podatke`, 'info');

        const otpremaModal = document.getElementById('registrirajOtpremuModal');
        if (!otpremaModal) {
            showMessage('Modal nije pronađen', 'error');
            return;
        }

        // Store doc IDs for bulk submit
        window.bulkOtpremaDocIds = docs.map(d => d.id);

        // Update modal title
        const modalTitle = otpremaModal.querySelector('.seup-modal-title');
        if (modalTitle) {
            if (!modalTitle.dataset.originalTitle) {
                modalTitle.dataset.originalTitle = modalTitle.innerHTML;
            }
            modalTitle.innerHTML = modalTitle.dataset.originalTitle + ` (${docs.length} dokumenata)`;
        }

        // Show document list in modal
        const docListHTML = '<div class="seup-bulk-doc-list" style="margin: 10px 0; padding: 10px; background: #f5f5f5; border-radius: 8px;">' +
            '<strong>Dokumenti za otpremu:</strong><ul style="margin: 5px 0; padding-left: 20px;">' +
            docs.map(d => '<li>' + d.filename + '</li>').join('') +
            '</ul></div>';

        const modalBody = otpremaModal.querySelector('.seup-modal-body');
        const existingList = modalBody.querySelector('.seup-bulk-doc-list');
        if (existingList) {
            existingList.remove();
        }
        modalBody.insertAdjacentHTML('afterbegin', docListHTML);

        // Mark as bulk mode
        otpremaModal.dataset.bulkMode = 'true';

        // Open modal
        otpremaModal.classList.add('show');
    }

    function bulkDownload(docs) {
        showMessage(`Kreiranje ZIP arhive sa ${docs.length} dokumenata...`, 'info');

        const formData = new FormData();
        formData.append('action', 'bulk_download_zip');
        docs.forEach(doc => {
            formData.append('doc_ids[]', doc.id);
        });

        fetch('', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Download failed');
            }
            return response.blob();
        })
        .then(blob => {
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'dokumenti_' + new Date().toISOString().slice(0,10) + '.zip';
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
            showMessage('ZIP arhiva preuzeta', 'success');
            cancelBulkActionBtn.click();
        })
        .catch(error => {
            console.error('Error downloading ZIP:', error);
            showMessage('Greška pri preuzimanju ZIP arhive', 'error');
        });
    }

    function bulkDelete(docs) {
        const deleteModal = document.getElementById('deleteDocModal');
        if (!deleteModal) {
            showMessage('Modal nije pronađen', 'error');
            return;
        }

        window.bulkDeleteDocs = docs;

        const modalTitle = deleteModal.querySelector('.seup-modal-title');
        if (modalTitle) {
            modalTitle.innerHTML = `<i class="fas fa-trash me-2"></i>Brisanje ${docs.length} Dokumenata`;
        }

        const deleteDocInfo = deleteModal.querySelector('.seup-delete-doc-info');
        if (deleteDocInfo) {
            deleteDocInfo.innerHTML = `
                <div class="seup-delete-doc-icon"><i class="fas fa-file-alt"></i></div>
                <div class="seup-delete-doc-details">
                    <div class="seup-delete-doc-name">Označeni dokumenti za brisanje:</div>
                    <div class="seup-bulk-doc-list" style="margin: 10px 0; padding: 10px; background: #fef2f2; border-radius: 8px; max-height: 200px; overflow-y: auto;">
                        <ul style="margin: 5px 0; padding-left: 20px; list-style: disc;">
                            ${docs.map(d => '<li>' + d.filename + '</li>').join('')}
                        </ul>
                    </div>
                    <div class="seup-delete-doc-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        Jeste li sigurni da želite obrisati ${docs.length} dokumenata? Ova akcija je nepovratna.
                    </div>
                </div>
            `;
        }

        deleteModal.dataset.bulkMode = 'true';
        deleteModal.classList.add('show');
    }

    // PDF Preview functionality
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('seup-pdf-preview-trigger')) {
            e.preventDefault();
            e.stopPropagation();

            const pdfUrl = e.target.getAttribute('data-pdf-url');
            const filename = e.target.getAttribute('data-filename');

            if (pdfUrl) {
                openPDFPreviewModal(pdfUrl, filename);
            }
        }
    });

    function openPDFPreviewModal(pdfUrl, filename) {
        // Create modal if it doesn't exist
        let modal = document.getElementById('pdfPreviewModal');

        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'pdfPreviewModal';
            modal.className = 'seup-modal-overlay';
            modal.innerHTML = `
                <div class="seup-modal-container seup-pdf-preview-modal">
                    <div class="seup-modal-header">
                        <h3 class="seup-modal-title">
                            <i class="fas fa-file-pdf me-2"></i>
                            <span id="pdfPreviewFilename">${filename || 'PDF Predpregled'}</span>
                        </h3>
                        <button class="seup-modal-close" id="closePdfPreviewModal">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="seup-modal-body" style="padding: 0; height: 80vh; overflow: hidden;">
                        <iframe id="pdfPreviewFrame" style="width: 100%; height: 100%; border: none;"></iframe>
                    </div>
                    <div class="seup-modal-footer">
                        <a href="" target="_blank" download class="seup-btn seup-btn-secondary" id="pdfDownloadBtn">
                            <i class="fas fa-download me-2"></i>Preuzmi
                        </a>
                        <button type="button" class="seup-btn seup-btn-secondary" id="closePdfPreviewModalBtn">
                            Zatvori
                        </button>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);

            // Add event listeners
            document.getElementById('closePdfPreviewModal').addEventListener('click', closePDFPreviewModal);
            document.getElementById('closePdfPreviewModalBtn').addEventListener('click', closePDFPreviewModal);
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    closePDFPreviewModal();
                }
            });
        }

        // Update modal content
        document.getElementById('pdfPreviewFilename').textContent = filename || 'PDF Predpregled';
        document.getElementById('pdfPreviewFrame').src = pdfUrl;
        document.getElementById('pdfDownloadBtn').href = pdfUrl;

        // Show modal
        modal.classList.add('show');
    }

    function closePDFPreviewModal() {
        const modal = document.getElementById('pdfPreviewModal');
        if (modal) {
            modal.classList.remove('show');
            // Clear iframe source to stop loading
            document.getElementById('pdfPreviewFrame').src = '';
        }
    }

    // Zaprimanje Details Modal
    const zapIndicators = document.querySelectorAll('.seup-zap-indicator');
    const zapDetailsModal = document.getElementById('zaprimanjeDetailsModal');
    const closeZapDetailsBtn = document.getElementById('closeZaprimanjeDetailsModal');
    const zapDetailsContent = document.getElementById('zaprimanjeDetailsContent');

    if (zapIndicators) {
        zapIndicators.forEach(indicator => {
            indicator.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const zaprimanjeId = this.dataset.zaprimanjeId;
                openZaprimanjeDetailsModal(zaprimanjeId);
            });
        });
    }

    if (closeZapDetailsBtn) {
        closeZapDetailsBtn.addEventListener('click', closeZaprimanjeDetailsModal);
    }

    if (zapDetailsModal) {
        zapDetailsModal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeZaprimanjeDetailsModal();
            }
        });
    }

    function openZaprimanjeDetailsModal(zaprimanjeId) {
        if (!zapDetailsModal || !zapDetailsContent) return;

        zapDetailsContent.innerHTML = '<div class="seup-loading"><i class="fas fa-spinner fa-spin"></i> Učitavam...</div>';
        zapDetailsModal.classList.add('show');

        const formData = new FormData();
        formData.append('action', 'get_zaprimanje_details');
        formData.append('zaprimanje_id', zaprimanjeId);

        fetch(window.location.pathname + window.location.search, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const zap = data.data;
                let html = '<div class="seup-zaprimanje-details-grid">';

                html += '<div class="seup-zaprimanje-detail-field">';
                html += '<div class="seup-zaprimanje-detail-label"><i class="fas fa-calendar"></i> Datum zaprimanja</div>';
                html += '<div class="seup-zaprimanje-detail-value">' + formatDate(zap.datum_zaprimanja) + '</div>';
                html += '</div>';

                html += '<div class="seup-zaprimanje-detail-field">';
                html += '<div class="seup-zaprimanje-detail-label"><i class="fas fa-inbox"></i> Način zaprimanja</div>';
                html += '<div class="seup-zaprimanje-detail-value">' + formatNacinZaprimanja(zap.nacin_zaprimanja) + '</div>';
                html += '</div>';

                html += '<div class="seup-zaprimanje-detail-field">';
                html += '<div class="seup-zaprimanje-detail-label"><i class="fas fa-user"></i> Pošiljatelj</div>';
                html += '<div class="seup-zaprimanje-detail-value">' + (zap.posiljatelj_naziv || '—') + '</div>';
                html += '</div>';

                html += '<div class="seup-zaprimanje-detail-field">';
                html += '<div class="seup-zaprimanje-detail-label"><i class="fas fa-hashtag"></i> Broj pošiljke</div>';
                html += '<div class="seup-zaprimanje-detail-value">' + (zap.posiljatelj_broj || '—') + '</div>';
                html += '</div>';

                html += '<div class="seup-zaprimanje-detail-field">';
                html += '<div class="seup-zaprimanje-detail-label"><i class="fas fa-tag"></i> Tip dokumenta</div>';
                html += '<div class="seup-zaprimanje-detail-value">' + formatTipDokumenta(zap.tip_dokumenta) + '</div>';
                html += '</div>';

                html += '<div class="seup-zaprimanje-detail-field">';
                html += '<div class="seup-zaprimanje-detail-label"><i class="fas fa-file"></i> Dokument</div>';
                html += '<div class="seup-zaprimanje-detail-value">' + (zap.doc_filename || '—') + '</div>';
                html += '</div>';

                html += '</div>';

                if (zap.napomena) {
                    html += '<div class="seup-zaprimanje-detail-napomena">';
                    html += '<div class="seup-zaprimanje-detail-label"><i class="fas fa-sticky-note"></i> Napomena</div>';
                    html += '<div class="seup-zaprimanje-detail-value">' + zap.napomena + '</div>';
                    html += '</div>';
                }

                zapDetailsContent.innerHTML = html;
            } else {
                zapDetailsContent.innerHTML = '<div class="seup-error">Greška: ' + data.error + '</div>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            zapDetailsContent.innerHTML = '<div class="seup-error">Došlo je do greške pri učitavanju podataka</div>';
        });
    }

    function closeZaprimanjeDetailsModal() {
        if (zapDetailsModal) {
            zapDetailsModal.classList.remove('show');
        }
    }

    function formatDate(dateStr) {
        if (!dateStr) return '—';
        const date = new Date(dateStr);
        return date.toLocaleDateString('hr-HR', { day: '2-digit', month: '2-digit', year: 'numeric' });
    }

    function formatNacinZaprimanja(nacin) {
        const labels = {
            'posta': 'Pošta',
            'email': 'E-mail',
            'rucno': 'Na ruke',
            'courier': 'Kurirska služba',
            'fax': 'Fax',
            'web': 'Web',
            'sluzben_put': 'Službeni put'
        };
        return labels[nacin] || nacin;
    }

    function formatTipDokumenta(tip) {
        const labels = {
            'akt': 'Akt',
            'prilog': 'Prilog',
            'nedodjeljeni': 'Nedodijeljeni',
            'novi_akt': 'Novi akt',
            'prilog_postojecem': 'Prilog postojećem',
            'nerazvrstan': 'Nerazvrstan'
        };
        return labels[tip] || tip;
    }

    const otpIndicators = document.querySelectorAll('.seup-otp-indicator');
    const otpDetailsModal = document.getElementById('otpremaDetailsModal');
    const closeOtpDetailsBtn = document.getElementById('closeOtpremaDetailsModal');
    const otpDetailsContent = document.getElementById('otpremaDetailsContent');

    if (otpIndicators) {
        otpIndicators.forEach(indicator => {
            indicator.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const ecmFileId = this.dataset.ecmFileId;
                openOtpremaDetailsModal(ecmFileId);
            });
        });
    }

    if (closeOtpDetailsBtn) {
        closeOtpDetailsBtn.addEventListener('click', closeOtpremaDetailsModal);
    }

    if (otpDetailsModal) {
        otpDetailsModal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeOtpremaDetailsModal();
            }
        });
    }

    function openOtpremaDetailsModal(ecmFileId) {
        if (!otpDetailsModal || !otpDetailsContent) return;

        otpDetailsContent.innerHTML = '<div class="seup-loading"><i class="fas fa-spinner fa-spin"></i> Učitavam...</div>';
        otpDetailsModal.classList.add('show');

        const formData = new FormData();
        formData.append('action', 'get_otprema_details');
        formData.append('ecm_file_id', ecmFileId);

        fetch(window.location.pathname + window.location.search, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data && data.data.length > 0) {
                let html = '';

                data.data.forEach((otp, index) => {
                    if (index > 0) {
                        html += '<hr class="seup-otprema-separator">';
                    }

                    html += '<div class="seup-otprema-record">';
                    html += '<div class="seup-otprema-header">';
                    html += '<h6 class="seup-otprema-title"><i class="fas fa-paper-plane"></i> Otprema #' + (index + 1) + '</h6>';
                    html += '</div>';

                    html += '<div class="seup-otprema-details-grid">';

                    html += '<div class="seup-otprema-detail-field">';
                    html += '<div class="seup-otprema-detail-label"><i class="fas fa-calendar"></i> Datum otpreme</div>';
                    html += '<div class="seup-otprema-detail-value">' + formatDate(otp.datum_otpreme) + '</div>';
                    html += '</div>';

                    html += '<div class="seup-otprema-detail-field">';
                    html += '<div class="seup-otprema-detail-label"><i class="fas fa-shipping-fast"></i> Način otpreme</div>';
                    html += '<div class="seup-otprema-detail-value">' + formatNacinOtpreme(otp.nacin_otpreme) + '</div>';
                    html += '</div>';

                    html += '<div class="seup-otprema-detail-field">';
                    html += '<div class="seup-otprema-detail-label"><i class="fas fa-user"></i> Primatelj</div>';
                    html += '<div class="seup-otprema-detail-value">' + (otp.primatelj_naziv || '—') + '</div>';
                    html += '</div>';

                    if (otp.primatelj_adresa) {
                        html += '<div class="seup-otprema-detail-field">';
                        html += '<div class="seup-otprema-detail-label"><i class="fas fa-map-marker-alt"></i> Adresa</div>';
                        html += '<div class="seup-otprema-detail-value">' + otp.primatelj_adresa + '</div>';
                        html += '</div>';
                    }

                    if (otp.primatelj_email) {
                        html += '<div class="seup-otprema-detail-field">';
                        html += '<div class="seup-otprema-detail-label"><i class="fas fa-envelope"></i> Email</div>';
                        html += '<div class="seup-otprema-detail-value">' + otp.primatelj_email + '</div>';
                        html += '</div>';
                    }

                    if (otp.primatelj_telefon) {
                        html += '<div class="seup-otprema-detail-field">';
                        html += '<div class="seup-otprema-detail-label"><i class="fas fa-phone"></i> Telefon</div>';
                        html += '<div class="seup-otprema-detail-value">' + otp.primatelj_telefon + '</div>';
                        html += '</div>';
                    }

                    html += '<div class="seup-otprema-detail-field">';
                    html += '<div class="seup-otprema-detail-label"><i class="fas fa-tag"></i> Tip dokumenta</div>';
                    html += '<div class="seup-otprema-detail-value">' + formatTipDokumenta(otp.tip_dokumenta) + '</div>';
                    html += '</div>';

                    if (otp.klasifikacijska_oznaka) {
                        html += '<div class="seup-otprema-detail-field">';
                        html += '<div class="seup-otprema-detail-label"><i class="fas fa-tag"></i> Klasifikacijska oznaka</div>';
                        html += '<div class="seup-otprema-detail-value">' + otp.klasifikacijska_oznaka + '</div>';
                        html += '</div>';
                    }

                    html += '</div>';

                    if (otp.napomena) {
                        html += '<div class="seup-otprema-detail-napomena">';
                        html += '<div class="seup-otprema-detail-label"><i class="fas fa-sticky-note"></i> Napomena</div>';
                        html += '<div class="seup-otprema-detail-value">' + otp.napomena + '</div>';
                        html += '</div>';
                    }

                    html += '</div>';
                });

                otpDetailsContent.innerHTML = html;
            } else {
                otpDetailsContent.innerHTML = '<div class="seup-error">Nema podataka o otpremi</div>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            otpDetailsContent.innerHTML = '<div class="seup-error">Došlo je do greške pri učitavanju podataka</div>';
        });
    }

    function closeOtpremaDetailsModal() {
        if (otpDetailsModal) {
            otpDetailsModal.classList.remove('show');
        }
    }

    function formatNacinOtpreme(nacin) {
        const labels = {
            'posta': 'Pošta',
            'email': 'E-mail',
            'rucno': 'Na ruke',
            'ostalo': 'Ostalo'
        };
        return labels[nacin] || nacin;
    }
});
