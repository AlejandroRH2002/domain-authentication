define(['jquery', 'core/str'], function($, str) {
    return {
        init: function() {
            $(document).ready(function() {
                // Definir dominios autorizados (igual que en PHP).
                var authorizedDomains = [
                    'uady.mx',
                    'fmat.uady.mx',
                    'alumnos.uady.mx',
                    'correo.uady.mx'
                ];

                var emailInput = $('#id_email');
                var dependentFields = $('[data-domain-dependent="1"]').closest('.fitem');

                function isInstitutionalDomain(domain) {
                    if (authorizedDomains.indexOf(domain) !== -1) {
                        return true;
                    }

                    return authorizedDomains.some(function(authorizedDomain) {
                        return domain.length > authorizedDomain.length &&
                            domain.slice(-(authorizedDomain.length + 1)) === '.' + authorizedDomain;
                    });
                }

                function toggleDependentFields() {
                    var email = emailInput.val().trim().toLowerCase();
                    var parts = email.split('@');
                    var domain = (parts.length === 2) ? parts[1] : '';

                    var isExternal = (domain !== '' && !isInstitutionalDomain(domain));

                    if (isExternal) {
                        dependentFields.show();
                    } else {
                        dependentFields.hide();
                        // Limpiar valores de los campos ocultos.
                        dependentFields.find('input, textarea, select').each(function() {
                            var $el = $(this);
                            if ($el.is('select')) {
                                // Para selects, seleccionar la primera opción (que debería ser vacía).
                                $el.val($el.find('option:first').val());
                            } else {
                                $el.val('');
                            }
                        });
                    }
                }

                // Ejecutar al cargar y al cambiar el email.
                toggleDependentFields();
                emailInput.on('input change', function() {
                    toggleDependentFields();
                });
            });
        }
    };
});