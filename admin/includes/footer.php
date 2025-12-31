</main> <!-- End Main Content Body -->
    </div> <!-- End Main Content Wrapper -->

    <script>
        // Mobile Sidebar Toggle Logic
        const mobileMenuBtn = document.getElementById('mobile-menu-btn');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebar-overlay');

        function toggleSidebar() {
            const isClosed = sidebar.classList.contains('-translate-x-full');
            if (isClosed) {
                // Open
                sidebar.classList.remove('-translate-x-full');
                overlay.classList.remove('hidden');
                // Small delay to allow display:block to apply before opacity transition
                setTimeout(() => {
                    overlay.classList.remove('opacity-0');
                }, 10);
            } else {
                // Close
                sidebar.classList.add('-translate-x-full');
                overlay.classList.add('opacity-0');
                setTimeout(() => {
                    overlay.classList.add('hidden');
                }, 300);
            }
        }

        if(mobileMenuBtn) {
            mobileMenuBtn.addEventListener('click', toggleSidebar);
            overlay.addEventListener('click', toggleSidebar);
        }
    </script>
</body>
</html>