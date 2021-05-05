<?php
namespace Crm\UjszoBlogModule\Components;

use Crm\ApplicationModule\Widget\BaseWidget;
use Crm\ApplicationModule\Widget\WidgetManager;
use Crm\UjszoUsersModule\Repository\DrupalUserRepository;
use Crm\UsersModule\Repository\UsersRepository;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\RequestException;
use Nette\Utils\Json;

class UserKarmaPoints extends BaseWidget
{
  private $templateName = 'user_karma_points.latte';

  private $drupalUserId;

  private $drupalUserRepository;

  private $httpClient;

  private $usersRepository;

  public function __construct(
    WidgetManager $widgetManager,
    DrupalUserRepository $drupalUserRepository,
    UsersRepository $usersRepository
    ) {
    parent::__construct($widgetManager);
    $this->drupalUserRepository = $drupalUserRepository;
    $this->usersRepository = $usersRepository;
    $this->httpClient = new HttpClient([
      'base_uri' => getenv('CMS_HOST')
    ]);
  }

  public function identifier()
  {
    return 'ujszobloguserkarmapoints';
  }

  public function render($params)
  {
    if (!isset($params['user'])) {
      return '';
    }

    $crmUser = $this->usersRepository->find($params['user']->getIdentity()->id);
    $drupalUser = $this->drupalUserRepository->loadDrupalUser($crmUser);

    // TODO: Fetch karma points.

    $this->template->points = 0;
    $this->template->header = isset($params['header']) ?? false;
    $this->template->setFile(__DIR__ . DIRECTORY_SEPARATOR . $this->templateName);
    $this->template->render();
  }

}