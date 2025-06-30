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

    <!-- Invoice Modal -->
    <div id="invoiceModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden items-center justify-center" onclick="closeInvoiceModal()">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-3xl max-h-[90vh] overflow-y-auto" onclick="event.stopPropagation()">
            <div class="p-4 sm:p-6 border-b flex justify-between items-center">
                <h3 class="text-lg font-semibold text-slate-800">Detail Invoice</h3>
                <div class="flex items-center space-x-2">
                    <button onclick="printInvoice()" class="text-slate-500 hover:text-blue-600 p-2 rounded-full transition-colors">
                        <i data-lucide="printer" class="w-5 h-5"></i>
                    </button>
                    <button onclick="closeInvoiceModal()" class="text-slate-500 hover:text-red-600 p-2 rounded-full transition-colors">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>
            </div>
            <div id="invoiceModalBody" class="p-4 sm:p-6">
                <!-- Invoice content will be loaded here by JavaScript -->
            </div>
        </div>
    </div>

</body>
</html>