<?php 
    echo head(array('title' => 'MySQL Database Import', 
				'bodyclass' => 'primary', 
        		'content_class' => 'horizontal-nav'));
?>

<div id="primary">
    <h2>Status</h2>
	<table class="full">
	    <thead>
	        <tr>
	            <?php echo browse_sort_links(array(
	                __('Title') => 'title',
	                __('Original DB') => 'orgdb',
	                __('Original table') => 'orgtable',
	                __('Status') => 'status',
					__('Public') => 'is_public',
					__('Featured') => 'is_featured',
					__('By user') => 'db_user',
					), 
					array('link_tag' => 'th scope="col"', 
					'list_tag' => ''));
	            ?>
	        </tr>
	    </thead>
	    <tbody>
	    <?php foreach (loop('database_transfer_imports') as $databaseTransfer): ?>
	        <tr>
	            <td>
	                <span class="title">
                       	<?php echo metadata('database_transfer_imports', 'added'); ?>
	                </span>
	                <ul class="action-links group">
	                    <?php if ($databaseTransfer->isFinished()  || $databaseTransfer->isStopped() || $databaseTransfer->isError()): ?>
						<li><a class="delete-confirm" href="<?php echo $this->url(array('action' => 'undo-import', 'id' => $databaseTransfer->id),'default'); ?>">
	                        <?php echo __('Undo import'); ?>
	                    </a></li>
		                <?php elseif ($databaseTransfer->isUndone()): ?>
						<li><a class="delete-confirm" href="<?php echo $this->url(array('action' => 'clear-history', 'id' => $databaseTransfer->id), 'default'); ?>">
	                        <?php echo __('Clear history'); ?>
	                    </a></li>
	                	<?php else: ?>
	                    <li><font color="red">in progress</font></li>
			            <?php endif; ?>
	                </ul>
	            </td>
	            <td><?php echo metadata('database_transfer_import', 'original_dbname'); ?></td>
	            <td><?php echo metadata('database_transfer_import', 'db_table'); ?></td>
	            <td><?php echo metadata('database_transfer_import', 'status'); ?></td>
	            <td><?php echo metadata('database_transfer_import', 'is_public'); ?></td>
	            <td><?php echo metadata('database_transfer_import', 'is_featured'); ?></td>
	            <td><?php echo metadata('database_transfer_import', 'db_user'); ?></td>
	            </td>
	        </tr>
	    <?php endforeach; ?>
	    </tbody>
	</table>

</div>
<?php 
    foot(); 
?>
