<? if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/**
 * Copyright (c) 10/1/2021 Created By/Edited By ASDAFF asdaff.asad@yandex.ru
 */

use \Bitrix\Main\Localization\Loc;

if(!empty($arResult['ERROR'])) {

    echo $arResult['ERROR'];

} else {

    if(!empty($arResult['ARPHONES'])) {

        $arPhones = $arResult['ARPHONES'];

        unset($arResult['ARPHONES']);

    }

    ?>

    <script src="https://api-maps.yandex.ru/2.1/?apikey=<?= $arParams['yandexApiKey'] ?>&lang=ru_RU"
            type="text/javascript"></script>

    <script type="text/javascript">

        ymaps.ready(function () {

            var myMap = new ymaps.Map('map',

                {

                    center: [<?=$arParams['mapCenter']?>],
                    zoom: <?=$arParams['mapZoom']?>

                }, {

                    searchControlProvider: 'yandex#search'

                }
            )<? if(count($arResult)>0) {echo ",";} else {echo "";}?>

            <? $counter_i = 1; ?>

            <?foreach ($arResult as $item) {

            if (!empty($item["PREVIEW_PICTURE"])) {

            $item["PREVIEW_PICTURE"] = CFile::GetFileArray($item["PREVIEW_PICTURE"]);

            $body = '';

            $item["DETAIL_PICTURE"] = CFile::GetFileArray($item["DETAIL_PICTURE"]);

            if(!empty($item["DETAIL_PICTURE"])) $body .= "<img src=\"".$item['DETAIL_PICTURE']['SRC']."\"> <br/>";

            if(!empty($item['PROPERTY_CODEPLACE_VALUE']['TEXT'])) {

                $body = str_replace(array("\r\n", "\r", "\n"), '', $item['~PROPERTY_CODEPLACE_VALUE']['TEXT']);

            } else {

                if(!empty($item['PROPERTY_ADRESS_VALUE'])) $body .= '<b>'.Loc::getMessage('KIT_PLACE_ADRESS').'</b>: '.$item['PROPERTY_ADRESS_VALUE'].'<br />';

                if(!empty($item['PROPERTY_PHONE_VALUE'])) {

                    foreach ($arPhones[$item['ID']] as $key => $arPhone) {

                        if($key == (count($arPhones[$item['ID']])-1)) {

                            $Phone_string .= $arPhone;

                        } else {

                            $Phone_string .= $arPhone.', ';

                        }

                    }

                    $body .= '<b>'.Loc::getMessage('KIT_PLACE_PHONE').'</b>: '.$Phone_string.'<br />';

                    unset($Phone_string);

                }

                if(!empty($item['PROPERTY_TIMEWORK_VALUE'])) $body .= '<b>'.Loc::getMessage('KIT_PLACE_TIMEWORK').'</b>: '.$item['PROPERTY_TIMEWORK_VALUE'].'<br />';

            }

            ?>

            placemark<?=$item['ID']?> = new ymaps.Placemark([<?=$item['PROPERTY_COORDS_VALUE']?>], {
                balloonContentHeader: '<?=$item['NAME']?>',
                <?if(!empty($body)) echo "balloonContentBody: '".$body."',"?>
                hintContent: '<?=$item['NAME']?>',
                balloonContent: '<?=$item['NAME']?>'
            }, {
                iconLayout: 'default#image',
                iconImageHref: '<?=$item['PREVIEW_PICTURE']['SRC']?>',
                iconImageSize: [<?if($item['PROPERTY_SIZE_ICO_VALUE']) echo $item['PROPERTY_SIZE_ICO_VALUE']; else echo '20, 20';?>],
                iconImageOffset: [-5, -38]
            })<? if($counter_i == count($arResult)) { echo ";"; } else { echo ","; $counter_i++;} ?>

            <?

            } else {

            if(!empty($item['PROPERTY_TEMPLIMGPLACE_VALUE'])) {

                $preset = $item['PROPERTY_TEMPLIMGPLACE_VALUE'];

            } else {

                $preset = 'islands#governmentCircleIcon';

            }

            if(!empty($item['PROPERTY_COLORIMGPLACE_VALUE'])) {

                $iconColor = $item['PROPERTY_COLORIMGPLACE_VALUE'];

            } else {

                $iconColor = '#3b5998';

            }

            $body = '';

            $item["DETAIL_PICTURE"] = CFile::GetFileArray($item["DETAIL_PICTURE"]);

            if(!empty($item["DETAIL_PICTURE"])) $body .= "<img src=\"".$item['DETAIL_PICTURE']['SRC']."\"> <br/>";

            if(!empty($item['PROPERTY_CODEPLACE_VALUE']['TEXT'])) {

                $body = str_replace(array("\r\n", "\r", "\n"), '', $item['~PROPERTY_CODEPLACE_VALUE']['TEXT']);

            } else {

                if(!empty($item['PROPERTY_ADRESS_VALUE'])) $body .= '<b>'.Loc::getMessage('KIT_PLACE_ADRESS').'</b>: '.$item['PROPERTY_ADRESS_VALUE'].'<br />';

                if(!empty($item['PROPERTY_PHONE_VALUE'])) {

                    foreach ($arPhones[$item['ID']] as $key => $arPhone) {

                        if($key == (count($arPhones[$item['ID']])-1)) {

                            $Phone_string .= $arPhone;

                        } else {

                            $Phone_string .= $arPhone.', ';

                        }

                    }

                    $body .= '<b>'.Loc::getMessage('KIT_PLACE_PHONE').'</b>: '.$Phone_string.'<br />';

                    unset($Phone_string);

                }

                if(!empty($item['PROPERTY_TIMEWORK_VALUE'])) $body .= '<b>'.Loc::getMessage('KIT_PLACE_TIMEWORK').'</b>: '.$item['PROPERTY_TIMEWORK_VALUE'].'<br />';

            }

            ?>

            placemark<?=$item['ID']?> = new ymaps.Placemark([<?=$item['PROPERTY_COORDS_VALUE']?>], {
                balloonContentHeader: '<?=$item['NAME']?>',
                <?if(!empty($body)) echo "balloonContentBody: '".$body."',"?>
                balloonContent: '<?=$item['NAME']?>'
            }, {
                preset: '<?=$preset?>',
                iconColor: '<?=$iconColor?>'
            })<? if($counter_i == count($arResult)) { echo ";"; } else { echo ","; $counter_i++;} ?>


            <? } //endif?>

            <? } //endforeach?>

            <?

            foreach ($arResult as $item) {

                echo "myMap.geoObjects.add(placemark".$item['ID'].");";

            }

            if ($arParams['useScroll'] == 'false') {

                echo "myMap.behaviors.disable('scrollZoom');";

            } ?>

        });

    </script>

    <div id="map" style="width: <?= $arParams['mapWidht'] ?>; height: <?= $arParams['mapHeight'] ?>"></div>

    <?
}
?>