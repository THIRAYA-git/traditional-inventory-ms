</div> 

    <style>
        footer {
            background-color: var(--card-bg) !important;
            border-top: 1px solid var(--border-color) !important;
            transition: background-color 0.3s ease;
            z-index: 1040;
        }
        footer p {
            color: var(--text-muted) !important;
        }
    </style>

    <footer class="text-center py-2 fixed-bottom">
        <div class="container-fluid">
            <p class="mb-0 small" style="font-size: .65rem;">
                &copy; <?php echo date("Y"); ?> Inventory Management System. All Rights Reserved. | Version 1.0
            </p>
        </div>
    </footer>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const toggleBtn = document.getElementById('theme-toggle');
            const themeIcon = document.getElementById('theme-icon');
            const htmlElement = document.documentElement;

            // 1. On Load: Check Local Storage for saved preference
            const savedTheme = localStorage.getItem('invento-theme') || 'light';
            htmlElement.setAttribute('data-theme', savedTheme);
            updateToggleIcon(savedTheme);

            // 2. Click Event: Toggle Theme
            if (toggleBtn) {
                toggleBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const currentTheme = htmlElement.getAttribute('data-theme');
                    const newTheme = (currentTheme === 'light') ? 'dark' : 'light';
                    
                    htmlElement.setAttribute('data-theme', newTheme);
                    localStorage.setItem('invento-theme', newTheme);
                    updateToggleIcon(newTheme);
                });
            }

            function updateToggleIcon(theme) {
                if (!themeIcon) return; // Guard clause
                
                if (theme === 'dark') {
                    themeIcon.classList.remove('fa-moon');
                    themeIcon.classList.add('fa-sun');
                    themeIcon.style.color = '#ffc107'; // Sun yellow
                } else {
                    themeIcon.classList.remove('fa-sun');
                    themeIcon.classList.add('fa-moon');
                    themeIcon.style.color = '#ffffff'; // Moon white
                }
            }
        });
    </script>
</body>
</html>