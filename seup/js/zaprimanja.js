(function() {
    'use strict';

    let posiljateljAutocompleteTimeout;
    let selectedPosiljatelj = null;

    document.addEventListener('DOMContentLoaded', function() {
        console.log('Zaprimanja.js loaded');

        const zaprimiBtn = document.getElementById('zaprimiDokumentBtn');
        const modal = document.getElementById('zaprimiDokumentModal');
        const closeModalBtn = document.getElementById('closeZaprimanjeModal');
        const cancelBtn = document.getElementById('cancelZaprimanjeBtn');
        const zaprimanjeForm = document.getElementById('zaprimanjeForm');
        const tipDokumentaSelect = document.getElementById('tip_dokumenta');
        const aktZaPrilogWrapper = document.getElementById('akt_za_prilog_wrapper');
        const posiljateljSearchInput = document.getElementById('posiljatelj_search');
        const posiljateljDropdown = document.getElementById('posiljatelj_dropdown');
        const fkPosiljateljInput = document.getElementById('fk_posiljatelj');

        console.log('Elements found:', {
            zaprimiBtn: !!zaprimiBtn,
            modal: !!modal,
            zaprimanjeForm: !!zaprimanjeForm,
            submitBtn: !!document.getElementById('submitZaprimanjeBtn')
        });

        if (zaprimiBtn) {
            zaprimiBtn.addEventListener('click', function() {
                if (modal) {
                    modal.classList.add('show');
                    if (zaprimanjeForm) zaprimanjeForm.reset();
                    if (fkPosiljateljInput) fkPosiljateljInput.value = '';
                    selectedPosiljatelj = null;
                    if (aktZaPrilogWrapper) aktZaPrilogWrapper.style.display = 'none';
                    if (posiljateljDropdown) posiljateljDropdown.classList.remove('active');
                }
            });
        }

        function closeModal() {
            if (modal) {
                modal.classList.remove('show');
                if (zaprimanjeForm) zaprimanjeForm.reset();
                if (fkPosiljateljInput) fkPosiljateljInput.value = '';
                selectedPosiljatelj = null;
                if (posiljateljDropdown) posiljateljDropdown.classList.remove('active');
            }
        }

        if (closeModalBtn) {
            closeModalBtn.addEventListener('click', closeModal);
        }

        if (cancelBtn) {
            cancelBtn.addEventListener('click', closeModal);
        }

        if (modal) {
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    closeModal();
                }
            });
        }

        if (tipDokumentaSelect && aktZaPrilogWrapper) {
            tipDokumentaSelect.addEventListener('change', function() {
                if (this.value === 'prilog_postojecem') {
                    aktZaPrilogWrapper.style.display = 'block';
                    const aktSelect = document.getElementById('fk_akt_za_prilog');
                    if (aktSelect) aktSelect.required = true;
                } else {
                    aktZaPrilogWrapper.style.display = 'none';
                    const aktSelect = document.getElementById('fk_akt_za_prilog');
                    if (aktSelect) aktSelect.required = false;
                }
            });
        }

        if (posiljateljSearchInput) {
            posiljateljSearchInput.addEventListener('input', function() {
                const query = this.value.trim();

                clearTimeout(posiljateljAutocompleteTimeout);

                if (query.length < 2) {
                    if (posiljateljDropdown) {
                        posiljateljDropdown.classList.remove('active');
                        posiljateljDropdown.innerHTML = '';
                    }
                    if (fkPosiljateljInput) fkPosiljateljInput.value = '';
                    selectedPosiljatelj = null;
                    return;
                }

                if (posiljateljDropdown) {
                    posiljateljDropdown.innerHTML = '<div class="seup-autocomplete-loading"><i class="fas fa-spinner fa-spin"></i> Pretraživanje...</div>';
                    posiljateljDropdown.classList.add('active');
                }

                posiljateljAutocompleteTimeout = setTimeout(function() {
                    searchPosiljatelji(query);
                }, 300);
            });

            document.addEventListener('click', function(e) {
                if (posiljateljSearchInput && posiljateljDropdown) {
                    if (!posiljateljSearchInput.contains(e.target) && !posiljateljDropdown.contains(e.target)) {
                        posiljateljDropdown.classList.remove('active');
                    }
                }
            });
        }

        function searchPosiljatelji(query) {
            if (!posiljateljDropdown) return;

            const formData = new FormData();
            formData.append('action', 'search_posiljatelji');
            formData.append('query', query);

            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayPosiljateljiResults(data.results);
                } else {
                    posiljateljDropdown.innerHTML = '<div class="seup-autocomplete-no-results">Greška pri pretraživanju</div>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                if (posiljateljDropdown) {
                    posiljateljDropdown.innerHTML = '<div class="seup-autocomplete-no-results">Greška pri pretraživanju</div>';
                }
            });
        }

        function displayPosiljateljiResults(results) {
            if (!posiljateljDropdown) return;

            if (!results || results.length === 0) {
                posiljateljDropdown.innerHTML = '<div class="seup-autocomplete-no-results">Nema rezultata. Možete unijeti novi naziv.</div>';
                return;
            }

            let html = '';
            results.forEach(function(posiljatelj) {
                let details = [];
                if (posiljatelj.oib) details.push('<span>OIB: ' + posiljatelj.oib + '</span>');
                if (posiljatelj.email) details.push('<span>Email: ' + posiljatelj.email + '</span>');
                if (posiljatelj.telefon) details.push('<span>Tel: ' + posiljatelj.telefon + '</span>');

                html += '<div class="seup-autocomplete-item" data-id="' + posiljatelj.rowid + '" data-naziv="' + posiljatelj.naziv + '">';
                html += '<div class="seup-autocomplete-item-title">' + posiljatelj.naziv + '</div>';
                if (details.length > 0) {
                    html += '<div class="seup-autocomplete-item-details">' + details.join('') + '</div>';
                }
                html += '</div>';
            });

            posiljateljDropdown.innerHTML = html;

            const items = posiljateljDropdown.querySelectorAll('.seup-autocomplete-item');
            items.forEach(function(item) {
                item.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const naziv = this.getAttribute('data-naziv');

                    if (posiljateljSearchInput) posiljateljSearchInput.value = naziv;
                    if (fkPosiljateljInput) fkPosiljateljInput.value = id;
                    selectedPosiljatelj = { id: id, naziv: naziv };

                    if (posiljateljDropdown) posiljateljDropdown.classList.remove('active');
                });
            });
        }

        const submitZaprimanjeBtn = document.getElementById('submitZaprimanjeBtn');
        if (submitZaprimanjeBtn) {
            console.log('Submit button found, attaching click handler');
            submitZaprimanjeBtn.addEventListener('click', function(e) {
                console.log('Submit button clicked');
                e.preventDefault();

                const form = document.getElementById('zaprimanjeForm');
                if (!form) {
                    console.error('Form not found');
                    return;
                }

                console.log('Form found, submitting...');

                const originalText = submitZaprimanjeBtn.innerHTML;
                submitZaprimanjeBtn.disabled = true;
                submitZaprimanjeBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Zaprimanje...';

                const formData = new FormData(form);
                console.log('FormData created');

                fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showMessage('Dokument uspješno zaprimljen!', 'success');
                        closeModal();
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        showMessage('Greška: ' + (data.error || 'Nepoznata greška'), 'error');
                        submitZaprimanjeBtn.disabled = false;
                        submitZaprimanjeBtn.innerHTML = originalText;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showMessage('Greška pri zaprimanju dokumenta', 'error');
                    submitZaprimanjeBtn.disabled = false;
                    submitZaprimanjeBtn.innerHTML = originalText;
                });
            });
        }

        function showMessage(message, type) {
            const existingMessage = document.querySelector('.seup-message');
            if (existingMessage) {
                existingMessage.remove();
            }

            const messageDiv = document.createElement('div');
            messageDiv.className = 'seup-message seup-message-' + type;
            messageDiv.innerHTML = '<i class="fas fa-' + (type === 'success' ? 'check-circle' : 'exclamation-circle') + '"></i> ' + message;

            document.body.appendChild(messageDiv);

            setTimeout(function() {
                messageDiv.classList.add('show');
            }, 10);

            setTimeout(function() {
                messageDiv.classList.remove('show');
                setTimeout(function() {
                    messageDiv.remove();
                }, 300);
            }, 3000);
        }
    });

})();
