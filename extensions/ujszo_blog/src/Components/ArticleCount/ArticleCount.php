<?php
namespace Crm\UjszoBlogModule\Components;

use Crm\ApplicationModule\Widget\BaseWidget;
use Crm\ApplicationModule\Widget\WidgetManager;
use Crm\UjszoUsersModule\Repository\DrupalUserRepository;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\RequestException;
use Nette\Utils\Json;
use Tracy\Debugger;

class ArticleCount extends BaseWidget
{
  private $templateName = 'article_count.latte';

  private $drupalUserId;

  private $drupalUserRepository;

  private $httpClient;

  public function __construct(
    WidgetManager $widgetManager,
    DrupalUserRepository $drupalUserRepository
    ) {
    parent::__construct($widgetManager);
    $this->drupalUserRepository = $drupalUserRepository;
    $this->httpClient = new HttpClient([
      'base_uri' => getenv('CMS_HOST')
    ]);
  }

  public function identifier()
  {
    return 'ujszoblogarticlecount';
  }

  public function render($params)
  {
    if (!isset($params['user'])) {
      return '';
    }
    $articles = $this->fetchArticles($this->drupalUserRepository->findByUser($params['user']->getIdentity()));
    $this->template->articleCount = $articles->meta->count ?? 0;
    $this->template->header = $params['header'] ?? false;
    $this->template->setFile(__DIR__ . DIRECTORY_SEPARATOR . $this->templateName);
    $this->template->render();
  }

  private function fetchArticles($drupalUserId) {
    try {
      $result = $this->httpClient->get('/jsonapi/node/blog?filter[uid.uid]=' . $drupalUserId, [
        'headers' => [
          'Content-Type'=>'application/json',
          'accept'=>'application/json',
          'Authorization'=>'Basic ' . getenv('CMS_TOKEN'),
        ],
      ]);

      return JSON::decode($result->getBody());
    } catch(RequestException $e) {
      Debugger::log($e, Debugger::ERROR);
    }
  }
}