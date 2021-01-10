<?php
/**
 * Copyright (c) 10/1/2021 Created By/Edited By ASDAFF asdaff.asad@yandex.ru
 */

use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc; // для работы с сообщениями
use Bitrix\Main\ModuleManager;
use \Bitrix\Main\Config\Option; // для работы с опциями модуля
//use Bex\D7dull\ExampleTable; в lib установка БД

//задается константа с названием модуля (либо она полученна либо создаем)
defined('ADMIN_MODULE_NAME') or define('ADMIN_MODULE_NAME', 'kit.yandexmappoint');

Loc::loadMessages(__FILE__); // подгрузка сообщений из lang

class kit_yandexmappoint extends CModule
{

    var $iblockType = 'wm_yandex_adress';
    var $iblockXMLID = "wm_yandexAdress_xml_id_1";
    var $iblockCODE = "wm_yandex_adress";
    var $iblockId = '';

    // Идентификатор модуля, обызательно в начале наше партнерский код
    var $MODULE_ID = 'kit.yandexmappoint'; // MODULE_ID должен быть именно здесь в __construct не подойдет т.к битрикс не пропустит на модерацию

    var $ID_SITE = false; // идентификатор сайта для установки модуля

    // должна быть опеределена LOG_FILENAME в dbconn.php
    var $debug = true; // or false константа для отладки кода вляет на звпись в лог в корне


    public function __construct()
    {
        $arModuleVersion = array();

        include __DIR__ . '/version.php'; // подгрузка обязательного файла с версией модуля, незабываем ее менять от выката к выкату

        if (is_array($arModuleVersion) && array_key_exists('VERSION', $arModuleVersion))
        {
            $this->MODULE_VERSION = $arModuleVersion['VERSION'];
            $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        }

        $this->MODULE_NAME = Loc::getMessage('YANDEX_POINT_MODULE_NAME'); // Название модуля
        $this->MODULE_DESCRIPTION = Loc::getMessage('YANDEX_POINT_DESCRIPTION'); // Описание модуля
        $this->MODULE_GROUP_RIGHTS = 'N';
        $this->PARTNER_NAME = Loc::getMessage('YANDEX_POINT_PARTNER_NAME'); // Название нашей компании
        $this->PARTNER_URI = 'https://asdaff.github.io/'; // url нашей компании

    }

    // системная функция модуля обязательная выполняющая в процессе установки модуля
    public function doInstall()
    {

        global $APPLICATION;

        $status_install = false;

        $arSite = $this->get_site();

        if(count($arSite) > 1) {

            // выбор текущего сайта в зависимости от URL
            $arSite = $this->get_site($_SERVER['HTTP_HOST']);

            $this->ID_SITE = $arSite['ID'];

        } else {

            $this->ID_SITE = $arSite[0]['ID'];

        }

        if($this->ID_SITE == false or $this->ID_SITE == '') {

            $this->all_log_wm('не удалось определить сайт');
            return false;
        }

        // проверка на то что версия bitrix выше 14 т.е уже есть эрмитаж и нужные нам API функции
        if(CheckVersion(ModuleManager::getVersion("main"), "14.00.00")){

            $this->InstallFiles(); // функция установки файлов

            //$this->InstallDB(); // функция установки базы данных

            // функция установки инфоблоков
            if($this->InstallIblock()) {

                $status_install = true;

            } else {

                $status_install = false;

            }

        }else{

            // выбрасываем исключение об ошибке версии битрикса, исключение старого образца (с версии 3.3.21)
            $APPLICATION->ThrowException(
                Loc::getMessage("YANDEX_POINT_ERROR_VERSION")
            );

            $status_install = false;

        }

        if($status_install) {

            ModuleManager::registerModule($this->MODULE_ID); // регистрируем наш модуль

            // добавление опции ID созданного  инфоблока в модуль
            COption::SetOptionString($this->MODULE_ID, 'IBLOCK_YMAP', $this->iblockId);

            $this->InstallEvents(); // регистрируем события которые относятся к модулю

            // добавление опции ID инфоблока с адресами меток
            COption::SetOptionString($this->MODULE_ID, 'dataIblockId', $this->iblockId);

            // добавление опции Координаты центра карты
            COption::SetOptionString($this->MODULE_ID, 'mapCenter', '51.7933,55.1975');

            // добавление опции Стартовое приближение карты
            COption::SetOptionString($this->MODULE_ID, 'mapZoom', '9');

            // добавление опции Стартовое Ширина карты (px)
            COption::SetOptionString($this->MODULE_ID, 'mapWidht', '600px');

            // добавление опции Стартовое Высота карты (px)
            COption::SetOptionString($this->MODULE_ID, 'mapHeight', '500px');

            // добавление опции Стартовое использование скрола при прокрутке колесиком
            COption::SetOptionString($this->MODULE_ID, 'mapScroll', 'false');

            // переход на следующий шаг установки
            $APPLICATION->IncludeAdminFile(
                Loc::getMessage("YANDEX_POINT_INSTALL_TITLE")." \"".Loc::getMessage("YANDEX_POINT_MODULE_NAME")."\"", //Заголовок страницы.
                __DIR__."/step.php"
            );

        }

        return false;

    }

    // системная функция модуля обязательная выполняющая в процессе удаления модуля
    public function doUninstall()
    {

        //$this->uninstallDB(); // удаление базы данных

        $this->UnInstallFiles(); // удаление файлов

        $this->unInstallIblock($this->iblockType); // удаление инфоблока (точнее его типа поэтоум он снесет и сам инфоблок)

        // удаляем опции модуля
        Option::delete(ADMIN_MODULE_NAME); // удаление всех опций перед записью

        ModuleManager::unRegisterModule($this->MODULE_ID);

    }

    public function installDB()
    {

        return false;

        /* если нужно установить файлы БД
        if (Loader::includeModule($this->MODULE_ID))
        {
            ExampleTable::getEntity()->createDbTable();
        } */
    }

    public function uninstallDB()
    {
        if (Loader::includeModule($this->MODULE_ID))
        {
            $connection = Application::getInstance()->getConnection();
            $connection->dropTable(ExampleTable::getTableName());
        }
    }

    // функция установки файлов
    public function InstallFiles(){

        // выполнение копирования файлов роутинга при обращении
        //CopyDirFiles(__DIR__.'/admin/', $_SERVER['DOCUMENT_ROOT'].'/bitrix/admin', true);

        // выполнение копирования файлов компонентов
        CopyDirFiles(__DIR__.'/components/', $_SERVER['DOCUMENT_ROOT'].'/bitrix/components', true, true);


        // вот куда складываются js и css когда они нужны компонентам модуля т.к прямой достп к ним будет запрещен
        // обычно они нужны внутри админки модуля
        // копирование Css модуля в корневую папаку bitrix
        //CopyDirFiles(__DIR__.'/css/', $_SERVER['DOCUMENT_ROOT'].'/bitrix/css/'.$this->MODULE_ID.'/', true, true);

        // копирование js модуля в корневую папаку bitrix
        //CopyDirFiles(__DIR__.'/js/', $_SERVER['DOCUMENT_ROOT'].'/bitrix/js/'.$this->MODULE_ID.'/', true, true);

        return false;

    }

    // функция удаления файлов
    public function UnInstallFiles(){

        // удаление файлов связанных с роутингом
        //DeleteDirFiles(__DIR__.'/admin/', $_SERVER['DOCUMENT_ROOT'].'/bitrix/admin');

        // удаление файлов компонентов
        DeleteDirFilesEx("/bitrix/components/kit/yandexmap.pointview");

        // удаление сайтового инсталятора
        //DeleteDirFilesEx('/bitrix/wizards/'.self::partnerName.'/'.self::solutionName.'/');

        return true;
    }

    // установка инфоблока вызов
    public function InstallIblock(){

        global $APPLICATION; // для работы переадресации в случае ошибки

        // если модуль инфоблок неподключен - по хорошему выбросить исключение и сообщить
        if(!CModule::IncludeModule("iblock")) {

            $this->all_log_wm('не подключен модуль iblock, метод InstallIblock');

            return false;

        }

        $iblockID = true; // статус добавления инфоблока

        // запрашиваем существование нашего инфоблока на случай перезаписи
        $rsIBlock = CIBlock::GetList(array(), array("TYPE" => $this->iblockType, "SITE_ID" => $this->ID_SITE));

        // обработка существования инфоблока
        // выполниться если хотябы один инфоблок есть (расчитываем на то что с нашим префиксом инфоблоки никто не делает)
        if ($arIBlock = $rsIBlock->Fetch()) {

            $this->all_log_wm('Iblock найден, должна быть замена');

            $iblockID = false; //инфоблок есть, без процедуры удаления будет ошибка

            // если инфоблок найден то мы его удалим
            if ($arIBlock["ID"]) {

                if(CIBlock::Delete($arIBlock["ID"])) { // удаление инфоблока с данными

                    $this->all_log_wm('Iblock успешно удален');
                    $iblockID = true; // удаление успешно прошло

                } else {

                    $this->all_log_wm('Iblock проблема с удалением');
                    $iblockID = false; // удаление успешно прошло

                }

            }
        }

        if($iblockID){

            // для типа инфоблоков
            $arFieldsForType = Array(
                'ID' => $this->iblockType,
                'SECTIONS' => 'Y',
                'IN_RSS' => 'N',
                'SORT' => 500,
                'LANG' => Array(
                    'en' => Array(
                        'NAME' => Loc::getMessage('YANDEX_POINT_MODULE_IBLOCK_TYPE_NAME_EN'),
                    ),
                    'ru' => Array(
                        'NAME' => Loc::getMessage('YANDEX_POINT_MODULE_IBLOCK_TYPE_NAME_RU'),
                    )
                )
            );

            // создаем тип инфоблока
            // если создали тип инфоблока, создаём инфоблоки
            if ($this->AddIblockType($arFieldsForType)){

                $this->all_log_wm('тип '.$this->iblockType.' инфоблока успешно создан');

                $permissions = array("1" => "X", "2" => "R"); // выставляем уровень прав

                //получаем группу content_editor точнее ее ID
                $dbGroup = CGroup::GetList($by = "", $order = "", array("STRING_ID" => "content_editor"));

                // выставляем для данной группы значение записи в будущий инфоблок
                if($arGroup = $dbGroup->Fetch()){
                    $permissions[$arGroup["ID"]] = "W";

                    $this->all_log_wm('группа прав успешно получна '.$arGroup["ID"].' = w');

                };

                // параметры для создания инфоблока
                $arFieldsForIblock = Array(
                    "ACTIVE" => "Y",
                    "NAME" => Loc::getMessage('YANDEX_POINT_MODULE_IBLOCK_NAME'),
                    "CODE" => $this->iblockCODE, // код и нфоблока
                    "IBLOCK_TYPE_ID" => $arFieldsForType["ID"],
                    "SITE_ID" => $this->ID_SITE,
                    "GROUP_ID" => $permissions, // массив прав группам пользовотелей к инфоблоку
                    "FIELDS" => Array(
                        "CODE" => Array(
                            "IS_REQUIRED" => "Y",
                            "DEFAULT_VALUE" => Array(
                                "TRANS_CASE" => "L",
                                "UNIQUE" => "Y",
                                "TRANSLITERATION" => "Y",
                                "TRANS_SPACE" => "-",
                                "TRANS_OTHER" => "-"
                            )
                        )
                    )
                );

                // если добавление инфоблока прошло успешно добавляем к нему свойства
                if ($this->iblockId = $this->AddIblock($arFieldsForIblock)){

                    $this->all_log_wm('инфоблок успешно добавлен переходим к добавлению свойств');

                    //добавление свойств наш инфоблок
                    $this->AddPropIblock($this->iblockId);

                    return true;

                } else{

                    $this->all_log_wm('проблема с добавленим инфоблока в методе AddIblock добавление не выполенно, завершаем скрипт');

                    // добавляем ошибку в обработчик, битрикс сам ее покажет
                    $APPLICATION->ThrowException(
                        Loc::getMessage("YANDEX_POINT_MODULE_IBLOCK_NOT_INSTALLED")
                    );

                    return false;
                }

            } else{

                // ошибка в APPLICATION->ThrowException добавлена в функции AddIblockType

                $this->all_log_wm('тип '.$this->iblockType.' инфоблока НЕ СОЗДАН, завершаеться выполнение');

                return false;
            }

        }

        return false;

    }

    // создание типа инфоблока
    public function AddIblockType($arFieldsForType){

        global $DB;
        global $APPLICATION; // для работы переадресации в случае ошибки

        CModule::IncludeModule("iblock"); //для работы с инфоблоком

        $iblockType = $arFieldsForType["ID"];

        // Работа с типом инфоблока
        // проверяем наличие нужного типа инфоблока
        $db_iblock_type = CIBlockType::GetList(Array("SORT" => "ASC"), Array("ID" => $iblockType));

        // если его нет - создаём
        if (!$ar_iblock_type = $db_iblock_type->Fetch()){

            $obBlocktype = new CIBlockType;

            $DB->StartTransaction();

            $resIBT = $obBlocktype->Add($arFieldsForType);

            if (!$resIBT){

                $DB->Rollback();

                // добавляем ошибку в обработчик, битрикс сам ее покажет
                $APPLICATION->ThrowException(
                    'Error: '.$obBlocktype->LAST_ERROR.''
                );

                return false;


            }else{

                $DB->Commit();

                return true;

            }

        } else {

            // инфоблок уже существует, можно в него писать
            return true;

        }

        return $iblockType;

    }

    // функция добавления инфоблока
    public function AddIblock($arFieldsIB){

        if(CModule::IncludeModule("iblock")) {

            $this->all_log_wm('iblock подключен метод AddIblock');

        } else {

            $this->all_log_wm('ошобка, iblock НЕ подключен метод AddIblock, дальнейшее выполнение прерванно');

            return false;

        };

        $iblockCode = $arFieldsIB["CODE"];
        $iblockType = $arFieldsIB["TYPE"];

        $ib = new CIBlock;

        // проверка на наличие создание/обновление
        $resIBE = CIBlock::GetList(Array(), Array('TYPE' => $iblockType, "CODE" => $iblockCode));

        if ($ar_resIBE = $resIBE->Fetch()){

            $this->all_log_wm('проблема желаемый код инфоблока занят, остановка выполнения скрипта, поидее такого быть не должно т.к должно отработать удаление');

            return false; // желаемый код занят, вроде как не должен отработать т.к удаление есть в начале

        }else{

            $ID = $ib->Add($arFieldsIB);
            $iblockID = $ID;

        }

        return $iblockID;
    }

    // добавление свойств в инфоблока
    public function AddProp($arFieldsProp){

        if(CModule::IncludeModule("iblock")) {

            $this->all_log_wm('iblock подключен метод AddProp');

        } else {

            $this->all_log_wm('ошобка, iblock НЕ подключен метод AddProp, дальнейшее выполнение прерванно');

            return false;

        };

        $ibp = new CIBlockProperty;
        $propID = $ibp->Add($arFieldsProp);

        if($propID) {

            $this->all_log_wm('добавление свойства прошло успешно успешно');

            return $propID;

        } else {

            $this->all_log_wm('проблема с добавлением свойства');
            $this->all_log_wm($arFieldsProp);

            return false;

        }

    }

    // добавление свойств в инфоблока вызов
    public function AddPropIblock($iblockID) {

        if(CModule::IncludeModule("iblock")) {

            $this->all_log_wm('iblock подключен метод AddPropIblock');

        } else {

            $this->all_log_wm('ошобка, iblock НЕ подключен метод AddPropIblock, дальнейшее выполнение прерванно');

            return false;

        };

        $arFieldsProp = Array(
            "NAME" => Loc::getMessage('YANDEX_POINT_MODULE_IBLOCK_PROP_NAME_1'), //название свойства
            "ACTIVE" => "Y", // активность свойства
            "SORT" => "100", // индекс сортировки
            "MULTIPLE" => "N", // флаг множественного
            "CODE" => "ADRESS", // код свойства
            "PROPERTY_TYPE" => "S", // тип свойства, S строка
            //"USER_TYPE" => "UserID",
            "IBLOCK_ID" => $iblockID // ID инфоблка к которому привязываем свойство
        );

        // добавляем свойство в инфоблок
        if($this->AddProp($arFieldsProp)) {

            // все ок идем дальше

        } else {

            return false; // перкращаем выполнение, ошибка будет добавлена в лог через метод AddProp, для даигностики достатоноч

        }

        $arFieldsProp = Array(
            "NAME" => Loc::getMessage('YANDEX_POINT_MODULE_IBLOCK_PROP_NAME_2'), //название свойства
            "ACTIVE" => "Y", // активность свойства
            "SORT" => "200", // индекс сортировки
            "MULTIPLE" => "N", // флаг множественного
            "CODE" => "TIMEWORK", // код свойства
            "PROPERTY_TYPE" => "S", // тип свойства, S строка
            //"USER_TYPE" => "UserID",
            "IBLOCK_ID" => $iblockID // ID инфоблка к которому привязываем свойство
        );

        // добавляем свойство в инфоблок
        if($this->AddProp($arFieldsProp)) {

            // все ок идем дальше

        } else {

            return false; // перкращаем выполнение, ошибка будет добавлена в лог через метод AddProp, для даигностики достатоноч

        }

        $arFieldsProp = Array(
            "NAME" => Loc::getMessage('YANDEX_POINT_MODULE_IBLOCK_PROP_NAME_3'), //название свойства
            "ACTIVE" => "Y", // активность свойства
            "SORT" => "300", // индекс сортировки
            "MULTIPLE" => "Y", // флаг множественного
            "CODE" => "PHONE", // код свойства
            "PROPERTY_TYPE" => "S", // тип свойства, S строка
            //"USER_TYPE" => "UserID",
            "IBLOCK_ID" => $iblockID // ID инфоблка к которому привязываем свойство
        );

        // добавляем свойство в инфоблок
        if($this->AddProp($arFieldsProp)) {

            // все ок идем дальше

        } else {

            return false; // перкращаем выполнение, ошибка будет добавлена в лог через метод AddProp, для даигностики достатоноч

        }

        $arFieldsProp = Array(
            "NAME" => Loc::getMessage('YANDEX_POINT_MODULE_IBLOCK_PROP_NAME_4'), //название свойства
            "ACTIVE" => "Y", // активность свойства
            "SORT" => "400", // индекс сортировки
            "MULTIPLE" => "N", // флаг множественного
            "CODE" => "COORDS", // код свойства
            "PROPERTY_TYPE" => "S", // тип свойства, S строка
            //"USER_TYPE" => "UserID",
            "IBLOCK_ID" => $iblockID // ID инфоблка к которому привязываем свойство
        );

        // добавляем свойство в инфоблок
        if($this->AddProp($arFieldsProp)) {

            // все ок идем дальше

        } else {

            return false; // перкращаем выполнение, ошибка будет добавлена в лог через метод AddProp, для даигностики достатоноч

        }

        $arFieldsProp = Array(
            "NAME" => Loc::getMessage('YANDEX_POINT_MODULE_IBLOCK_PROP_NAME_5'), //название свойства
            "ACTIVE" => "Y", // активность свойства
            "SORT" => "500", // индекс сортировки
            "MULTIPLE" => "N", // флаг множественного
            "CODE" => "SIZE_ICO", // код свойства
            "PROPERTY_TYPE" => "S", // тип свойства, S строка
            //"USER_TYPE" => "UserID",
            "IBLOCK_ID" => $iblockID // ID инфоблка к которому привязываем свойство
        );

        // добавляем свойство в инфоблок
        if($this->AddProp($arFieldsProp)) {

            // все ок идем дальше

        } else {

            return false; // перкращаем выполнение, ошибка будет добавлена в лог через метод AddProp, для даигностики достатоноч

        }


        $arFieldsProp = Array(
            "NAME" => Loc::getMessage('YANDEX_POINT_MODULE_IBLOCK_PROP_NAME_6'), //название свойства
            "ACTIVE" => "Y", // активность свойства
            "SORT" => "600", // индекс сортировки
            "MULTIPLE" => "N", // флаг множественного
            "CODE" => "CODEPLACE", // код свойства
            "PROPERTY_TYPE" => "S",
            "USER_TYPE" => "HTML", // поле типа HTML
            "IBLOCK_ID" => $iblockID // ID инфоблка к которому привязываем свойство
        );

        // добавляем свойство в инфоблок
        if($this->AddProp($arFieldsProp)) {

            // все ок идем дальше

        } else {

            return false; // перкращаем выполнение, ошибка будет добавлена в лог через метод AddProp, для даигностики достатоноч

        }

        $arFieldsProp = Array(
            "NAME" => Loc::getMessage('YANDEX_POINT_MODULE_IBLOCK_PROP_NAME_7'), //название свойства
            "ACTIVE" => "Y", // активность свойства
            "SORT" => "700", // индекс сортировки
            "MULTIPLE" => "N", // флаг множественного
            "CODE" => "TEMPLIMGPLACE", // код свойства
            "PROPERTY_TYPE" => "S", // тип свойства, S строка
            //"USER_TYPE" => "UserID",
            "IBLOCK_ID" => $iblockID // ID инфоблка к которому привязываем свойство
        );

        // добавляем свойство в инфоблок
        if($this->AddProp($arFieldsProp)) {

            // все ок идем дальше

        } else {

            return false; // перкращаем выполнение, ошибка будет добавлена в лог через метод AddProp, для даигностики достатоноч

        }

        $arFieldsProp = Array(
            "NAME" => Loc::getMessage('YANDEX_POINT_MODULE_IBLOCK_PROP_NAME_8'), //название свойства
            "ACTIVE" => "Y", // активность свойства
            "SORT" => "800", // индекс сортировки
            "MULTIPLE" => "N", // флаг множественного
            "CODE" => "COLORIMGPLACE", // код свойства
            "PROPERTY_TYPE" => "S", // тип свойства, S строка
            //"USER_TYPE" => "UserID",
            "IBLOCK_ID" => $iblockID // ID инфоблка к которому привязываем свойство
        );

        // добавляем свойство в инфоблок
        if($this->AddProp($arFieldsProp)) {

            // все ок идем дальше

        } else {

            return false; // перкращаем выполнение, ошибка будет добавлена в лог через метод AddProp, для даигностики достатоноч

        }


    }

    // удаление типа инфоблока
    public function unInstallIblock($iblockType) {

        global $DB;
        global $APPLICATION; // для работы переадресации в случае ошибки

        CModule::IncludeModule("iblock");

        $DB->StartTransaction();

        if (!CIBlockType::Delete($iblockType)){

            $DB->Rollback();

            // добавляем ошибку в обработчик, битрикс сам ее покажет
            $APPLICATION->ThrowException(
                Loc::getMessage("YANDEX_POINT_MODULE_IBLOCK_TYPE_DELETE_ERROR")
            );

            return false;

        } else {

            $DB->Commit();

            return true;

        }

    }

    // метод вернет список сайтов в системе
    private function get_site($site_url = false) {

        $arSite = array();

        if($site_url == false) {

            $rsSites = CSite::GetList($by="sort", $order="desc", Array("ACTIVE"=>"Y"));

        } else {

            $rsSites = CSite::GetList($by="sort", $order="desc", Array("SERVER_NAME" => $site_url, "ACTIVE"=>"Y"));

            while ($arSite = $rsSites->Fetch())
            {
                return $arSite;
            }

            return false;

        }

        while ($res = $rsSites->Fetch())
        {
            $arSite[] = $res;
        }

        return $arSite;

    }

    public function all_log_wm($to_log) {

        if(!$this->debug) {

            // если debug flase выходим из функции, отладка отключена

            return false;

        }

        //see var debug

        $to_log; // может быть в том числе и массивом

        $ff='';
        $ff .= 'LOG: '.print_r($to_log,true);

        AddMessage2Log('NEW2'.$ff);


    }



}
