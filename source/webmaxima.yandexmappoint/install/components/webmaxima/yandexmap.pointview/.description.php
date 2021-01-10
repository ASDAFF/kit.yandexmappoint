<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

use \Bitrix\Main\Localization\Loc;

Loc::loadLanguageFile(__FILE__);

$arComponentDescription = array(
    "NAME" => Loc::getMessage('WEBMAXIMA_COMPANENT_NAME'),
    "DESCRIPTION" => Loc::getMessage('WEBMAXIMA_COMPANENT_DESCRIPTION'),
    "PATH" => array(
        "ID" => Loc::getMessage('WEBMAXIMA_COMPANENT_PATH_ID'),
        "CHILD" => array(
            "ID" => "yandexmappointview",
            "NAME" => Loc::getMessage('WEBMAXIMA_COMPANENT_PATH_CHILD_NAME')
        )
    ),
    //"ICON" => "/images/icon.gif",
);
?>