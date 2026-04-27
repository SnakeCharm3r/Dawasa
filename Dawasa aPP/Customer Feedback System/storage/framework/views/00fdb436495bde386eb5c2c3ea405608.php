   <!-- JAVASCRIPT -->
   <script src="<?php echo e(asset('assets/libs/bootstrap/js/bootstrap.bundle.min.js')); ?>"></script>
   <script src="<?php echo e(asset('assets/libs/simplebar/simplebar.min.js')); ?>"></script>
   <script src="<?php echo e(asset('assets/js/plugins.js')); ?>"></script>

   <!-- list js-->
   <script src="<?php echo e(asset('assets/libs/list.js/list.min.js')); ?>"></script>
   <script src="<?php echo e(asset('assets/libs/list.pagination.js/list.pagination.min.js')); ?>"></script>

   <!-- apexcharts -->
   <script src="<?php echo e(asset('assets/libs/apexcharts/apexcharts.min.js')); ?>"></script>

   <!--dashboard learning init js-->
   <script src="<?php echo e(asset('assets/js/pages/dashboard-learning.init.js')); ?>"></script>

   <!-- App js -->
   <script src="<?php echo e(asset('assets/js/app.js')); ?>"></script>



   <!-- Vector map-->
   <script src="<?php echo e(asset('assets/libs/jsvectormap/js/jsvectormap.min.js')); ?>"></script>
   <script src="<?php echo e(asset('assets/libs/jsvectormap/maps/world-merc.js')); ?>"></script>

   <!--Swiper slider js-->
   <script src="<?php echo e(asset('assets/libs/swiper/swiper-bundle.min.js')); ?>"></script>

   <!-- Tom Select JS -->
   <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>

   <script src="<?php echo e(asset('assets/libs/list.js/list.min.js')); ?>"></script>

   <!-- Dashboard init -->
   <script src="<?php echo e(asset('assets/js/pages/dashboard-ecommerce.init.js')); ?>"></script>

   <script>
      document.addEventListener('DOMContentLoaded', function () {
         const sidebarMenu = document.querySelector('.app-menu.navbar-menu');

         if (!sidebarMenu) {
            return;
         }

         const syncSidebarBrandState = function () {
            const sidebarSize = document.documentElement.getAttribute('data-sidebar-size');
            const isCollapsed = sidebarSize === 'sm' || sidebarSize === 'sm-hover';

            sidebarMenu.classList.toggle('sidebar-brand-collapsed', isCollapsed);
         };

         syncSidebarBrandState();

         const sidebarObserver = new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
               if (mutation.type === 'attributes' && mutation.attributeName === 'data-sidebar-size') {
                  syncSidebarBrandState();
               }
            });
         });

         sidebarObserver.observe(document.documentElement, {
            attributes: true,
            attributeFilter: ['data-sidebar-size']
         });

         window.addEventListener('resize', syncSidebarBrandState);
      });
   </script>

   <?php if(session('toast')): ?>
   <script>
      document.addEventListener('DOMContentLoaded', function () {
         const toneMap = {
            success: 'success',
            error: 'danger',
            warning: 'warning',
            info: 'info'
         };
         const toastTone = toneMap['<?php echo e(session('toast_type', 'success')); ?>'] || 'success';
         const container = document.createElement('div');
         container.className = 'toast-container position-fixed top-0 end-0 p-3';
         container.style.zIndex = '1080';
         container.innerHTML = '<div class="toast align-items-center text-bg-' + toastTone + ' border-0 shadow" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="3500">'
            + '<div class="d-flex">'
            + '<div class="toast-body"><?php echo e(addslashes(session('toast'))); ?></div>'
            + '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>'
            + '</div>'
            + '</div>';

         document.body.appendChild(container);
         const toastElement = container.querySelector('.toast');
         const toast = new bootstrap.Toast(toastElement);

         toastElement.addEventListener('hidden.bs.toast', function () {
            container.remove();
         });

         toast.show();
      });
   </script>
   <?php endif; ?>
<?php /**PATH /var/www/Customer Feedback System/resources/views/includes/scripts.blade.php ENDPATH**/ ?>