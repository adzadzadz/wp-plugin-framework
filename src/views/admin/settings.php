<?php 

use AdzWP\View;

?>


<div class="adz section-container">

<h1>Settings</h1>

<?php echo View::render('widgets/conf-prod-grid-spacing', [
  'model' => $model
]); ?>

</div>