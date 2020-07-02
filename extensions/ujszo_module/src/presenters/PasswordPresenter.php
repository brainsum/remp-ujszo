<?php

namespace Crm\UjszoModule\Presenters;

use Crm\ApplicationModule\Presenters\FrontendPresenter;
use Crm\ApplicationModule\User\DeleteUserData;
use Crm\ApplicationModule\User\DownloadUserData;
use Crm\UsersModule\Auth\Access\AccessToken;
use Crm\UsersModule\Auth\UserManager;
use Crm\UsersModule\Events\NotificationEvent;
use Crm\UsersModule\Forms\ChangePasswordFormFactory;
use Crm\UsersModule\Forms\RequestPasswordFormFactory;
use Crm\UjszoModule\Forms\SetPasswordFormFactory;
use Crm\UsersModule\Forms\UserDeleteFormFactory;
use Crm\UsersModule\Repository\PasswordResetTokensRepository;
use Crm\UsersModule\User\ZipBuilder;
use Nette\Application\Responses\FileResponse;
use Nette\Forms\Form;
use Nette\Utils\Html;
use Nette\Utils\Json;

class PasswordPresenter extends FrontendPresenter {

  private $changePasswordFormFactory;

    private $downloadUserData;

    private $deleteUserData;

    private $requestPasswordFormFactory;

    private $setPasswordFormFactory;

    private $passwordResetTokensRepository;

    private $zipBuilder;

    private $userDeleteFormFactory;

    private $userManager;

    private $accessToken;

    public function __construct(
        ChangePasswordFormFactory $changePasswordFormFactory,
        DownloadUserData $downloadUserData,
        DeleteUserData $deleteUserData,
        RequestPasswordFormFactory $requestPasswordFormFactory,
        SetPasswordFormFactory $resetPasswordFormFactory,
        PasswordResetTokensRepository $passwordResetTokensRepository,
        ZipBuilder $zipBuilder,
        UserDeleteFormFactory $userDeleteFormFactory,
        UserManager $userManager,
        AccessToken $accessToken
    ) {
        parent::__construct();
        $this->changePasswordFormFactory = $changePasswordFormFactory;
        $this->downloadUserData = $downloadUserData;
        $this->deleteUserData = $deleteUserData;
        $this->requestPasswordFormFactory= $requestPasswordFormFactory;
        $this->setPasswordFormFactory = $resetPasswordFormFactory;
        $this->passwordResetTokensRepository = $passwordResetTokensRepository;
        $this->zipBuilder = $zipBuilder;
        $this->userDeleteFormFactory = $userDeleteFormFactory;
        $this->userManager = $userManager;
        $this->accessToken = $accessToken;
    }

  public function renderSet($id) {
    if ($this->getUser()->isLoggedIn()) {
      $this->getUser()->logout(true);
    }

    if (is_null($id)) {
        $this->redirect(':Users:Users:requestPassword');
    }

    if (!$this->passwordResetTokensRepository->isAvailable($id)) {
        $this->flashMessage(
            $this->translator->translate('users.frontend.reset_password.errors.invalid_password_reset_token'),
            "error"
        );
        $this->redirect(':Users:Users:requestPassword');
    }
  }

  public function createComponentSetPasswordForm()
    {
        $token = '';
        if (isset($this->params['id'])) {
            $token = $this->params['id'];
        }
        $form = $this->setPasswordFormFactory->create($token);
        $this->setPasswordFormFactory->onSuccess = function () {
            $this->flashMessage($this->translator->translate('users.frontend.set_password.success'));
            $this->redirect(':Users:Sign:In');
        };
        $form['new_password_confirm']->setOption('description',
        Html::el('div', ['class' => 'description'])
            ->addHtml($this->translator->translate('users.frontend.request_password.login.text'))
            ->addHtml(
                Html::el('a')
                    ->href($this->link(':Users:Sign:in'))
                    ->setText($this->translator->translate('users.frontend.request_password.login.link')
            )
        )
    );
        return $form;
    }

}