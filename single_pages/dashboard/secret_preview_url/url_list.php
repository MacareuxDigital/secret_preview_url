<?php

defined('C5_EXECUTE') or die("Access Denied.");

use Concrete\Core\Page\Page;

/** @var \Concrete5cojp\SecretPreviewUrl\Search\SignatureList $list */
/** @var \Concrete\Core\Search\Pagination\Pagination $pagination */
/** @var \Concrete\Core\User\UserInfoRepository $repository */
/** @var \Concrete\Core\Localization\Service\Date $dh */
/** @var \Concrete\Core\Validation\CSRF\Token $token */

if (is_object($pagination)) {
    $results = $pagination->getCurrentPageResults();
    if (count($results) > 0) {
        ?>
        <div class="ccm-dashboard-content-full">
            <div data-search-element="results">
                <div class="table-responsive">
                    <table class="ccm-search-results-table">
                        <thead>
                        <tr>
                            <th><span><?= t('Page Name') ?></span></th>
                            <th class="<?= $list->getSortClassName('h.expirationDate'); ?>">
                                <a href="<?= $list->getSortURL('h.expirationDate', 'asc'); ?>">
                                    <?= t('Expiration Date') ?>
                                </a>
                            </th>
                            <th><span><?= t('Preview As') ?></span></th>
                            <th><span><?= t('Preview At') ?></span></th>
                            <th><span><?= t('Action') ?></span></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php
                        /** @var \Concrete5cojp\SecretPreviewUrl\Entity\Signature $signature */
                        foreach ($results as $signature) {
                            $cID = $signature->getCollectionID();
                            $c = Page::getByID($cID);
                            $pageName = (is_object($c) && !$c->isError()) ? $c->getCollectionName() : t('Not Found');
                            $uID = $signature->getUserID();
                            $ui = $repository->getByID($uID);
                            $userName = (is_object($ui)) ? $ui->getUserDisplayName() : t('Not Found');
                            $previewDate = $signature->getPreviewDate();
                            $preview = (is_object($previewDate)) ? $dh->formatDateTime($previewDate) : t('No Date');
                            $expirationDate = $signature->getExpirationDate();
                            $expiration = (is_object($previewDate)) ? $dh->formatDateTime($expirationDate) : t('No Date');
                            ?>
                            <tr>
                                <td>
                                    <a href="<?= URL::to('/ccm/secret_preview_url', 'view', $signature->getSignatureString()); ?>"
                                       target="_blank"><?= h($pageName); ?></a></td>
                                <td><?= h($expiration); ?></td>
                                <td><?= h($userName); ?></td>
                                <td><?= h($preview); ?></td>
                                <td>
                                    <form action="<?= URL::to('/ccm/secret_preview_url', 'dialog/delete', $signature->getSignatureString()); ?>"
                                          class="ccm-delete-secret-url" method="post">
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
                <?php if (is_object($pagination)) { ?>
                    <div class="ccm-search-results-pagination">
                        <?= $pagination->renderDefaultView(); ?>
                    </div>
                <?php } ?>
            </div>
        </div>
        <script type="text/javascript">
            $(function () {
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
        <?php
    } else {
        ?>
        <p><?= t('No URL found.'); ?></p>
        <?php
    }
}