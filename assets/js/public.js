/* ============================================================
   Pylaris Contracts — Public JS
   Google Identity Services + flujo de autenticación
   ============================================================ */

(function () {
    'use strict';

    // pcData es inyectado por wp_localize_script en PC_Public::enqueue_assets()
    var cfg = window.pcData || {};

    // ----------------------------------------------------------------
    // Google Sign-In
    // ----------------------------------------------------------------

    /**
     * Inicializa Google Identity Services.
     * Se llama cuando la librería de Google está lista (callback onload).
     */
    function initGoogleSignIn() {
        if ( ! cfg.googleClientId ) return;
        if ( typeof google === 'undefined' || ! google.accounts ) return;

        google.accounts.id.initialize({
            client_id:        cfg.googleClientId,
            callback:         handleGoogleCredential,
            auto_select:      false,
            cancel_on_tap_outside: true,
        });
    }

    /**
     * Callback que recibe la respuesta de Google tras el login.
     * @param {Object} response  { credential: "id_token_string" }
     */
    function handleGoogleCredential(response) {
        if ( ! response || ! response.credential ) {
            showAuthError('No se recibió respuesta de Google. Intentá nuevamente.');
            return;
        }

        sendTokenToBackend(response.credential);
    }

    /**
     * Envía el ID token al endpoint AJAX del backend.
     * El backend lo valida con Google, verifica el email y crea la sesión.
     */
    function sendTokenToBackend(idToken) {
        setButtonLoading(true);

        var formData = new FormData();
        formData.append('action',          'pc_google_auth');
        formData.append('nonce',           cfg.authNonce);
        formData.append('id_token',        idToken);
        formData.append('contract_token',  cfg.contractToken);

        fetch(cfg.ajaxUrl, {
            method:      'POST',
            body:        formData,
            credentials: 'same-origin',
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if ( data.success ) {
                // Sesión creada — recargar página para mostrar el contrato
                window.location.href = data.data.redirect || window.location.href;
            } else {
                setButtonLoading(false);

                var errorData = data.data || {};

                if ( errorData.access_denied ) {
                    // Email incorrecto — mostrar pantalla de acceso denegado inline
                    showAccessDenied(errorData.google_email || '');
                } else {
                    showAuthError(errorData.message || 'Error desconocido.');
                }
            }
        })
        .catch(function() {
            setButtonLoading(false);
            showAuthError('Error de conexión. Verificá tu internet e intentá nuevamente.');
        });
    }

    // ----------------------------------------------------------------
    // Pantalla de login requerido
    // ----------------------------------------------------------------

    function initLoginScreen() {
        var btn = document.getElementById('pc-btn-google');
        if ( ! btn ) return;

        if ( ! cfg.googleClientId ) {
            btn.disabled = true;
            btn.textContent = 'Google no configurado';
            return;
        }

        btn.addEventListener('click', function() {
            if ( typeof google === 'undefined' || ! google.accounts ) {
                showAuthError('No se pudo cargar Google. Recargá la página.');
                return;
            }

            // Lanzar el selector de cuenta de Google
            google.accounts.id.prompt(function(notification) {
                // Si el prompt es bloqueado por el browser, usar el popup
                if (notification.isNotDisplayed() || notification.isSkippedMoment()) {
                    openGooglePopup();
                }
            });
        });
    }

    /**
     * Abre el popup de login de Google como fallback.
     * Útil cuando el One Tap está bloqueado por el navegador.
     */
    function openGooglePopup() {
        if ( typeof google === 'undefined' || ! google.accounts ) return;

        // Crear un botón de Google invisible y hacer click programáticamente
        var container = document.getElementById('pc-google-signin-btn');
        if ( ! container ) return;

        // Renderizar el botón de Google (oculto) y dispararle click
        var hidden = document.createElement('div');
        hidden.style.position = 'absolute';
        hidden.style.opacity  = '0';
        hidden.style.pointerEvents = 'none';
        document.body.appendChild(hidden);

        google.accounts.id.renderButton(hidden, {
            type:  'standard',
            theme: 'outline',
            size:  'large',
        });

        var gBtn = hidden.querySelector('[role="button"]');
        if ( gBtn ) {
            gBtn.click();
        }

        setTimeout(function() {
            document.body.removeChild(hidden);
        }, 2000);
    }

    // ----------------------------------------------------------------
    // Pantalla de acceso denegado (switch account)
    // ----------------------------------------------------------------

    function initDeniedScreen() {
        var btn = document.getElementById('pc-btn-switch-account');
        if ( ! btn ) return;

        btn.addEventListener('click', function() {
            if ( typeof google === 'undefined' || ! google.accounts ) return;

            // Cerrar sesión de Google para forzar selección de otra cuenta
            google.accounts.id.disableAutoSelect();

            // Redirigir al mismo URL para que muestre login requerido
            logout(function() {
                window.location.reload();
            });
        });
    }

    // ----------------------------------------------------------------
    // Feedback UI
    // ----------------------------------------------------------------

    function setButtonLoading(loading) {
        var btn = document.getElementById('pc-btn-google');
        if ( ! btn ) return;

        btn.disabled    = loading;
        btn.textContent = loading ? 'Verificando...' : '';

        if ( ! loading ) {
            // Restaurar contenido original del botón
            btn.innerHTML = '<svg width="18" height="18" viewBox="0 0 18 18" xmlns="http://www.w3.org/2000/svg">' +
                '<path d="M17.64 9.2c0-.637-.057-1.251-.164-1.84H9v3.481h4.844c-.209 1.125-.843 2.078-1.796 2.717v2.258h2.908c1.702-1.567 2.684-3.875 2.684-6.615z" fill="#4285F4"/>' +
                '<path d="M9 18c2.43 0 4.467-.806 5.956-2.18l-2.908-2.259c-.806.54-1.837.86-3.048.86-2.344 0-4.328-1.584-5.036-3.711H.957v2.332A8.997 8.997 0 0 0 9 18z" fill="#34A853"/>' +
                '<path d="M3.964 10.71A5.41 5.41 0 0 1 3.682 9c0-.593.102-1.17.282-1.71V4.958H.957A8.996 8.996 0 0 0 0 9c0 1.452.348 2.827.957 4.042l3.007-2.332z" fill="#FBBC05"/>' +
                '<path d="M9 3.58c1.321 0 2.508.454 3.44 1.345l2.582-2.58C13.463.891 11.426 0 9 0A8.997 8.997 0 0 0 .957 4.958L3.964 7.29C4.672 5.163 6.656 3.58 9 3.58z" fill="#EA4335"/>' +
                '</svg> Continuar con Google';
        }
    }

    function showAuthError(message) {
        var container = document.getElementById('pc-google-signin-btn');
        if ( ! container ) return;

        var existing = document.getElementById('pc-auth-error');
        if ( existing ) existing.remove();

        var alert = document.createElement('div');
        alert.id        = 'pc-auth-error';
        alert.className = 'pc-alert pc-alert--error';
        alert.style.marginTop = '16px';
        alert.textContent = message;

        container.parentNode.insertBefore(alert, container.nextSibling);
    }

    function showAccessDenied(email) {
        var state = document.querySelector('.pc-state');
        if ( ! state ) return;

        state.innerHTML =
            '<div class="pc-state__icon pc-state__icon--red">' +
            '<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#dc3545" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>' +
            '</div>' +
            '<h1 class="pc-state__title">Acceso denegado</h1>' +
            '<p class="pc-state__text">Este contrato fue asignado a otra cuenta y no puede ser visualizado desde este correo.</p>' +
            (email ? '<p style="font-size:13px;color:#aaa;margin-bottom:24px;">Cuenta utilizada: <strong>' + escapeHtml(email) + '</strong></p>' : '') +
            '<button class="pc-btn pc-btn--google" id="pc-btn-retry" type="button">' +
            '<svg width="18" height="18" viewBox="0 0 18 18" xmlns="http://www.w3.org/2000/svg"><path d="M17.64 9.2c0-.637-.057-1.251-.164-1.84H9v3.481h4.844c-.209 1.125-.843 2.078-1.796 2.717v2.258h2.908c1.702-1.567 2.684-3.875 2.684-6.615z" fill="#4285F4"/><path d="M9 18c2.43 0 4.467-.806 5.956-2.18l-2.908-2.259c-.806.54-1.837.86-3.048.86-2.344 0-4.328-1.584-5.036-3.711H.957v2.332A8.997 8.997 0 0 0 9 18z" fill="#34A853"/><path d="M3.964 10.71A5.41 5.41 0 0 1 3.682 9c0-.593.102-1.17.282-1.71V4.958H.957A8.996 8.996 0 0 0 0 9c0 1.452.348 2.827.957 4.042l3.007-2.332z" fill="#FBBC05"/><path d="M9 3.58c1.321 0 2.508.454 3.44 1.345l2.582-2.58C13.463.891 11.426 0 9 0A8.997 8.997 0 0 0 .957 4.958L3.964 7.29C4.672 5.163 6.656 3.58 9 3.58z" fill="#EA4335"/></svg>' +
            ' Ingresar con otra cuenta' +
            '</button>';

        var retryBtn = document.getElementById('pc-btn-retry');
        if ( retryBtn ) {
            retryBtn.addEventListener('click', function() {
                logout(function() { window.location.reload(); });
            });
        }
    }

    // ----------------------------------------------------------------
    // Logout del contrato
    // ----------------------------------------------------------------

    function logout(callback) {
        var formData = new FormData();
        formData.append('action', 'pc_logout');
        formData.append('nonce',  cfg.logoutNonce);

        fetch(cfg.ajaxUrl, {
            method:      'POST',
            body:        formData,
            credentials: 'same-origin',
        })
        .then(function() {
            if ( callback ) callback();
        })
        .catch(function() {
            if ( callback ) callback();
        });
    }

    // ----------------------------------------------------------------
    // Utilidades
    // ----------------------------------------------------------------

    function escapeHtml(str) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    // ----------------------------------------------------------------
    // Init
    // ----------------------------------------------------------------

    document.addEventListener('DOMContentLoaded', function() {
        initLoginScreen();
        initDeniedScreen();
    });

    // Google llama a este callback cuando su librería cargó
    window.pcInitGoogle = function() {
        initGoogleSignIn();
    };

})();

/* Google Sign-In + validación de formulario de firma */

(function () {
    'use strict';

    /* ---- Google Sign-In ---- */

    /**
     * Callback que Google llama cuando el usuario selecciona su cuenta.
     * Recibe el credential (JWT id_token) y lo envía al backend via POST.
     *
     * @param {Object} response  Objeto con response.credential
     */
    window.pcHandleGoogleCredential = function (response) {
        if (!response || !response.credential) {
            console.error('PC: no credential received from Google');
            return;
        }

        if (typeof pcData === 'undefined') {
            console.error('PC: pcData not defined');
            return;
        }

        var form = document.createElement('form');
        form.method = 'POST';
        form.action = pcData.authEndpoint;

        var fields = {
            pc_action: 'pc_google_auth',
            credential: response.credential,
            _wpnonce: pcData.authNonce,
        };

        Object.keys(fields).forEach(function (key) {
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = key;
            input.value = fields[key];
            form.appendChild(input);
        });

        document.body.appendChild(form);
        form.submit();
    };

    /**
     * Inicializa el botón de Google Sign-In personalizado.
     * Se llama cuando la librería de Google (GSI) está lista.
     */
    function initGoogleSignIn() {
        if (typeof google === 'undefined' || typeof pcData === 'undefined') {
            return;
        }

        if (!pcData.googleClientId) {
            console.warn('PC: Google Client ID no configurado en Ajustes > Pylaris Contracts.');
            return;
        }

        google.accounts.id.initialize({
            client_id: pcData.googleClientId,
            callback: window.pcHandleGoogleCredential,
            auto_select: false,
            cancel_on_tap_outside: true,
        });

        /* Renderizar en el div oficial si existe (One Tap) */
        var oneTapDiv = document.getElementById('g_id_onload');
        if (oneTapDiv) {
            google.accounts.id.prompt();
        }

        /* Conectar el botón manual (pantalla login-required y access-denied) */
        var btn = document.getElementById('pc-btn-google');
        if (btn) {
            btn.addEventListener('click', function () {
                google.accounts.id.prompt(function (notification) {
                    if (notification.isSkippedMoment() || notification.isDismissedMoment()) {
                        /* Si el usuario cierra el One Tap, abrir la selección de cuenta */
                        google.accounts.id.renderButton(
                            document.createElement('div'),
                            { theme: 'outline', size: 'large' }
                        );
                    }
                });
            });
        }

        /* Botón "Ingresar con otra cuenta" (pantalla access-denied) */
        var switchBtn = document.getElementById('pc-btn-switch-account');
        if (switchBtn) {
            switchBtn.addEventListener('click', function () {
                google.accounts.id.revoke(pcData.contractToken, function () {
                    google.accounts.id.prompt();
                });
            });
        }
    }

    /* ---- Validación del formulario de firma ---- */

    var signForm = document.getElementById('pc-sign-form');
    if (signForm) {
        var signBtn      = document.getElementById('pc-btn-sign');
        var nameInput    = document.getElementById('signed_name');
        var dniInput     = document.getElementById('signed_dni_cuit');
        var checkbox     = document.getElementById('accepted_checkbox');

        signForm.addEventListener('submit', function (e) {
            var errors = [];

            if (!nameInput || !nameInput.value.trim()) {
                errors.push('Ingresá tu nombre completo.');
            }
            if (!dniInput || !dniInput.value.trim()) {
                errors.push('Ingresá tu DNI o CUIT.');
            }
            if (!checkbox || !checkbox.checked) {
                errors.push('Debés aceptar el acuerdo antes de firmar.');
            }

            if (errors.length) {
                e.preventDefault();
                alert(errors.join('\n'));
                return;
            }

            if (signBtn) {
                signBtn.disabled = true;
                signBtn.textContent = 'Procesando...';
            }
        });
    }

    /* ---- Init ---- */

    /* Si GSI ya cargó, inicializar ahora. Si no, esperar al evento. */
    if (typeof google !== 'undefined') {
        initGoogleSignIn();
    } else {
        document.addEventListener('DOMContentLoaded', function () {
            /* Dar tiempo a que el script de Google cargue */
            setTimeout(initGoogleSignIn, 500);
        });
    }

    /* El script de GSI llama a window.onGoogleLibraryLoad cuando está listo */
    window.onGoogleLibraryLoad = initGoogleSignIn;

})();
