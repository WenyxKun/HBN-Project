// Fungsi untuk load komponen
function loadComponents() {
  document.querySelectorAll('[data-include]').forEach(async (element) => {
    const file = element.getAttribute('data-include');
    const response = await fetch(file);
    element.outerHTML = await response.text();
  });
}

// Jalankan saat halaman dimuat
document.addEventListener('DOMContentLoaded', loadComponents);