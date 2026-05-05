        </main>
        
        <footer class="py-4 px-5 text-center text-muted small mt-auto">
            &copy; <?= date('Y') ?> <strong>Brasallis ERP</strong>. Desenvolvido com <i class="fas fa-heart text-danger"></i> para sua empresa.
        </footer>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Fechar sidebar ao clicar fora em mobile
        document.addEventListener('click', (e) => {
            if (window.innerWidth <= 992 && 
                !document.getElementById('sidebar').contains(e.target) && 
                !e.target.closest('.btn-bars') && 
                document.body.classList.contains('sidebar-open')) {
                document.body.classList.remove('sidebar-open');
            }
        });
    </script>
</body>
</html>
