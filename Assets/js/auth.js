// Fungsi untuk handle registrasi
async function handleRegister(e) {
    e.preventDefault();
    
    const form = e.target;
    const submitButton = form.querySelector('button[type="submit"]');
    const originalText = submitButton.innerHTML;
    
    try {
        // Tampilkan loading
        submitButton.disabled = true;
        submitButton.innerHTML = '<span class="spinner">⏳</span> Memproses...';

        const formData = {
            name: form.name.value,
            email: form.email.value,
            phone: form.phone.value,
            password: form.password.value
        };

        const response = await fetch('backend/register.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(formData)
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.message || 'Registrasi gagal');
        }

        await Swal.fire({
            icon: 'success',
            title: 'Berhasil!',
            text: data.message,
            confirmButtonText: 'OK'
        });
        
        window.location.href = 'login.html';

    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Error!',
            text: error.message,
            confirmButtonText: 'Mengerti'
        });
        console.error('Registration error:', error);
    } finally {
        submitButton.disabled = false;
        submitButton.innerHTML = originalText;
    }
}

// Fungsi untuk handle login
async function handleLogin(e) {
    e.preventDefault();
    
    const form = e.target;
    const submitButton = form.querySelector('button[type="submit"]');
    const originalText = submitButton.innerHTML;
    
    try {
        submitButton.disabled = true;
        submitButton.innerHTML = '<span class="spinner">⏳</span> Masuk...';

        const formData = {
            email: form.email.value,
            password: form.password.value
        };

        const response = await fetch('backend/login.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(formData)
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.message || 'Login gagal');
        }

        // Simpan data user di localStorage/session
        localStorage.setItem('user', JSON.stringify(data.data));
        
        await Swal.fire({
            icon: 'success',
            title: 'Berhasil Login!',
            text: `Selamat datang ${data.data.name}`,
            confirmButtonText: 'Lanjut'
        });
        
        window.location.href = 'index.html';

    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Gagal Login',
            text: error.message,
            confirmButtonText: 'Coba Lagi'
        });
    } finally {
        submitButton.disabled = false;
        submitButton.innerHTML = originalText;
    }
}

// Inisialisasi event listener
document.addEventListener('DOMContentLoaded', function() {
    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        registerForm.addEventListener('submit', handleRegister);
    }

    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', handleLogin);
    }
});