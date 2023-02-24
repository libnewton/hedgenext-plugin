
<?php
/** @var $l \OCP\IL10N */
/** @var $_ array */

script('hedgenext', 'settings');
?>
<div id="hdoc-settings" class="section">
    <h2>HedgeNext Editor</h2>
<div>
<input type="url" id="hdocurl" name="docserver" style="width: 25rem;" value="<?php p($_['hdoc.server']); ?>" placeholder="<?php p($l->t('URL of the HedgeNext instance')); ?>" pattern="https?://.*" required />
<br>
        <input id="hdocsave" type="button" value="<?php p($l->t('Save')); ?>" />

        <div class="hdoc-result"></div>
</div>
<br>
<br>
<div>
<h4>Internal Secret Key for Hedge: <br></h4><br><p id="clickkey" style="text-decoration: underline;">Click to show</p><code id="hiddenkey" style="display: none;"><?php p($_['hdoc.secretkey']); ?></code>
</div>
    <!-- <form id="hdoc-preview"> -->

    <!-- </form> -->
</div>
