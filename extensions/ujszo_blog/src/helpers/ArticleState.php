<?php

namespace Crm\UjszoBlogModule\Helpers;

use Kdyby\Translation\Translator;
use Nette\Utils\Html;

class ArticleState {
  private $translator;

  public function __construct(Translator $translator)
  {
    $this->translator = $translator;
  }

  public function process($state)
  {
    return $this->translator->translate('blog.states.' . $state);
  }
}