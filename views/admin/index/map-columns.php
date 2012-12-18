<?php 
    print head(array('title' => 'Database Transfer', 'bodyclass' => 'primary', 
        'content_class' => 'horizontal-nav'));
?>
<div id="primary">
    <h2>Step 3: Map Columns To Elements, Tags, or Files</h2>
    <?php echo flash(); ?>

    <?php
    echo $this->form;
    ?>
    
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
