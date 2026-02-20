    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.0.0/dist/chart.umd.min.js"></script>
    
    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Custom JS -->
    <script>
        // Global JavaScript functions
        function showAlert(type, message, title = '') {
            Swal.fire({
                icon: type,
                title: title || (type === 'success' ? 'Success!' : 'Error!'),
                text: message,
                confirmButtonColor: '#667eea'
            });
        }
        
        function showConfirm(message, callback, title = 'Are you sure?') {
            Swal.fire({
                title: title,
                text: message,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, proceed!'
            }).then((result) => {
                if (result.isConfirmed) {
                    callback();
                }
            });
        }
        
        // Initialize DataTables
        function initDataTable(selector, options = {}) {
            const defaultOptions = {
                responsive: true,
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                order: [[0, 'desc']]
            };
            
            return $(selector).DataTable(Object.assign(defaultOptions, options));
        }
        
        // Initialize Select2
        function initSelect2(selector, options = {}) {
            const defaultOptions = {
                theme: 'bootstrap-5',
                placeholder: 'Select...'
            };
            
            return $(selector).select2(Object.assign(defaultOptions, options));
        }
        
        // Form validation helper
        function validateForm(formSelector) {
            let isValid = true;
            
            $(formSelector + ' [required]').each(function() {
                const $field = $(this);
                const value = $field.val().trim();
                
                if (!value) {
                    $field.addClass('is-invalid');
                    isValid = false;
                } else {
                    $field.removeClass('is-invalid').addClass('is-valid');
                }
            });
            
            return isValid;
        }
        
        // Auto-hide alerts
        $(document).ready(function() {
            setTimeout(function() {
                $('.alert:not(.alert-permanent)').fadeOut();
            }, 5000);
        });
    </script>
    
    <?= $additional_js ?? '' ?>
</body>
</html>