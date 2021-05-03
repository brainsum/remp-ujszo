<?php

namespace Crm\UjszoUsersModule\Presenters;

use Crm\ApplicationModule\Presenters\FrontendPresenter;
use Crm\ApplicationModule\User\DeleteUserData;
use Crm\ApplicationModule\User\DownloadUserData;
use Crm\UjszoModule\Forms\SetPasswordFormFactory;
use Crm\UjszoUsersModule\Repository\DrupalUserRepository;
use Crm\UsersModule\Auth\Access\AccessToken;
use Crm\UsersModule\Auth\UserManager;
use Crm\UsersModule\Events\NotificationEvent;
use Crm\UsersModule\Forms\ChangePasswordFormFactory;
use Crm\UsersModule\Forms\RequestPasswordFormFactory;
use Crm\UsersModule\Forms\UserDeleteFormFactory;
use Crm\UsersModule\Repository\PasswordResetTokensRepository;
use Crm\UsersModule\Repository\UserMetaRepository;
use Crm\UsersModule\Repository\UsersRepository;
use Crm\UsersModule\User\ZipBuilder;
use Nette\Application\Responses\FileResponse;
use Nette\Application\UI\Form;
use Nette\Utils\Html;
use Nette\Utils\Json;

class ProfilePresenter extends FrontendPresenter {

  public $usersRepository;

  private $userMetaRepository;

  private $drupalUserRepository;

  public function __construct(
    UsersRepository $usersRepository,
    UserMetaRepository $userMetaRepository,
    DrupalUserRepository $drupalUserRepository
  ) {
    parent::__construct();
    $this->usersRepository = $usersRepository;
    $this->userMetaRepository = $userMetaRepository;
    $this->drupalUserRepository = $drupalUserRepository;
  }

  public function renderDefault() {
    $this->onlyLoggedIn();
  }

  public function createComponentProfileForm() {
    $user = $this->usersRepository->find($this->getUser()->id);

    $drupalUser = $this->drupalUserRepository->loadDrupalUser($user);


    $form = new Form();
    $form->addProtection();

    $form->addHidden('user_picture_tid', isset($drupalUser->user_picture[0]->target_id) ? $drupalUser->user_picture[0]->target_id :'');
    $form->addHidden('user_picture_src', isset($drupalUser->user_picture[0]->url) ? $drupalUser->user_picture[0]->url :'');

    $form->addText('name', $this->translator->translate('ujszo_users.form.name_or_nickname.label'))
      ->setRequired($this->translator->translate('ujszo_users.form.name_or_nickname.required'));

    $form->addText('email', $this->translator->translate('ujszo_users.form.email.label'))
      ->setType('email')
      ->setDisabled();;

    $form->addTextArea('short_description', $this->translator->translate('ujszo_users.form.short_description.label'));
    $form->addTextArea('bio', $this->translator->translate('ujszo_users.form.bio.label'));

    $image_label = $this->translator->translate(isset($drupalUser->user_picture[0]->target_id) ? 'ujszo_users.form.profile_image.change' : 'ujszo_users.form.profile_image.upload');

    $form->addUpload('profile_image', $image_label)
      ->setRequired(false)
      ->addRule(Form::IMAGE, $this->translator->translate('ujszo_users.form.profile_image.file_extension'))
      ->addRule(Form::MAX_FILE_SIZE, $this->translator->translate('ujszo_users.form.profile_image.file_size'), 2048 * 1024);

    $form->addSubmit('submit', $this->translator->translate('ujszo_users.form.submit'));

    $form->setDefaults([
      'name' => $drupalUser->name[0]->value,
      'email' => $user->email,
      'short_description' => isset($drupalUser->field_description[0]->value) ? $drupalUser->field_description[0]->value : '',
      'bio' => isset($drupalUser->field_bio[0]->value) ? $drupalUser->field_bio[0]->value : '',
    ]);

    $form->onSuccess[] = [$this, 'formSucceeded'];

    return $form;
  }

  public function formSucceeded($form, $values) {
    $user = $this->usersRepository->find($this->getUser()->id);
    $drupalUser = $this->drupalUserRepository->loadDrupalUser($user);

    $drupalUser->name[0]->value = $values->name;
    $drupalUser->field_description[0]->value = $values->short_description;
    $drupalUser->field_bio[0]->value = $values->bio;

    if (!$values->profile_image->error) {
      // Profile image changed;
      // $file = $values->profile_image->getTemporaryFile();
      $r = $this->drupalUserRepository->uploadImage($values->profile_image);

      if (isset($r->fid[0]->value)) {
        $drupalUser->user_picture = [
          ['target_id' => $r->fid[0]->value]
        ];
      }
    }

    $this->drupalUserRepository->updateDrupalUser($drupalUser);

    $this->flashMessage('Profile Saved');
    $this->redirect('default');
  }

}