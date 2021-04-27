<?php

namespace Crm\UjszoUsersModule\Repository;

use Crm\ApplicationModule\ActiveRow;
use Crm\ApplicationModule\Repository;
use Crm\UsersModule\Events\AddressChangedEvent;
use Crm\UsersModule\Events\NewAddressEvent;
use Crm\UsersModule\Repository\UserMetaRepository;
use Crm\UsersModule\Repository\UsersRepository;
use League\Event\Emitter;
use Nette\Database\Context;
use Nette\Database\Table\IRow;
use Nette\Security\Identity;
use Nette\Utils\DateTime;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\RequestException;
use Nette\Utils\Json;

class DrupalUserRepository {

  private $userMetaRepository;

  private $usersRepository;

  private $emitter;

  private $httpClient;

  public function __construct(
    UsersRepository $usersRepository,
    UserMetaRepository $userMetaRepository,
    Emitter $emitter
  ) {
      $this->usersRepository = $usersRepository;
      $this->userMetaRepository = $userMetaRepository;

      $this->emitter = $emitter;

      $this->httpClient = new HttpClient([
        'base_uri' => getenv('CMS_HOST')
      ]);
  }

  public function findByUser(Identity $user) {
    $crmUser = $this->usersRepository->find($user->id);

    if (!$crmUser) {
      return false;
    }

    $drupalUserId = $this->userMetaRepository->userMetaValueByKey($crmUser, 'drupal_user_id');

    if (!$drupalUserId) {
      // no user mapped... create?
      $this->createByCrmUser($crmUser);

      $drupalUserId = $this->userMetaRepository->userMetaValueByKey($crmUser, 'drupal_user_id');
    }

    if ($drupalUserId) {
      return $drupalUserId;
    }
    return false;
    // dump($drupalUserId);

    // try {
    //   $response = $this->httpClient->get('/');
    // } catch(RequestException $e) {
    //   dump($e);
    // }
  }

  public function createByCrmUser(ActiveRow $user) {

    $data = [
      'name' => [['value' => $user->email]],
      'mail' => [['value' => $user->email]],
      'roles' => [["target_id" => "authenticated"],["target_id" => "blogger"]],
      'pass' => [['value' => 'passwd']],
      'status' => [['value' => '1']],
    ];

    try {
      $result = $this->httpClient->post('/entity/user?_format=json', [
        'headers' => [
          'Content-Type'=>'application/json',
          'accept'=>'application/json',
          'Authorization'=>'Basic YWRtaW46S2F2dG91ZDM=',
          // 'X-CSRF-Token'=>'EeMke8cbL_VhPp6Kdquw3hZW06QWxVoe_GwlQGuQN6o'
        ],
        'body' => JSON::encode($data),
      ]);
      $result_data = JSON::decode($result->getBody());

      $this->userMetaRepository->add($user, 'drupal_user_id', $result_data->uid[0]->value);
    } catch(RequestException $e) {
      dump($e);
    }

  }

  public function syncUser($user) {

  }

}