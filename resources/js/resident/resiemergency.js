/**
 * report_emergency.js
 * Place in: public/js/resident/report_emergency.js
 * Load via: <script src="{{ asset('js/resident/report_emergency.js') }}" defer></script>
 */

document.addEventListener('DOMContentLoaded', function () {

    /* ─────────────────────────────────────────
       Auto-dismiss flash alerts after 5 s
       ───────────────────────────────────────── */
    document.querySelectorAll('.alert').forEach(function (el) {
        setTimeout(function () {
            el.style.transition = 'opacity 0.4s ease';
            el.style.opacity    = '0';
            setTimeout(function () { el.remove(); }, 420);
        }, 5000);
    });

    /* ─────────────────────────────────────────
       File upload zone
       - Drag & drop support
       - Image thumbnail previews
       - File size validation (max 5 MB each)
       ───────────────────────────────────────── */
    var uploadZone  = document.getElementById('upload_zone');
    var uploadInput = document.getElementById('photo_evidence');
    var previewWrap = document.getElementById('upload_preview');
    var MAX_SIZE_MB = 5;

    if (uploadZone && uploadInput) {

        // Click on zone triggers file picker
        uploadZone.addEventListener('click', function (e) {
            // Don't re-trigger if the click is already on the input
            if (e.target !== uploadInput) {
                uploadInput.click();
            }
        });

        // Drag over styling
        uploadZone.addEventListener('dragover', function (e) {
            e.preventDefault();
            uploadZone.classList.add('drag-over');
        });

        uploadZone.addEventListener('dragleave', function () {
            uploadZone.classList.remove('drag-over');
        });

        // Drop files
        uploadZone.addEventListener('drop', function (e) {
            e.preventDefault();
            uploadZone.classList.remove('drag-over');

            var dt    = e.dataTransfer;
            var files = dt.files;

            // Assign dropped files to the input via DataTransfer
            try {
                var transfer = new DataTransfer();
                Array.from(files).forEach(function (file) {
                    transfer.items.add(file);
                });
                uploadInput.files = transfer.files;
            } catch (err) {
                // Fallback for browsers without DataTransfer constructor
            }

            renderPreviews(files);
        });

        // File input change
        uploadInput.addEventListener('change', function () {
            renderPreviews(this.files);
        });

        function renderPreviews(files) {
            if (!previewWrap) return;
            previewWrap.innerHTML = '';

            var hasError = false;

            Array.from(files).forEach(function (file) {
                // Size check
                if (file.size > MAX_SIZE_MB * 1024 * 1024) {
                    showFieldError(
                        uploadZone,
                        file.name + ' exceeds the ' + MAX_SIZE_MB + ' MB limit.'
                    );
                    hasError = true;
                    return;
                }

                if (!file.type.match('image.*')) return;

                var reader = new FileReader();
                reader.onload = function (e) {
                    var img = document.createElement('img');
                    img.src       = e.target.result;
                    img.className = 'preview-thumb';
                    img.alt       = file.name;
                    previewWrap.appendChild(img);
                };
                reader.readAsDataURL(file);
            });

            if (!hasError) clearFieldError(uploadZone);
        }
    }

    /* ─────────────────────────────────────────
       Client-side form validation on submit
       (server-side via Laravel is primary guard)
       ───────────────────────────────────────── */
    var form = document.getElementById('report_form');

    if (form) {
        form.addEventListener('submit', function (e) {
            var valid = true;

            var requiredFields = [
                { id: 'incident_type', label: 'Type of Incident' },
            ];

            requiredFields.forEach(function (field) {
                var el = document.getElementById(field.id);
                if (el && !el.value.trim()) {
                    showFieldError(el, field.label + ' is required. Please detect location.');
                    valid = false;
                } else if (el) {
                    clearFieldError(el);
                }
            });

            if (!valid) {
                e.preventDefault();
                var firstInvalid = form.querySelector('.is-invalid');
                if (firstInvalid) {
                    firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    firstInvalid.focus();
                }
            }
        });

        // Clear errors on input
        form.querySelectorAll('.form-input, .form-select, .form-textarea').forEach(function (el) {
            el.addEventListener('input',  function () { clearFieldError(this); });
            el.addEventListener('change', function () { clearFieldError(this); });
        });
    }

    /* ─────────────────────────────────────────
       Helpers
       ───────────────────────────────────────── */
    function showFieldError(el, message) {
        el.classList.add('is-invalid');

        var parent   = el.closest('.form-group') || el.parentElement;
        var existing = parent.querySelector('.field-error');
        if (existing) existing.remove();

        var err = document.createElement('div');
        err.className = 'field-error';
        err.innerHTML = '<span class="material-icons">error_outline</span>' + message;
        parent.appendChild(err);
    }

    function clearFieldError(el) {
        el.classList.remove('is-invalid');
        var parent = el.closest('.form-group') || el.parentElement;
        var err    = parent.querySelector('.field-error');
        if (err) err.remove();
    }

    /* ─────────────────────────────────────────
       Incident location map
       ───────────────────────────────────────── */
    var mapEl = document.getElementById('map');
    var latInput = document.getElementById('latitude');
    var lngInput = document.getElementById('longitude');

    if (mapEl && latInput && lngInput && typeof L !== 'undefined') {
        var defaultCenter = [14.663, 121.074];
        var map = L.map(mapEl, {
            center: defaultCenter,
            zoom: 13,
            zoomControl: true
        });

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        var incidentMarker = L.marker(defaultCenter, {
            draggable: true
        }).addTo(map).bindPopup('Drag this pin or click on the map to set the incident location.');

        var currentLocationMarker = null;
        var locateBtn = document.getElementById('locate_current_position');

        function updateLocationInputs(latlng) {
            latInput.value = latlng.lat.toFixed(6);
            lngInput.value = latlng.lng.toFixed(6);
        }

        function requestCurrentLocation(showAlertOnFail) {
            if (!navigator.geolocation) {
                if (showAlertOnFail) {
                    alert('Geolocation is not supported by your browser.');
                }
                return;
            }

            navigator.geolocation.getCurrentPosition(function (position) {
                var userLatLng = L.latLng(position.coords.latitude, position.coords.longitude);
                map.setView(userLatLng, 15);

                if (currentLocationMarker) {
                    currentLocationMarker.setLatLng(userLatLng);
                } else {
                    currentLocationMarker = L.circleMarker(userLatLng, {
                        radius: 8,
                        color: '#15803d',
                        fillColor: '#15803d',
                        fillOpacity: 0.9,
                        weight: 2
                    }).addTo(map).bindPopup('Your current location');
                }

                setIncidentLocation(userLatLng, 'Incident location set to your current location. Drag or click another spot to move the pin.');
            }, function () {
                map.setView(defaultCenter, 13);
                incidentMarker.openPopup();
                if (showAlertOnFail) {
                    alert('Unable to access your current location. Please allow location access or click the map.');
                }
            }, {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 60000
            });
        }

        if (locateBtn) {
            locateBtn.addEventListener('click', function () {
                requestCurrentLocation(true);
            });
        }

        function setIncidentLocation(latlng, message) {
            incidentMarker.setLatLng(latlng);
            if (message) {
                incidentMarker.bindPopup(message).openPopup();
            }
            updateLocationInputs(latlng);
        }

        incidentMarker.on('dragend', function (e) {
            setIncidentLocation(e.target.getLatLng(), 'Incident location set here.');
        });

        map.on('click', function (e) {
            setIncidentLocation(e.latlng, 'Incident location set here.');
        });

        if (navigator.geolocation) {
            requestCurrentLocation(false);
        } else {
            map.setView(defaultCenter, 13);
            incidentMarker.openPopup();
        }
    }

});