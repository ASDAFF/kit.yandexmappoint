<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
/**
 * Copyright (c) 10/1/2021 Created By/Edited By ASDAFF asdaff.asad@yandex.ru
 */

use \Bitrix\Main\Localization\Loc;

Loc::loadLanguageFile(__FILE__);

$arComponentDescription = array(
    "NAME" => Loc::getMessage('KIT_COMPANENT_NAME'),
    "DESCRIPTION" => Loc::getMessage('KIT_COMPANENT_DESCRIPTION'),
    "PATH" => array(
        "ID" => Loc::getMessage('KIT_COMPANENT_PATH_ID'),
        "CHILD" => array(
            "ID" => "yandexmappointview",
            "NAME" => Loc::getMessage('KIT_COMPANENT_PATH_CHILD_NAME')
        )
    ),
    //"ICON" => "/images/icon.gif",
);
?>