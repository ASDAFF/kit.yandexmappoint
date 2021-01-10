<? if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use \Bitrix\Iblock\ElementTable;
use \Bitrix\Main\Localization\Loc;
use \Bitrix\Main\Loader;
use \Bitrix\Main\Config\Option;


defined('ADMIN_MODULE_NAME') or define('ADMIN_MODULE_NAME', 'webmaxima.yandexmappoint');

Loc::loadLanguageFile(__FILE__);

if(isset($arParams["IBLOCK_ID"]) and is_numeric($arParams["IBLOCK_ID"]) and $arParams["IBLOCK_ID"] != '') {

    $iblock_id = $arParams["IBLOCK_ID"];

} else {

    $iblock_id = Option::get(ADMIN_MODULE_NAME, "dataIblockId");

}


if(empty($iblock_id)) {

    $arResult['ERROR'] = Loc::getMessage('WEBMAXIMA_IBLOCK_ID_EMPTY');

}

if(empty(Option::get(ADMIN_MODULE_NAME, "yandexApiKey"))) {

    $arResult['ERROR'] = Loc::getMessage('WEBMAXIMA_APIKEY_IS_EMPTY');

}

if(empty($arResult['ERROR'])) {

    $arParams["CACHE_TIME"] = IntVal($arParams["CACHE_TIME"]);

    $obCache = new CPHPCache;

    $cache_id = "YANDEXMAP_POINTVIEW".$iblock_id.md5($arParams["SOURCE"]);

    if ($obCache->InitCache($arParams["CACHE_TIME"], $cache_id, "/")) {
        $vars = $obCache->GetVars();
        $arResult = $vars["ARRESULT"];
    }
    else
    {

        Loader::includeModule("iblock");

        $arSelect = Array("ID", "NAME", "DATE_ACTIVE_FROM", "PREVIEW_PICTURE", "DETAIL_PICTURE", "PROPERTY_ADRESS", "PROPERTY_TIMEWORK", "PROPERTY_PHONE", "PROPERTY_COORDS", "PROPERTY_SIZE_ICO", "PROPERTY_CODEPLACE", "PROPERTY_TEMPLIMGPLACE", "PROPERTY_COLORIMGPLACE");

        $arFilter = Array("IBLOCK_ID"=>IntVal($iblock_id), "ACTIVE_DATE"=>"Y", "ACTIVE"=>"Y");

        $res = CIBlockElement::GetList(Array(), $arFilter, false, Array("nPageSize"=>50), $arSelect);

        while($ob = $res->GetNextElement())
        {

            $arResult_tmp[] = $ob->GetFields();

        }

        $arPhones = array();
        if(isset($arResult_tmp) and (is_array($arResult_tmp))) {
            foreach ($arResult_tmp as $item) {

                $arPhones[$item['ID']][] = $item['PROPERTY_PHONE_VALUE'];

            }

            foreach ($arResult_tmp as $item) {

                if (empty($arResult[$item['ID']])) {

                    $arResult[$item['ID']] = $item;

                }

            }
        }

        $arResult['ARPHONES'] = $arPhones;


        $arParams['yandexApiKey'] = Option::get(ADMIN_MODULE_NAME, "yandexApiKey");

        if(empty(Option::get(ADMIN_MODULE_NAME, "mapCenter"))) {

            $arParams['mapCenter'] = '55.7342,37.6001';

        } else {

            $arParams['mapCenter'] = Option::get(ADMIN_MODULE_NAME, "mapCenter");

        }

        if(empty(Option::get(ADMIN_MODULE_NAME, "mapZoom"))) {

            $arParams['mapZoom'] = '10';

        } else {

            $arParams['mapZoom'] = Option::get(ADMIN_MODULE_NAME, "mapZoom");

        }

        if(empty(Option::get(ADMIN_MODULE_NAME, "mapWidht"))) {

            $arParams['mapWidht'] = '600px';

        } else {

            $arParams['mapWidht'] = Option::get(ADMIN_MODULE_NAME, "mapWidht");

        }

        if(empty(Option::get(ADMIN_MODULE_NAME, "mapHeight"))) {

            $arParams['mapHeight'] = '400px';

        } else {

            $arParams['mapHeight'] = Option::get(ADMIN_MODULE_NAME, "mapHeight");

        }

        if(empty(Option::get(ADMIN_MODULE_NAME, "mapScroll"))) {

            $arParams['useScroll'] = false;

        } else {

            $arParams['useScroll'] = Option::get(ADMIN_MODULE_NAME, "mapScroll");

        }

        if($obCache->StartDataCache())
        {

            $obCache->EndDataCache(array(
                "ARRESULT" => $arResult
            ));

        }

    }

    $this->IncludeComponentTemplate();

} else {

    $this->IncludeComponentTemplate();

}

?>