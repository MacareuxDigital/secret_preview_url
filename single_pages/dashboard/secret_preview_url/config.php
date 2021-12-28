<?php defined('C5_EXECUTE') or die("Access Denied."); ?>
<form method="post" action="<?php echo $this->action('save_settings'); ?>">

    <?php echo $token->output('save_settings'); ?>

    <fieldset>
        <legend><?php echo t('URL Settings') ?></legend>
        <div class="form-group">
            <?= $form->label('lifetime', t('Expiration Lifetime')); ?>
            <div class="input-group">
                <?= $form->number('lifetime', $lifetime); ?>
                <div class="input-group-addon input-group-text"><?= t('Minutes'); ?></div>
            </div>
        </div>
    </fieldset>
    <div class="ccm-dashboard-form-actions-wrapper">
        <div class="ccm-dashboard-form-actions">
            <button class="pull-right btn btn-success" type="submit"><?php echo t('Save') ?></button>
        </div>
    </div>

</form>
