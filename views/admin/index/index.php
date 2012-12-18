<?php
$head = array('bodyclass' => 'database-transfer primary', 
				'title' => 'Database Transfer',
				'content_class' => 'horizontal-nav');
echo head($head);
?>
<ul id="section-nav" class="navigation">
    <li class="<?php if (isset($_GET['view'])) {echo 'current';} ?>">
        <a href="<?php echo html_escape(url('database-transfer')); ?>"><?php echo __('Select Database'); ?></a>
    </li>
    <li class="<?php if (isset($_GET['view'])) {echo 'current';} ?>">
        <a href="<?php echo html_escape(url('database-transfer/index/browse')); ?>"><?php echo __('Imported Tables'); ?></a>
    </li>
</ul>
<?php echo flash(); ?>

<div id="primary">
    <h2>Step 1: Insert database Settings</h2>
    <?php echo flash(); ?>
    <?php echo $this->form; ?>
</div>
<?php foot(); ?>