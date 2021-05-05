<?php

namespace Crm\UjszoBlogModule\Presenters;

use Crm\ApplicationModule\Presenters\FrontendPresenter;
use Crm\UjszoUsersModule\Repository\DrupalUserRepository;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\RequestException;
use Nette\Utils\Json;
use Tracy\Debugger;

class BlogPresenter extends FrontendPresenter {

  private $drupalUserId;

  private $drupalUserRepository;

  private $httpClient;

  public function __construct(
    DrupalUserRepository $drupalUserRepository
  ) {
    parent::__construct();
    $this->drupalUserRepository = $drupalUserRepository;
    $this->httpClient = new HttpClient([
      'base_uri' => getenv('CMS_HOST')
    ]);
  }

  public function startup() {
    parent::startup();
    $this->onlyLoggedIn();
    $this->getDrupalUserId();
  }

  public function renderDefault() {
    $articles = $this->fetchArticles();
    $this->template->baseUrl = rtrim(getenv('CMS_HOST'), '/');
    if (isset($articles->data) && $articles->data) {
      $this->template->articles = $articles->data;
    }
  }

  private function getDrupalUserId() {
    $this->drupalUserId = $this->drupalUserRepository->findByUser($this->user->getIdentity());
  }

  private function fetchArticles() {
    try {
      $result = $this->httpClient->get('/jsonapi/node/blog?filter[uid.uid]=' . $this->drupalUserId, [
        'headers' => [
          'Content-Type'=>'application/json',
          'accept'=>'application/json',
          'Authorization'=>'Basic ' . getenv('CMS_TOKEN'),
        ],
      ]);

      return JSON::decode($result->getBody());
    } catch(RequestException $e) {
      Debugger::log($e);
    }
  }

}