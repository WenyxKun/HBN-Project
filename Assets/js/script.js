// Load overlay transaksi dari file terpisah
document.getElementById('btnShowOverlay').addEventListener('click', function() {
    const overlay = document.getElementById('transactionOverlay');
    
    // Nonaktifkan scroll body
    document.body.classList.add('body-no-scroll');
    
    // Fetch file transaction.html
    fetch('transaction.html')
        .then(response => response.text())
        .then(html => {
            overlay.innerHTML = html;
            overlay.style.display = 'flex'; // Gunakan flex untuk centering
            
      // Initialize Font Awesome (if needed)
      if(!document.querySelector('.fa')) {
        const faScript = document.createElement('script');
        faScript.src = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/js/all.min.js';
        document.head.appendChild(faScript);
      }
      
      // Interval selector functionality
      const decreaseBtn = overlay.querySelector('#decreaseBtn');
      const increaseBtn = overlay.querySelector('#increaseBtn');
      const intervalInput = overlay.querySelector('#intervalInput');
      
      decreaseBtn.addEventListener('click', () => {
        let currentValue = parseInt(intervalInput.value);
        if (currentValue > 2) {
          intervalInput.value = currentValue - 1;
        }
      });
      
      increaseBtn.addEventListener('click', () => {
        let currentValue = parseInt(intervalInput.value);
        if (currentValue < 5) {
          intervalInput.value = currentValue + 1;
        }
      });
      
      intervalInput.addEventListener('input', () => {
        let val = parseInt(intervalInput.value);
        if (isNaN(val) || val < 2) {
          intervalInput.value = 2;
        } else if (val > 5) {
          intervalInput.value = 5;
        }
      });
      
      // Close buttons
      overlay.querySelector('#btnCloseOverlay').addEventListener('click', closeOverlay);
      overlay.querySelector('#btnCancel').addEventListener('click', closeOverlay);
    })
    .catch(error => {
      console.error('Error loading overlay:', error);
      closeOverlay();
    });
    
  function closeOverlay() {
    overlay.style.display = 'none';
    document.body.classList.remove('body-no-scroll');
  }
});