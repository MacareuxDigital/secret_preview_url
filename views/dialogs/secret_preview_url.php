<?php
defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Asset\AssetGroup;
use Concrete\Core\Asset\CssAsset;
use Concrete\Core\Asset\JavascriptAsset;
use Concrete\Core\Form\Service\Form;
use Concrete\Core\Form\Service\Widget\DateTime as DateTimeWidget;
use Concrete\Core\Form\Service\Widget\UserSelector;
use Concrete\Core\Localization\Service\Date;
use Concrete\Core\Url\Resolver\Manager\ResolverManagerInterface;
use Concrete\Core\User\UserInfoRepository;
use Concrete\Core\Validation\CSRF\Token;
use Concrete5cojp\SecretPreviewUrl\Entity\Signature;

/* @var AssetGroup $selectize */
/* @var Form $form */
/* @var UserSelector $userSelector */
/* @var int $uID */
/* @var DateTimeWidget $dateTime */
/* @var Token $token */
/* @var Date $dh */
/* @var ResolverManagerInterface $resolver */
/* @var UserInfoRepository $userInfoRepository */
/* @var array $signatures */

?>
<script>
    <?php
    foreach ($selectize->getAssetPointers() as $pointer) {
        $asset = $pointer->getAsset();
        if ($asset instanceof CssAsset) {
            echo 'ConcreteAssetLoader.loadCSS("' . $asset->getAssetURL() . '");';
        } elseif ($asset instanceof JavascriptAsset) {
            echo 'ConcreteAssetLoader.loadJavaScript("' . $asset->getAssetURL() . '");';
        }
    }
    ?>
</script>
<div class="ccm-ui">
    <form action="<?= h($view->action('add')); ?>" method="post" id="ccm-secret-url-dialog">
        <?= $token->output('add'); ?>
        <div class="form-group">
            <?= $form->label('uID', t('Preview As')); ?>
            <?= $userSelector->quickSelect('uID', $uID); ?>
        </div>
        <div class="form-group">
            <?= $form->label('preview_date', t('Preview At')); ?>
            <?= $dateTime->datetime('preview_date'); ?>
        </div>
        <button type="submit" class="btn btn-primary btn-s"><?= t('Add Preview URL'); ?></button>
    </form>
    <hr>
    <h5><?= t('Generated Preview URL'); ?></h5>
    <table class="table table-striped" id="ccm-secret-url-table">
        <thead>
        <tr>
            <th><?= t('Link'); ?></th>
            <th><?= t('User'); ?></th>
            <th><?= t('Preview Date'); ?></th>
            <th><?= t('Expiration Date'); ?></th>
            <th><?= t('Action'); ?></th>
        </tr>
        </thead>
        <tbody>
        <?php
        /** @var Signature $signature */
        foreach ($signatures as $signature) {
            $viewUrl = $resolver->resolve(['/ccm/secret_preview_url', 'view', $signature->getSignatureString()]);
            $deleteUrl = $resolver->resolve(['/ccm/secret_preview_url', 'dialog/delete', $signature->getSignatureString()]);
            $ui = $userInfoRepository->getByID($signature->getUserID());
            if (is_object($ui)) {
                $user = $ui->getUserDisplayName();
            } else {
                $user = t('Guest');
            }
            $previewDateTime = $signature->getPreviewDate();
            if (is_object($previewDateTime)) {
                $previewDate = $dh->formatDateTime($previewDateTime, true, true);
            } else {
                $previewDate = t('Current Time');
            }
            $expirationDateTime = $signature->getExpirationDate();
            if (is_object($expirationDateTime)) {
                $expirationDate = $dh->formatDateTime($expirationDateTime, true, true);
            } else {
                $expirationDate = t('No Expiration Date');
            } ?>
            <tr>
                <td><a href="<?= h($viewUrl); ?>" target="_blank"><i class="fa fa-external-link" aria-hidden="true"></i></a>
                </td>
                <td><?= h($user); ?></td>
                <td><?= h($previewDate); ?></td>
                <td><?= h($expirationDate); ?></td>
                <td>
                    <form action="<?= h($deleteUrl); ?>" class="ccm-delete-secret-url" method="post">
                        <?= $token->output('delete'); ?>
                        <button type="submit" class="btn btn-danger btn-xs"><?= t('Delete'); ?></button>
                    </form>
                </td>
            </tr>
            <?php
        }
        ?>
        </tbody>
    </table>
</div>

<script type="text/javascript">
    $(function () {
        $("#ccm-secret-url-dialog").ajaxForm({
            dataType: 'json',
            beforeSubmit: function (r) {
                jQuery.fn.dialog.showLoader();
            },
            success: function (r) {
                ConcreteToolbar.disableDirectExit();
                jQuery.fn.dialog.hideLoader();
                if (r.url) {
                    $('#ccm-secret-url-table tbody').append('<tr>' +
                        '<td><a href="' + r.url + '" target="_blank"><i class="fa fa-external-link" aria-hidden="true"></i></a></td>' +
                        '<td>' + r.user + '</td>' +
                        '<td>' + r.preview + '</td>' +
                        '<td>' + r.expiration + '</td>' +
                        '<td><span class="label label-success"><?= t('Success'); ?></span></td></tr>');
                }
            },
            error: function (r) {
                ConcreteToolbar.disableDirectExit();
                jQuery.fn.dialog.hideLoader();
                var msg = r.responseText;
                if (r.responseJSON && r.responseJSON.errors) {
                    msg = r.responseJSON.errors.join("<br/>");
                }
                ConcreteAlert.dialog('<?=t('Error'); ?>', msg);
            }
        });
        $('.ccm-delete-secret-url').ajaxForm({
            dataType: 'json',
            beforeSubmit: function () {
                jQuery.fn.dialog.showLoader();
            },
            success: function (r, s, x, e) {
                ConcreteToolbar.disableDirectExit();
                jQuery.fn.dialog.hideLoader();
                e.parents('tr').fadeOut();
            },
            error: function (r) {
                ConcreteToolbar.disableDirectExit();
                jQuery.fn.dialog.hideLoader();
                var msg = r.responseText;
                if (r.responseJSON && r.responseJSON.errors) {
                    msg = r.responseJSON.errors.join("<br/>");
                }
                ConcreteAlert.dialog('<?=t('Error'); ?>', msg);
            }
        });
    });
</script>