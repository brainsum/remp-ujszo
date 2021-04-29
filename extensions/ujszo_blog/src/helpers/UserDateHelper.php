<?php

namespace Crm\UjszoBlogModule\Helpers;

use DateTime;
use IntlDateFormatter;
use Nette\Localization\ITranslator;

class UserDateHelper
{
    private $translator;

    private $format;

    public function __construct(ITranslator $translator)
    {
        $this->translator = $translator;
    }

    /**
     * setFormat accepts any format supported by IntlDateFormatter.
     *
     * @param array|string $format
     */
    public function setFormat($format)
    {
        $this->format = $format;
    }

    public function process($date, $long = false)
    {
        if (!$date instanceof DateTime) {
            $date = new DateTime($date);
            // return (string) $date;
        };

        if ($this->format) {
            $format = $this->format;
        } elseif ($long) {
            $format = "yyyy MMMM dd. HH:mm:ss";
        } else {
            $format = "yyyy MMMM dd.";
        }

        return IntlDateFormatter::formatObject(
            $date,
            $format,
            $this->translator->getLocale()
        );
    }
}
