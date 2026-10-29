document.addEventListener('DOMContentLoaded', function () {
  var toggle = document.querySelector('.menu-toggle');
  if (toggle) {
    toggle.addEventListener('click', function () {
      document.querySelector('.pc-sidebar')?.classList.toggle('pc-sidebar-hide');
    });
  }
  document.querySelectorAll('[data-confirm]').forEach(function (element) {
    element.addEventListener('click', function (event) {
      if (!window.confirm(element.dataset.confirm)) event.preventDefault();
    });
  });
});
