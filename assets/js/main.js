/**
 * main.js - Funciones y utilidades generales de JS para GymSuite
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Toggle visibilidad de password
    const togglePasswordButtons = document.querySelectorAll('.toggle-password');
    togglePasswordButtons.forEach(button => {
        button.addEventListener('click', function() {
            const inputId = this.getAttribute('data-target');
            const input = document.getElementById(inputId);
            
            // Iconos (asumiendo que usan los SVG inline proporcionados en index)
            const iconEye = this.querySelector('.icon-eye');
            const iconEyeSlash = this.querySelector('.icon-eye-slash');

            if (input.type === 'password') {
                input.type = 'text';
                iconEye.classList.add('hidden');
                iconEyeSlash.classList.remove('hidden');
            } else {
                input.type = 'password';
                iconEye.classList.remove('hidden');
                iconEyeSlash.classList.add('hidden');
            }
        });
    });

    // 2. Comportamiento responsive del sidebar
    const toggleSidebarBtn = document.getElementById('toggle_sidebar');
    const sidebar = document.getElementById('sidebar');
    
    if (toggleSidebarBtn && sidebar) {
        toggleSidebarBtn.addEventListener('click', () => {
            // El sidebar en mobile puede estar transformado u oculto, lo controlamos con clase
            sidebar.classList.toggle('sidebar-open');
        });
    }

    // 3. Confirmación modular de borrado
    const confirmActions = document.querySelectorAll('.require-confirm');
    confirmActions.forEach(btn => {
        btn.addEventListener('click', (e) => {
            const text = btn.getAttribute('data-confirm') || "¿Estás seguro de realizar esta acción?";
            if (!confirm(text)) {
                e.preventDefault();
            }
        });
    });
});
