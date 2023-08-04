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
use Concrete\Core\Site\Config\Liaison;
use Concrete\Core\Url\Resolver\Manager\ResolverManagerInterface;
use Concrete\Core\User\User;
use Concrete\Core\User\UserInfoRepository;
use Concrete\Core\Utility\Service\Identifier;
use Concrete\Core\Validation\CSRF\Token;
use Concrete5cojp\SecretPreviewUrl\Entity\Signature;
use Concrete5cojp\SecretPreviewUrl\Entity\SignatureRepository;
use Concrete5cojp\SecretPreviewUrl\Service\Dashboard;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class SecretPreviewUrl extends Controller
{
    protected $viewPath = '/dialogs/secret_preview_url';

    public function dialog($cID)
    {
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
            'cID' => $cID
        ]);
        $this->set('signatures', $signatures);
    }

    public function add($cID)
    {
        /** @var Token $token */
        $token = $this->app->make('token');
        /** @var Date $dh */
        $dh = $this->app->make('helper/date');
        /** @var ErrorList $error */
        $error = $this->app->make('error');
        if (!$token->validate('add')) {
            $error->add($token->getErrorMessage());
        }

        /** @var UserInfoRepository $repository */
        $repository = $this->app->make(UserInfoRepository::class);
        $ui = $repository->getByID($this->post('uID'));
        /** @var DateTime $dt */
        $dt = $this->app->make('helper/form/date_time');
        $previewDate = $dt->translate('preview_date', $this->post(), true);

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
            $signature->setCollectionID($cID);
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
        } else {
            return $error->createResponse();
        }
    }

    public function delete($signature)
    {
        /** @var Token $token */
        $token = $this->app->make('token');
        /** @var ErrorList $error */
        $error = $this->app->make('error');
        if (!$token->validate('delete')) {
            $error->add($token->getErrorMessage());
        }

        if (!$error->has()) {
            /** @var EntityManagerInterface $em */
            $em = $this->app->make(EntityManagerInterface::class);
            /** @var SignatureRepository $repository */
            $repository = $em->getRepository(Signature::class);
            $signatureEntity = $repository->findOneBySignature($signature);
            if (is_object($signatureEntity)) {
                $em->remove($signatureEntity);
                $em->flush();
            }

            $response = new \stdClass();
            $response->responseText = t('Successfully deleted the secret url.');

            return new JsonResponse($response);
        } else {
            return $error->createResponse();
        }
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
     * @param $signature
     * @return bool|Signature
     */
    protected function validateSignature($signature)
    {
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