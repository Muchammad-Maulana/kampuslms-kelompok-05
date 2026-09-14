<?php
	$nama = '<script>alert("XSS berhasil dijalankan")</script>';
?>

<h2>Dengan &#123;&#123; &#125;&#125;</h2>
<p><?php echo e($nama); ?></p>

<h2>Dengan &#123;!! !!&#125;</h2>
<p>{ $nama }</p>
<?php /**PATH C:\Users\Linggar\Downloads\proweb\kampuslms-kelompok-05\test\resources\views/test break.blade.php ENDPATH**/ ?>