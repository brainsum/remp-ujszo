<?php

namespace Crm\UjszoUsersModule\Presenters;

use Crm\ApplicationModule\Presenters\FrontendPresenter;
use Crm\ApplicationModule\Snippet\SnippetRenderer;
use Crm\UsersModule\Auth\Authorizator;
use Crm\UsersModule\Auth\InvalidEmailException;
use Crm\UsersModule\Auth\UserManager;
use Crm\UsersModule\Builder\UserBuilder;
use Crm\UsersModule\Email\EmailValidator;
use Crm\UsersModule\Events\NotificationEvent;
use Crm\UsersModule\Events\UserSignOutEvent;
use Nette\Application\UI\Form;
use Nette\Security\AuthenticationException;
use Nette\Utils\Html;
use Tomaj\Form\Renderer\BootstrapRenderer;

class SignPresenter extends FrontendPresenter
{
    private $userBuilder;

    private $authorizator;

    private $userManager;

    private $snippetRenderer;

    private $referer;

    private $emailValidator;

    /** @persistent */
    public $back;

    private $cmsSecret;

    private $cmsUrl;

    public function __construct(
        Authorizator $authorizator,
        UserManager $userManager,
        SnippetRenderer $snippetRenderer,
        UserBuilder $userBuilder,
        EmailValidator $emailValidator
    ) {
        parent::__construct();
        $this->authorizator = $authorizator;
        $this->userManager = $userManager;
        $this->snippetRenderer = $snippetRenderer;
        $this->userBuilder = $userBuilder;
        $this->emailValidator = $emailValidator;
        $this->cmsSecret = getenv('CMS_SECRET');
        $this->cmsUrl = getenv('CMS_HOST');
    }

    public function startup()
    {
        parent::startup();

        $refererUrl = $this->request->getReferer();
        $this->referer = '';

        if ($refererUrl) {
            $this->referer = $refererUrl->__toString();
        }

        if ($this->request->getQuery('referer')) {
            $this->referer = $this->request->getQuery('referer');
        }
    }

    /**
     * Sign-in form factory.
     * @return Form
     */
    protected function createComponentSignInForm()
    {
        $form = new Form();
        $form->setRenderer(new BootstrapRenderer());
        $form->addProtection();
        $form->addText('username', $this->translator->translate('users.frontend.sign_in.username.label'))
            ->setType('email')
            ->setAttribute('autofocus')
            ->setRequired($this->translator->translate('users.frontend.sign_in.username.required'))
            ->setAttribute('placeholder', $this->translator->translate('users.frontend.sign_in.username.placeholder'));

        $form->addPassword('password', $this->translator->translate('users.frontend.sign_in.password.label'))
            ->setRequired($this->translator->translate('users.frontend.sign_in.password.required'))
            ->setAttribute('placeholder', $this->translator->translate('users.frontend.sign_in.password.required'));

        $form->addCheckbox('remember', $this->translator->translate('users.frontend.sign_in.remember'));

        $form->addSubmit('send', $this->translator->translate('users.frontend.sign_in.submit'));

        $form->setDefaults([
            'remember' => true,
        ]);

        $form->onSuccess[] = [$this, 'signInFormSucceeded'];
        return $form;
    }

    public function renderIn()
    {
        if ($this->getUser()->isLoggedIn()) {
            $user = $this->getUser()->getIdentity();
            $token = $this->encrypt($user->email, 'aes-256-cbc', $this->cmsSecret, false);

            $url = $this->cmsUrl . '/remp/login/' . $token;

            if ($this->getReferer() == $this->cmsUrl) {
                $url .= '?destination=' . $this->getReferer();
            }

            $this->redirectUrl($url);
        }
    }

    public function signInFormSucceeded($form, $values)
    {
        if ($values->remember) {
            $this->getUser()->setExpiration('14 days', false);
        } else {
            $this->getUser()->setExpiration('20 minutes', true);
        }

        try {
            $user = $this->getUser();

            $user->login(['username' => $values->username, 'password' => $values->password]);
            $user->setAuthorizator($this->authorizator);

            $session = $this->getSession('success_login');
            $session->success = 'success';

            $token = $this->encrypt($values->username, 'aes-256-cbc', $this->cmsSecret, false);

            $this->redirectUrl($this->cmsUrl . '/remp/login/' . $token);
            $this->redirect($this->homeRoute);
        } catch (AuthenticationException $e) {
            $form->addError($e->getMessage());
        }
    }

    public function actionOut()
    {
        $this->emitter->emit(new UserSignOutEvent($this->getUser()));

        $this->getUser()->logout();

        $this->flashMessage($this->translator->translate('users.frontend.sign_in.signed_out'));
        // $this->restoreRequest($this->getParameter('back'));

        // $this->redirect('in');
        $this->redirectUrl($this->cmsUrl . '/remp/logout');
    }

    public function renderUp()
    {
        if ($this->getUser()->isLoggedIn()) {
            $user = $this->getUser()->getIdentity();
            $token = $this->encrypt($user->email, 'aes-256-cbc', $this->cmsSecret, false);

            $url = $this->cmsUrl . '/remp/login/' . $token;

            if ($this->getReferer() == $this->cmsUrl) {
                $url .= '?destination=' . $this->getReferer();
            }

            $this->redirectUrl($url);
        }
    }

    protected function createComponentSignUpForm()
    {
        $form = new Form();
        $form->setRenderer(new BootstrapRenderer());
        $form->setTranslator($this->translator);

        $form->addText('username', 'users.frontend.sign_up.username.label')
            ->setType('email')
            ->setAttribute('autofocus')
            ->setRequired('users.frontend.sign_up.username.required')
            ->setAttribute('placeholder', 'users.frontend.sign_up.username.placeholder');

        $form->addPassword('password', 'users.frontend.sign_up.password.label')
            ->setRequired('users.frontend.sign_up.password.required')
            ->setAttribute('placeholder', 'users.frontend.sign_up.password.placeholder')
            ->addRule(Form::MIN_LENGTH, 'users.frontend.change_password.new_password.minlength', 6);

        $form->addPassword('password_confirm', 'users.frontend.sign_up.password_confirm.label')
            ->setRequired('users.frontend.sign_up.password_confirm.required')
            ->setAttribute('placeholder', 'users.frontend.sign_up.password_confirm.placeholder')
            ->addRule(Form::EQUAL, 'users.frontend.change_password.new_password_confirm.not_matching', $form['password']);

        $exists = false;
        if ($this->request->getPost('username')) {
            $exists = $this->userManager->loadUserByEmail($this->request->getPost('username'));
        }

        $form
            ->addCheckbox('toc', Html::el()->setHtml('Elfogadom az <a href="https://ujszo.com/gdpr" target="_blank">adatvédelmi szabályzatot</a> és az <a href="/files/BlogASZF.pdf" target="_blank">általános szerződési feltételeket</a>.'))
            ->setRequired('El kell fogadni a feltételeket');

        $form->addSubmit('send', 'users.frontend.sign_up.submit')->setAttribute('class', 'btn btn-primary btn-block');;

        $form->onSuccess[] = [$this, 'signUpFormSucceeded'];
        return $form;
    }

    public function signUpFormSucceeded($form, $values)
    {
        if ($this->userManager->loadUserByEmail($values->username)) {
            $form->addError('users.frontend.sign_up.error.already_registered');
            return;
        }

        $referer = null;
        if (isset($values->redirect) && $values->redirect) {
            $referer = $values->redirect;
        }

        if (!$this->emailValidator->isValid($values->username)) {
            $form['username']->addError('users.frontend.sign_up.error.invalid_email');
            return;
        }

        if ($values->password != $values->password_confirm) {
            $form['password']->addError('users.frontend.reset_password.new_password_confirm.not_matching');
            return;
        } else {
            try {
                $user = $this->userBuilder->createNew()
                    ->setEmail($values->username)
                    ->setPublicName(preg_replace('/@.*/', '', $values->username))
                    ->setPassword($values->password)
                    ->setActive(true)
                    ->setReferer($referer)
                    ->setSource('blog-form')
                    ->setAddTokenOption(true)
                    ->sendEmail(true)
                    ->save();

                $this->emitter->emit(new NotificationEvent(
                    $this->emitter,
                    $user,
                    'user_registered',
                    [
                        'email' => $user->email,
                    ]
                ));

            }
            catch (Exception $e) {
                $form['username']->addError("Cannot create user '{$values->username}' due to following errors: " . Json::encode($e->getMessage()));
                return;
            }
            $this->getUser()->login(['user' => $user, 'autoLogin' => true]);

            $token = $this->encrypt($user->email, 'aes-256-cbc', $this->cmsSecret, false);

            $this->redirectUrl($this->cmsUrl . '/remp/login/' . $token);

            if ($referer) {
                $this->redirectUrl($referer);
            } else {
                $this->redirect($this->homeRoute);
            }
        }
    }

    private function encrypt($value, $cipher, $key, $serialize = true) {
        $iv = random_bytes(openssl_cipher_iv_length($cipher));

        $value = \openssl_encrypt(
          $serialize ? serialize($value) : $value,
          $cipher, $key, 0, $iv
        );

        if ($value === false) {
          throw new Exception('Could not encrypt the data.');
        }

        $mac = $this->hash($iv = base64_encode($iv), $value, $key);

        $json = json_encode(compact('iv', 'value', 'mac'), JSON_UNESCAPED_SLASHES);

        if (json_last_error() !== JSON_ERROR_NONE) {
          throw new Exception('Could not encrypt the data.');
        }

        return base64_encode($json);
      }

      /**
       * Create a MAC for the given value.
       */
      private function hash($iv, $value, $key) {
          return hash_hmac('sha256', $iv . $value, $key);
      }
}
