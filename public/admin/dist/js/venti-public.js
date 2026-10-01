(function () {
  'use strict';

  document.addEventListener('click', function (event) {
    var toggle = event.target.closest('[data-password-toggle]');

    if (!toggle) {
      return;
    }

    var input = document.getElementById(toggle.getAttribute('data-password-toggle'));

    if (!input) {
      return;
    }

    var shouldShow = input.type === 'password';

    input.type = shouldShow ? 'text' : 'password';
    toggle.setAttribute('aria-pressed', shouldShow ? 'true' : 'false');
    toggle.setAttribute('aria-label', shouldShow ? 'Ocultar contraseña' : 'Mostrar contraseña');
    toggle.textContent = shouldShow ? 'Ocultar' : 'Mostrar';
  });
}());
