<?php 
    echo head(array('title' => 'MySQL Database Import', 
				'bodyclass' => 'primary', 
        		'content_class' => 'horizontal-nav'));
?>
<div id="primary">
    <h2>Step 2: Select Table</h2>
    <?php echo flash(); ?>
    <div class="pagination"><?php echo pagination_links(); ?></div>
    <?php echo $this->form; ?>
    
</div>
<script type="text/javascript">
//<![CDATA[
jQuery(document).ready(function () {
    Omeka.DatabaseTransfer.enableElementMapping();
});
//]]>
</script>
<?php 
    foot(); 
?>
