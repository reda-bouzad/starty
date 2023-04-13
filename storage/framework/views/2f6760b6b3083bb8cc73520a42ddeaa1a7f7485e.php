
:root {
<?php $__currentLoopData = $colors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    --colors-primary-<?php echo e($key); ?>: <?php echo e($value); ?>;
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
}<?php /**PATH C:\Users\devops\Desktop\projects_starty\startybackend-develop\storage\framework\views/0d281c0946fa64beae197110d6e2b5333494f902.blade.php ENDPATH**/ ?>