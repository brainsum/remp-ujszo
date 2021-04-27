<?php

namespace Crm\UjszoUsersModule\Presenters;

use Crm\ApplicationModule\Presenters\FrontendPresenter;
use Crm\ApplicationModule\User\DeleteUserData;
use Crm\ApplicationModule\User\DownloadUserData;
use Crm\UsersModule\Auth\Access\AccessToken;
use Crm\UsersModule\Auth\UserManager;
use Crm\UsersModule\Events\NotificationEvent;
use Crm\UsersModule\Repository\UserMetaRepository;
use Crm\UsersModule\Repository\UsersRepository;
use Crm\UsersModule\Forms\ChangePasswordFormFactory;
use Crm\UsersModule\Forms\RequestPasswordFormFactory;
use Crm\UjszoModule\Forms\SetPasswordFormFactory;
use Crm\UsersModule\Forms\UserDeleteFormFactory;
use Crm\UsersModule\Repository\PasswordResetTokensRepository;
use Crm\UsersModule\User\ZipBuilder;
use Nette\Application\Responses\FileResponse;
use Nette\Application\UI\Form;
use Nette\Utils\Html;
use Nette\Utils\Json;

class ProfilePresenter extends FrontendPresenter {

  public $usersRepository;

  private $userMetaRepository;

  public function __construct(
    UsersRepository $usersRepository,
    UserMetaRepository $userMetaRepository
  ) {
    parent::__construct();
    $this->usersRepository = $usersRepository;
    $this->userMetaRepository = $userMetaRepository;
  }

  public function renderDefault() {
    $this->onlyLoggedIn();
  }

  public function createComponentProfileForm() {
    $user = $this->usersRepository->find($this->getUser()->id);

    // loadDrupalUser;

    $form = new Form();
    $form->addProtection();

    $form->addText('name', $this->translator->translate('ujszo_users.form.name_or_nickname.label'))
      ->setRequired($this->translator->translate('ujszo_users.form.name_or_nickname.required'));

    $form->addText('email', $this->translator->translate('ujszo_users.form.email.label'))
      ->setType('email')
      ->setRequired($this->translator->translate('ujszo_users.form.email.required'));

    $form->addTextArea('short_description', $this->translator->translate('ujszo_users.form.short_description.label'));
    $form->addTextArea('bio', $this->translator->translate('ujszo_users.form.bio.label'));

    $form->addUpload('profile_image', $this->translator->translate('ujszo_users.form.profile_image.label'))
      ->setRequired(false)
      ->addRule(Form::IMAGE, $this->translator->translate('ujszo_users.form.profile_image.file_extension'))
      ->addRule(Form::MAX_FILE_SIZE, $this->translator->translate('ujszo_users.form.profile_image.file_size'), 2048 * 1024);

    $form->addSubmit('submit', $this->translator->translate('ujszo_users.form.submit'));

    $form->setDefaults([
      'name' => $user->public_name,
      'email' => $user->email
    ]);

    $form->onSuccess[] = [$this, 'formSucceeded'];

    return $form;
  }

  public function formSucceeded($form, $values) {
    // dump($values);

    $this->flashMessage('Profile Saved');
  }

}