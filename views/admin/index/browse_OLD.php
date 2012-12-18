<?php 
    echo head(array('title' => 'MySQL Database Import', 
				'bodyclass' => 'primary', 
        		'content_class' => 'horizontal-nav'));
?>
<h1>MySQL Database Transfer</h1>

<div id="primary">
    <h2>Status</h2>
    <?php echo flash(); ?>
    <div class="pagination"><?php echo pagination_links(); ?></div>
    <table class="simple" cellspacing="0" cellpadding="0">
        <thead>
            <tr>
                <th>Import Date</th>
                <th>Database Name</th>
                <th>Item Count</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($databasetransfer_imports as $databaseTransfer): ?>
            <tr>
                <td><?php echo html_escape($databaseTransfer->added); ?></td>
                <td><?php echo html_escape($databaseTransfer->original_dbname); ?></td>
                <td><?php echo $databaseTransfer->getProgress(); ?></td>
                <td><?php echo html_escape(Inflector::humanize($databaseTransfer->status)); ?></td>
                <?php
                    if ($databaseTransfer->isFinished() 
                        || $databaseTransfer->isStopped()
                        || $databaseTransfer->isError()): ?>
                    <td><?php echo delete_button($this->url(
                        array('action' => 'undo-import',
                              'id' => $databaseTransfer->id),
                        'default'),
                        'undo_import',
                        'Undo Import',
                        array('class' => 'db-undo-import delete-button')); ?>
                <?php elseif ($databaseTransfer->isUndone()): ?>
                    <td><?php echo delete_button($this->url(
                        array('action' => 'clear-history',
                              'id' => $databaseTransfer->id),
                        'default'),
                        'clear_history',
                        'Clear History',
                        array('class' => 'table-clear-history delete-button')); ?>
                    </td>
                <?php else: ?>
                    <td>No button yet!</td>
                <?php endif; ?>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php 
    foot(); 
?>
