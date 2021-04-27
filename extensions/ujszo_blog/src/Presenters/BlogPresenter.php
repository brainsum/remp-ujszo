<?php

namespace Crm\UjszoBlogModule\Presenters;

use Crm\ApplicationModule\Presenters\FrontendPresenter;
use Crm\UjszoUsersModule\Repository\DrupalUserRepository;
use GuzzleHttp\Client as HttpClient;
use Nette\Utils\Json;
use GuzzleHttp\Exception\RequestException;

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
    if ($articles->data) {
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
          'Authorization'=>'Basic YWRtaW46S2F2dG91ZDM=',
        ],
      ]);

      return JSON::decode($result->getBody());
    } catch(RequestException $e) {
      dump($e);
    }
  }

}