            </div>
        </main>
    </div>

    <script>
        // Initialize Lucide icons
        lucide.createIcons();

        // Dropdown functionality
        document.addEventListener('DOMContentLoaded', function() {
            const dropdownToggles = document.querySelectorAll('.dropdown-toggle');
            
            dropdownToggles.forEach(toggle => {
                toggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    const dropdownContent = this.nextElementSibling;
                    const chevron = this.querySelector('i[data-lucide="chevron-down"]');
                    
                    // Close all other dropdowns
                    document.querySelectorAll('.dropdown-content').forEach(content => {
                        if (content !== dropdownContent && content.classList.contains('show')) {
                            content.classList.remove('show');
                            const otherChevron = content.previousElementSibling.querySelector('i[data-lucide="chevron-down"]');
                            if (otherChevron) {
                                otherChevron.style.transform = 'rotate(0deg)';
                            }
                        }
                    });
                    
                    // Toggle current dropdown
                    dropdownContent.classList.toggle('show');
                    
                    // Rotate chevron
                    if (chevron) {
                        chevron.style.transform = dropdownContent.classList.contains('show') ? 
                            'rotate(180deg)' : 'rotate(0deg)';
                    }
                });
            });

            // Mark active links based on current page
            const currentPage = window.location.pathname.split('/').pop() || 'dashboard.php';
            
            // Mark main links
            document.querySelectorAll('.sidebar-link').forEach(link => {
                const linkPage = link.getAttribute('href').split('/').pop();
                if (currentPage === linkPage) {
                    link.classList.add('active');
                }
            });
            
            // Mark submenu links
            document.querySelectorAll('.sidebar-submenu-link').forEach(link => {
                const linkPage = link.getAttribute('href').split('/').pop();
                if (currentPage === linkPage) {
                    link.classList.add('active');
                    const dropdownContent = link.closest('.dropdown-content');
                    if (dropdownContent) {
                        dropdownContent.classList.add('show');
                        const toggle = dropdownContent.previousElementSibling;
                        if (toggle) {
                            toggle.classList.add('active');
                            const chevron = toggle.querySelector('i[data-lucide="chevron-down"]');
                            if (chevron) {
                                chevron.style.transform = 'rotate(180deg)';
                            }
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>