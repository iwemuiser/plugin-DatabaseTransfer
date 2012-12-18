<form id="databasetransfer" method="post" action="">
<?php
    $colNames = $this->columnNames;
    $colExamples = $this->columnExamples;
?>
    <table id="column-mappings" class="simple" cellspacing="0" cellpadding="0">
    <thead>
    <tr>
        <th>Column</th>
        <th>Example from Table</th>
        <th>Map To Element</th>
        <th>Use HTML?</th>
        <th>Tags?</th>
        <th>File?</th>
        <th>Delimiter (repeat fields)</th>
    </tr>
    </thead>
    <tbody>

<?php 
    
for($i = 0; $i < count($colNames); $i++): ?>
        <tr>
        <td><strong><?php echo html_escape($colNames[$i]); ?></strong></td>
        <td>&quot;<?php echo html_escape($colExamples[$colNames[$i]]); ?>&quot;</td>
        <?php echo $this->form->getSubForm("row$i"); ?>
        </tr>
<?php endfor; ?>
<!--	<tr><td colspan="7"><hr></td></tr>
	<tr>
		<td><strong><?php echo "Additional Value"; ?></strong></td>
		<?php echo $this->form->getSubForm("additional"); ?> -->
</tr>
    </tbody>
    </table>
    <fieldset>
    <?php echo $this->form->submit; ?>
    </fieldset>
</form>
