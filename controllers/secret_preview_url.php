<?php

namespace Concrete\Package\SecretPreviewUrl\Controller;

use Carbon\Carbon;
use Concrete\Core\Asset\AssetGroup;
use Concrete\Core\Asset\AssetList;
use Concrete\Core\Cache\Cache;
use Concrete\Core\Controller\Controller;
use Concrete\Core\Error\ErrorList\ErrorList;
use Concrete\Core\Form\Service\Form;
use Concrete\Core\Form\Service\Widget\DateTime;
use Concrete\Core\Form\Service\Widget\UserSelector;
use Concrete\Core\Http\Request;
use Concrete\Core\Http\Response;
use Concrete\Core\Http\ResponseFactoryInterface;
use Concrete\Core\Localization\Service\Date;
use Concrete\Core\Package\PackageService;
use Concrete\Core\Page\Event;
use Concrete\Core\Page\Page;
use Concrete\Core\Permission\Checker;
use Concrete\Core\Site\Config\Liaison;
use Concrete\Core\Url\Resolver\Manager\ResolverManagerInterface;
use Concrete\Core\User\User;
use Concrete\Core\User\UserInfoRepository;
use Concrete\Core\Utility\Service\Identifier;
use Concrete\Core\Utility\Service\Validation\Numbers;
use Concrete\Core\Validation\CSRF\Token;
use Concrete5cojp\SecretPreviewUrl\Entity\Signature;
use Concrete5cojp\SecretPreviewUrl\Entity\SignatureRepository;
use Concrete5cojp\SecretPreviewUrl\Service\Dashboard;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class SecretPreviewUrl extends Controller
{
    protected $viewPath = '/dialogs/secret_preview_url';

    protected $previewUserDenied = false;

    public function dialog($cID)
    {
        $page = $this->getAuthorizedPage($cID);
        if (!$page instanceof Page) {
            return $page;
        }

        $installedVersion = $this->app->make('config')->get('concrete.version_installed');
        if (version_compare($installedVersion, '9.0.0', '<')) {
            $al = AssetList::getInstance();
            /** @var AssetGroup $selectize */
            $selectize = $al->getAssetGroup('selectize');
            $this->set('selectize', $selectize);
        }

        /** @var Form $form */
        $form = $this->app->make('helper/form');
        $this->set('form', $form);

        /** @var UserSelector $userSelector */
        $userSelector = $this->app->make('helper/form/user_selector');
        $this->set('userSelector', $userSelector);

        /** @var User $u */
        $u = $this->app->make(User::class);
        $uID = (is_object($u)) ? $u->getUserID() : false;
        $this->set('uID', $uID);

        /** @var DateTime $dateTime */
        $dateTime = $this->app->make('helper/form/date_time');
        $this->set('dateTime', $dateTime);

        /** @var Token $token */
        $token = $this->app->make('token');
        $this->set('token', $token);

        /** @var Date $dh */
        $dh = $this->app->make('helper/date');
        $this->set('dh', $dh);

        /** @var ResolverManagerInterface $resolver */
        $resolver = $this->app->make(ResolverManagerInterface::class);
        $this->set('resolver', $resolver);

        /** @var UserInfoRepository $userInfoRepository */
        $userInfoRepository = $this->app->make(UserInfoRepository::class);
        $this->set('userInfoRepository', $userInfoRepository);

        /** @var EntityManagerInterface $em */
        $em = $this->app->make(EntityManagerInterface::class);
        /** @var SignatureRepository $repository */
        $repository = $em->getRepository(Signature::class);
        $signatures = $repository->findBy([
            'cID' => (int) $page->getCollectionID(),
        ]);
        $this->set('signatures', $signatures);
    }

    public function add($cID)
    {
        $page = $this->getAuthorizedPage($cID);
        if (!$page instanceof Page) {
            return $page;
        }

        /** @var Token $token */
        $token = $this->app->make('token');
        /** @var Date $dh */
        $dh = $this->app->make('helper/date');
        /** @var ErrorList $error */
        $error = $this->app->make('error');
        $failureStatus = Response::HTTP_BAD_REQUEST;
        if (!$token->validate('add')) {
            $error->add($token->getErrorMessage());
        }

        $ui = null;
        if (!$error->has()) {
            $resolved = $this->resolvePreviewUser($this->post('uID'), $error);
            if ($resolved === false) {
                $failureStatus = $this->previewUserDenied ? Response::HTTP_FORBIDDEN : Response::HTTP_BAD_REQUEST;
            } else {
                $ui = $resolved;
            }
        }
        if (!$error->has() && is_object($ui) && !(new Checker($page))->canPreviewPageAsUser()) {
            $error->add(t('Access Denied'));
            $failureStatus = Response::HTTP_FORBIDDEN;
        }
        /** @var DateTime $dt */
        $dt = $this->app->make('helper/form/date_time');
        $previewDate = (!$error->has()) ? $dt->translate('preview_date', $this->post(), true) : null;

        if (!$error->has()) {
            /** @var Identifier $identifier */
            $identifier = $this->app->make('helper/validation/identifier');
            $string = $identifier->getString(64);

            /** @var PackageService $service */
            $service = $this->app->make(PackageService::class);
            $package = $service->getClass('secret_preview_url');
            $lifetime = $package->getFileConfig()->get('url.lifetime', 10080); // 1 week
            $expiration = Carbon::now()->addMinutes($lifetime);

            $signature = new Signature();
            $signature->setCollectionID((int) $page->getCollectionID());
            if (is_object($ui)) {
                $signature->setUserID($ui->getUserID());
            }
            if (is_object($previewDate)) {
                $signature->setPreviewDate($previewDate);
            }
            $signature->setExpirationDate($expiration);
            $signature->setSignatureString($string);

            /** @var EntityManagerInterface $em */
            $em = $this->app->make(EntityManagerInterface::class);
            $em->persist($signature);
            $em->flush();

            /** @var ResolverManagerInterface $resolver */
            $resolver = $this->app->make(ResolverManagerInterface::class);

            $response = new \stdClass();
            $response->url = (string)$resolver->resolve(['/ccm/secret_preview_url', 'view', $string]);
            $response->user = (is_object($ui)) ? $ui->getUserDisplayName() : t('Guest');
            $response->preview = (is_object($previewDate)) ? $dh->formatDateTime($previewDate, true, true) : t('Current Time');
            $response->expiration = $dh->formatDateTime($expiration, true, true);

            return new JsonResponse($response);
        }

        return $error->createResponse($failureStatus);
    }

    public function delete($signature)
    {
        $user = $this->app->make(User::class);
        if (!is_object($user) || !$user->isRegistered()) {
            return $this->accessDenied();
        }
        $actor = $this->app->make(UserInfoRepository::class)->getByID($user->getUserID());
        if (!is_object($actor) || !$actor->isActive()) {
            return $this->accessDenied();
        }
        if (!is_string($signature) || preg_match('/\A[a-f0-9]{64}\z/', $signature) !== 1) {
            return $this->invalidRequest(t('Invalid preview link.'));
        }

        /** @var EntityManagerInterface $em */
        $em = $this->app->make(EntityManagerInterface::class);
        /** @var SignatureRepository $repository */
        $repository = $em->getRepository(Signature::class);
        $signatureEntity = $repository->findOneBySignature($signature);
        if (!is_object($signatureEntity)) {
            return $this->invalidRequest(t('Invalid preview link.'));
        }

        $page = $this->getAuthorizedPage($signatureEntity->getCollectionID());
        if (!$page instanceof Page) {
            $dashboard = Page::getByPath('/dashboard/secret_preview_url/url_list');
            $canViewList = is_object($dashboard) && !$dashboard->isError() && (new Checker($dashboard))->canViewPage();
            // 400 means the page is missing or not manageable. 403 keeps the normal page check.
            if ($page->getStatusCode() !== Response::HTTP_BAD_REQUEST || !$canViewList) {
                return $page;
            }
        }

        /** @var Token $token */
        $token = $this->app->make('token');
        if (!$token->validate('delete')) {
            return $this->invalidRequest($token->getErrorMessage());
        }

        $em->remove($signatureEntity);
        $em->flush();

        $response = new \stdClass();
        $response->responseText = t('Successfully deleted the secret url.');

        return new JsonResponse($response);
    }

    public function preview_page($signature)
    {
        $signatureEntity = $this->validateSignature($signature);
        if (is_object($signatureEntity)) {

            /** @var \Concrete\Core\Config\Repository\Liaison $config */
            $config = $this->app->make('config');
            $config->set('concrete.cache.blocks', false);
            $config->set('concrete.cache.pages', false);

            $c = Page::getByID($signatureEntity->getCollectionID(), 'RECENT');
            if (is_object($c) && !$c->isError()) {
                if ($c->isCheckedOut()) {
                    $c->forceCheckIn();
                    // Reload
                    $c = Page::getByID($c->getCollectionID(), 'RECENT');
                }

                $request = Request::getInstance();
                $request->setCurrentPage($c);

                /** @var UserInfoRepository $repository */
                $repository = $this->app->make(UserInfoRepository::class);
                $ui = $repository->getByID($signatureEntity->getUserID());
                if ($ui) {
                    // We need rebinding User singleton
                    // @see https://github.com/concretecms/concretecms/pull/9033
                    $this->app->singleton(User::class, function () use ($ui) {
                        return $ui->getUserObject();
                    });
                } else {
                    $ui = -1;
                }
                $request->setCustomRequestUser($ui);

                $previewDate = $signatureEntity->getPreviewDate();
                if (is_object($previewDate)) {
                    $request->setCustomRequestDateTime($previewDate->format('Y-m-d H:i:s'));
                }

                $pe = new Event($c);
                $pe->setUser($this->app->make(User::class));
                $pe->setRequest($request);
                $this->app['director']->dispatch('on_page_view', $pe);

                $controller = $c->getPageController();
                $view = $controller->getViewObject();

                $view->addHeaderAsset('<meta name="robots" content="noindex, nofollow">');

                $response = new Response();
                $response
                    ->setMaxAge(0)
                    ->setSharedMaxAge(0)
                    ->setPrivate()
                    ->setContent($view->render());

                return $response;
            }
        }

        return $this->app->make(ResponseFactoryInterface::class)->notFound('');
    }

    /**
     * @param mixed $cID
     *
     * @return Page|JsonResponse
     */
    protected function getAuthorizedPage($cID)
    {
        $user = $this->app->make(User::class);
        if (!is_object($user) || !$user->isRegistered()) {
            return $this->accessDenied();
        }
        $actor = $this->app->make(UserInfoRepository::class)->getByID($user->getUserID());
        if (!is_object($actor) || !$actor->isActive()) {
            return $this->accessDenied();
        }

        /** @var Numbers $numbers */
        $numbers = $this->app->make(Numbers::class);
        if (!$numbers->integer($cID, 1)) {
            return $this->invalidRequest(t('Invalid page.'));
        }

        $page = Page::getByID((int) $cID, 'RECENT');
        if (!is_object($page) || $page->isError() || $page->isAdminArea() || $page->isInTrash() || $page->isAliasPageOrExternalLink() || $page->isMasterCollection()) {
            return $this->invalidRequest(t('Invalid page.'));
        }

        $permissions = new Checker($page);
        if (!$permissions->canApprovePageVersions()) {
            return $this->accessDenied();
        }

        return $page;
    }

    /**
     * The posted user ID is who the page is previewed as, not who is allowed to create the link.
     *
     * @param mixed $postedUserId
     * @param ErrorList $error
     *
     * @return \Concrete\Core\User\UserInfo|null|false false when the caller must stop
     */
    protected function resolvePreviewUser($postedUserId, ErrorList $error)
    {
        if ($postedUserId === null || $postedUserId === '' || $postedUserId === false || $postedUserId === 0 || $postedUserId === '0' || $postedUserId === 'null') {
            return null;
        }

        /** @var Numbers $numbers */
        $numbers = $this->app->make(Numbers::class);
        if (!$numbers->integer($postedUserId, 1)) {
            $error->add(t('Invalid user.'));

            return false;
        }

        $ui = $this->app->make(UserInfoRepository::class)->getByID((int) $postedUserId);
        if (!is_object($ui) || !$ui->isActive()) {
            $error->add(t('Invalid user.'));

            return false;
        }
        if ((int) $ui->getUserID() === (int) USER_SUPER_ID) {
            $actor = $this->app->make(User::class);
            if (!$actor->isSuperUser() && !(new Checker())->canSudo()) {
                $this->previewUserDenied = true;
                $error->add(t('Access Denied'));

                return false;
            }
        }

        return $ui;
    }

    /**
     * @return JsonResponse
     */
    protected function accessDenied()
    {
        $error = $this->app->make('error');
        $error->add(t('Access Denied'));

        return $error->createResponse(Response::HTTP_FORBIDDEN);
    }

    /**
     * @param string $message
     *
     * @return JsonResponse
     */
    protected function invalidRequest($message)
    {
        $error = $this->app->make('error');
        $error->add($message);

        return $error->createResponse(Response::HTTP_BAD_REQUEST);
    }

    /**
     * @param $signature
     * @return bool|Signature
     */
    protected function validateSignature($signature)
    {
        if (!is_string($signature) || preg_match('/\A[a-f0-9]{64}\z/', $signature) !== 1) {
            return false;
        }

        /** @var EntityManagerInterface $em */
        $em = $this->app->make(EntityManagerInterface::class);
        /** @var SignatureRepository $signatureRepository */
        $signatureRepository = $em->getRepository(Signature::class);

        // First, remove all expired entity
        $expiredSignatures = $signatureRepository->findExpiredEntities();
        foreach ($expiredSignatures as $expiredSignature) {
            $em->remove($expiredSignature);
            $em->flush();
        }

        /** @var Signature $signatureEntity */
        $signatureEntity = $signatureRepository->findOneBySignature($signature);
        if (is_object($signatureEntity)) {
            $expirationDate = $signatureEntity->getExpirationDate();
            if (is_object($expirationDate)) {
                $now = Carbon::now();
                // Check expiration date just in case.
                if ($now->lessThan($expirationDate)) {
                    return $signatureEntity;
                }
            }
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function on_start()
    {
        // Hacky way to disable toolbar
        $this->app->singleton('helper/concrete/dashboard', Dashboard::class);
        $site = $this->app->make('site')->getSite();
        /** @var Liaison $config */
        $config = $site->getConfigRepository();
        $config->set('seo.tracking.code.header', '<style>div.ccm-page {padding-top: 0px !important;}</style>');
        $config->set('seo.tracking.code.footer', '');
    }
}