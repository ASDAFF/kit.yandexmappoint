<?php
/**
 * Copyright (c) 10/1/2021 Created By/Edited By ASDAFF asdaff.asad@yandex.ru
 */

use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;

defined('ADMIN_MODULE_NAME') or define('ADMIN_MODULE_NAME', 'kit.yandexmappoint');

if (!$USER->isAdmin()) {
    $APPLICATION->authForm('Nope');
}

$app = Application::getInstance();
$context = $app->getContext();
$request = $context->getRequest();


Loc::loadMessages($context->getServer()->getDocumentRoot()."/bitrix/modules/main/options.php");

Loc::loadMessages(__FILE__);

$tabControl = new CAdminTabControl("tabControl", array(
    array(
        "DIV" => "edit1",
        "TAB" => Loc::getMessage("MAIN_TAB_SET"),
        "TITLE" => Loc::getMessage("MAIN_TAB_TITLE_SET"),
    ),
));

if ((!empty($save) || !empty($restore)) && $request->isPost() && check_bitrix_sessid()) {

    if (!empty($restore)) {

        Option::delete(ADMIN_MODULE_NAME);

        CAdminMessage::showMessage(array(
            "MESSAGE" => Loc::getMessage("WM_OPTIONS_RESTORED"),
            "TYPE" => "OK",
        ));

    } else {

        $safe_prama_status = true;

        if ($request->getPost('yandexApiKey')) {

            Option::set(
                ADMIN_MODULE_NAME,
                "yandexApiKey",
                $request->getPost('yandexApiKey')
            );

        } else {

            Option::set(
                ADMIN_MODULE_NAME,
                "yandexApiKey",
                ''
            );

        }

        if ($request->getPost('dataIblockId')) {

            if(($request->getPost('dataIblockId') > 0) && ($request->getPost('dataIblockId') < 100000)
                && (is_numeric($request->getPost('dataIblockId'))) && (stristr($request->getPost('dataIblockId'), '.') === false) && (stristr($request->getPost('dataIblockId'), ',') === false)) {

                Option::set(
                    ADMIN_MODULE_NAME,
                    "dataIblockId",
                    $request->getPost('dataIblockId')
                );

            } else {

                CAdminMessage::showMessage(Loc::getMessage("WM_INVALID_VALUE")." ".Loc::getMessage("DATA_IBLOCK"));
                $safe_prama_status = false;
            }

        } else {

            Option::set(
                ADMIN_MODULE_NAME,
                "dataIblockId",
                ''
            );

        }

        if ($request->getPost('mapCenter')) {
            if(substr_count($request->getPost('mapCenter'),',')==1) {//в строке может быть только одназапятая
                $center_array = explode(',', $request->getPost('mapCenter'));
                if (count($center_array) == 2) {
                    if ((is_numeric($center_array[0])) and (is_numeric($center_array[1]))) {
                        Option::set(
                            ADMIN_MODULE_NAME,
                            "mapCenter",
                            $request->getPost('mapCenter')
                        );
                    } else {
                        CAdminMessage::showMessage(Loc::getMessage("WM_INVALID_VALUE") . " " . Loc::getMessage("MAP_CENTER"));
                        $safe_prama_status = false;
                    }
                } else {
                    CAdminMessage::showMessage(Loc::getMessage("WM_INVALID_VALUE") . " " . Loc::getMessage("MAP_CENTER"));
                    $safe_prama_status = false;
                }
            }
            else{
                CAdminMessage::showMessage(Loc::getMessage("WM_INVALID_VALUE") . " " . Loc::getMessage("MAP_CENTER"));
                $safe_prama_status = false;
            }
        }

        if ($request->getPost('mapZoom')) {

            if(($request->getPost('mapZoom') > 0) && ($request->getPost('mapZoom') < 100000)
                && (is_numeric($request->getPost('mapZoom'))) && (stristr($request->getPost('mapZoom'), '.') === false) && (stristr($request->getPost('mapZoom'), ',') === false)) {

                Option::set(
                    ADMIN_MODULE_NAME,
                    "mapZoom",
                    $request->getPost('mapZoom')
                );

            } else {

                CAdminMessage::showMessage(Loc::getMessage("WM_INVALID_VALUE")." ".Loc::getMessage("MAP_ZOOM"));
                $safe_prama_status = false;

            }


        } else {

            Option::set(
                ADMIN_MODULE_NAME,
                "mapZoom",
                ''
            );

        }

        if ($request->getPost('mapWidht')) {

            $lenght=strlen($request->getPost('mapWidht'));
            $end_one_string=substr($request->getPost('mapWidht'), -1);//%
            if($end_one_string=='%'){
                $lenght=$lenght-1;
                $num=(substr($request->getPost('mapWidht'),0,$lenght));
            }
            $end_two_string=substr($request->getPost('mapWidht'), -2);//px

            if($end_two_string=='px'){
                $lenght=$lenght-2;
                $num=(substr($request->getPost('mapWidht'),0,$lenght));
            }
            if((empty($num)) or (is_numeric($num)==false)){
                CAdminMessage::showMessage(Loc::getMessage("WM_INVALID_VALUE")." ".Loc::getMessage("MAP_WIDHT"));
                $safe_prama_status = false;
            }
            else {
                Option::set(
                    ADMIN_MODULE_NAME,
                    "mapWidht",
                    $request->getPost('mapWidht')
                );
            }

        }

        if ($request->getPost('mapHeight')) {

            $lenght=strlen($request->getPost('mapHeight'));
            $end_one_string=substr($request->getPost('mapHeight'), -1);//%
            if($end_one_string=='%'){
                $lenght=$lenght-1;
                $num=(substr($request->getPost('mapHeight'),0,$lenght));
            }
            $end_two_string=substr($request->getPost('mapHeight'), -2);//px

            if($end_two_string=='px'){
                $lenght=$lenght-2;
                $num_height=(substr($request->getPost('mapHeight'),0,$lenght));
            }


            if((empty($num_height)) or (is_numeric($num_height)==false)){
                CAdminMessage::showMessage(Loc::getMessage("WM_INVALID_VALUE")." ".Loc::getMessage("MAP_HEIGHT"));
                $safe_prama_status = false;
            }
            else {
                Option::set(
                    ADMIN_MODULE_NAME,
                    "mapHeight",
                    $request->getPost('mapHeight')
                );
            }

        }

        if ($request->getPost('mapScroll')) {
            if(($request->getPost('mapScroll')=='true') or ($request->getPost('mapScroll')=='false')) {

                Option::set(
                    ADMIN_MODULE_NAME,
                    "mapScroll",
                    $request->getPost('mapScroll')
                );
            }
            else {

                CAdminMessage::showMessage(Loc::getMessage("WM_INVALID_VALUE")." ".Loc::getMessage("MAP_ZOOM"));
                $safe_prama_status = false;
            }
        }

        if($safe_prama_status) {

            CAdminMessage::showMessage(array(
                "MESSAGE" => Loc::getMessage("WM_SUCCESS_VALUE"),
                "TYPE" => "OK",
            ));

        }

    }

}

$tabControl->begin();

?>

<form method="post" action="<?=sprintf('%s?mid=%s&lang=%s', $request->getRequestedPage(), urlencode($mid), LANGUAGE_ID)?>">
    <?php
    echo bitrix_sessid_post();
    $tabControl->beginNextTab();
    ?>

    <tr>
        <td style="vertical-align: top;line-height: 25px;" width="40%">
            <label for="yandexApiKey"><?=Loc::getMessage("YANDEX_API_KEY") ?>:</label>
        <td width="60%">
            <input type="text"
                   size="50"
                   name="yandexApiKey"
                   value="<?=Option::get(ADMIN_MODULE_NAME, "yandexApiKey");?>"
                   />
        </td>
    </tr>

    <tr>
        <td style="vertical-align: top;line-height: 25px;" width="40%">
            <label for="dataIblockId"><?=Loc::getMessage("DATA_IBLOCK") ?>:</label>
        <td width="60%">
            <input type="text"
                   size="50"
                   maxlength="5"
                   name="dataIblockId"
                   value="<?=Option::get(ADMIN_MODULE_NAME, "dataIblockId");?>"
            />
        </td>
    </tr>

    <tr>
        <td style="vertical-align: top;line-height: 25px;" width="40%">
            <label for="mapCenter"><?=Loc::getMessage("MAP_CENTER") ?>:</label>
        <td width="60%">
            <input type="text"
                   size="50"
                   maxlength="500"
                   name="mapCenter"
                   value="<?=Option::get(ADMIN_MODULE_NAME, "mapCenter");?>"
            />
            <p><?=Loc::getMessage("MAP_CENTER_DESCRIPTION") ?></p>
        </td>
    </tr>

    <tr>
        <td style="vertical-align: top;line-height: 25px;" width="40%">
            <label for="mapZoom"><?=Loc::getMessage("MAP_ZOOM") ?>:</label>
        <td width="60%">
            <input type="text"
                   size="50"
                   maxlength="50"
                   name="mapZoom"
                   value="<?=Option::get(ADMIN_MODULE_NAME, "mapZoom");?>"
            />
        </td>
    </tr>

    <tr>
        <td style="vertical-align: top;line-height: 25px;" width="40%">
            <label for="mapWidht"><?=Loc::getMessage("MAP_WIDHT") ?>:</label>
        <td width="60%">
            <input type="text"
                   size="50"
                   maxlength="50"
                   name="mapWidht"
                   value="<?=Option::get(ADMIN_MODULE_NAME, "mapWidht");?>"
            />
            <p><?=Loc::getMessage("MAP_HEIGHT_WIDTH_DESCRIPTION") ?></p>
        </td>
    </tr>

    <tr>
        <td style="vertical-align: top;line-height: 25px;" width="40%">
            <label for="mapHeight"><?=Loc::getMessage("MAP_HEIGHT") ?>:</label>
        <td width="60%">
            <input type="text"
                   size="50"
                   maxlength="50"
                   name="mapHeight"
                   value="<?=Option::get(ADMIN_MODULE_NAME, "mapHeight");?>"
            />
            <p><?=Loc::getMessage("MAP_HEIGHT_WIDTH_DESCRIPTION") ?></p>
        </td>
    </tr>

    <tr>
        <td style="vertical-align: top;line-height: 25px;" width="40%">
            <label for="mapScroll"><?=Loc::getMessage("MAP_SCROLL") ?>:</label>
        <td width="60%">
            <input type="text"
                   size="50"
                   maxlength="50"
                   name="mapScroll"
                   value="<?=Option::get(ADMIN_MODULE_NAME, "mapScroll");?>"
            />
        </td>
    </tr>


    <?php
    $tabControl->buttons();
    ?>
    <input type="submit"
           name="save"
           value="<?=Loc::getMessage("MAIN_SAVE") ?>"
           title="<?=Loc::getMessage("MAIN_OPT_SAVE_TITLE") ?>"
           class="adm-btn-save"
           />
    <input type="submit"
           name="restore"
           title="<?=Loc::getMessage("MAIN_HINT_RESTORE_DEFAULTS") ?>"
           onclick="return confirm('<?= AddSlashes(GetMessage("MAIN_HINT_RESTORE_DEFAULTS_WARNING")) ?>')"
           value="<?=Loc::getMessage("MAIN_RESTORE_DEFAULTS") ?>"
           />
    <?php
    $tabControl->end();
    ?>
</form>