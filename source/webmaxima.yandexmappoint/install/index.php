<?php

use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc; // ��� ������ � �����������
use Bitrix\Main\ModuleManager;
use \Bitrix\Main\Config\Option; // ��� ������ � ������� ������
//use Bex\D7dull\ExampleTable; � lib ��������� ��

//�������� ��������� � ��������� ������ (���� ��� ��������� ���� �������)
defined('ADMIN_MODULE_NAME') or define('ADMIN_MODULE_NAME', 'webmaxima.yandexmappoint');

Loc::loadMessages(__FILE__); // ��������� ��������� �� lang

class webmaxima_yandexmappoint extends CModule
{

    var $iblockType = 'wm_yandex_adress';
    var $iblockXMLID = "wm_yandexAdress_xml_id_1";
    var $iblockCODE = "wm_yandex_adress";
    var $iblockId = '';

    // ������������� ������, ����������� � ������ ���� ����������� ���
    var $MODULE_ID = 'webmaxima.yandexmappoint'; // MODULE_ID ������ ���� ������ ����� � __construct �� �������� �.� ������� �� ��������� �� ���������

    var $ID_SITE = false; // ������������� ����� ��� ��������� ������

    // ������ ���� ����������� LOG_FILENAME � dbconn.php
    var $debug = true; // or false ��������� ��� ������� ���� ����� �� ������ � ��� � �����


    public function __construct()
    {
        $arModuleVersion = array();

        include __DIR__ . '/version.php'; // ��������� ������������� ����� � ������� ������, ���������� �� ������ �� ������ � ������

        if (is_array($arModuleVersion) && array_key_exists('VERSION', $arModuleVersion))
        {
            $this->MODULE_VERSION = $arModuleVersion['VERSION'];
            $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        }

        $this->MODULE_NAME = Loc::getMessage('YANDEX_POINT_MODULE_NAME'); // �������� ������
        $this->MODULE_DESCRIPTION = Loc::getMessage('YANDEX_POINT_DESCRIPTION'); // �������� ������
        $this->MODULE_GROUP_RIGHTS = 'N';
        $this->PARTNER_NAME = Loc::getMessage('YANDEX_POINT_PARTNER_NAME'); // �������� ����� ��������
        $this->PARTNER_URI = 'http://www.web-maxima.ru/'; // url ����� ��������

    }

    // ��������� ������� ������ ������������ ����������� � �������� ��������� ������
    public function doInstall()
    {

        global $APPLICATION;

        $status_install = false;

        $arSite = $this->get_site();

        if(count($arSite) > 1) {

            // ����� �������� ����� � ����������� �� URL
            $arSite = $this->get_site($_SERVER['HTTP_HOST']);

            $this->ID_SITE = $arSite['ID'];

        } else {

            $this->ID_SITE = $arSite[0]['ID'];

        }

        if($this->ID_SITE == false or $this->ID_SITE == '') {

            $this->all_log_wm('�� ������� ���������� ����');
            return false;
        }

        // �������� �� �� ��� ������ bitrix ���� 14 �.� ��� ���� ������� � ������ ��� API �������
        if(CheckVersion(ModuleManager::getVersion("main"), "14.00.00")){

            $this->InstallFiles(); // ������� ��������� ������

            //$this->InstallDB(); // ������� ��������� ���� ������

            // ������� ��������� ����������
            if($this->InstallIblock()) {

                $status_install = true;

            } else {

                $status_install = false;

            }

        }else{

            // ����������� ���������� �� ������ ������ ��������, ���������� ������� ������� (� ������ 3.3.21)
            $APPLICATION->ThrowException(
                Loc::getMessage("YANDEX_POINT_ERROR_VERSION")
            );

            $status_install = false;

        }

        if($status_install) {

            ModuleManager::registerModule($this->MODULE_ID); // ������������ ��� ������

            // ���������� ����� ID ����������  ��������� � ������
            COption::SetOptionString($this->MODULE_ID, 'IBLOCK_YMAP', $this->iblockId);

            $this->InstallEvents(); // ������������ ������� ������� ��������� � ������

            // ���������� ����� ID ��������� � �������� �����
            COption::SetOptionString($this->MODULE_ID, 'dataIblockId', $this->iblockId);

            // ���������� ����� ���������� ������ �����
            COption::SetOptionString($this->MODULE_ID, 'mapCenter', '51.7933,55.1975');

            // ���������� ����� ��������� ����������� �����
            COption::SetOptionString($this->MODULE_ID, 'mapZoom', '9');

            // ���������� ����� ��������� ������ ����� (px)
            COption::SetOptionString($this->MODULE_ID, 'mapWidht', '600px');

            // ���������� ����� ��������� ������ ����� (px)
            COption::SetOptionString($this->MODULE_ID, 'mapHeight', '500px');

            // ���������� ����� ��������� ������������� ������ ��� ��������� ���������
            COption::SetOptionString($this->MODULE_ID, 'mapScroll', 'false');

            // ������� �� ��������� ��� ���������
            $APPLICATION->IncludeAdminFile(
                Loc::getMessage("YANDEX_POINT_INSTALL_TITLE")." \"".Loc::getMessage("YANDEX_POINT_MODULE_NAME")."\"", //��������� ��������.
                __DIR__."/step.php"
            );

        }

        return false;

    }

    // ��������� ������� ������ ������������ ����������� � �������� �������� ������
    public function doUninstall()
    {

        //$this->uninstallDB(); // �������� ���� ������

        $this->UnInstallFiles(); // �������� ������

        $this->unInstallIblock($this->iblockType); // �������� ��������� (������ ��� ���� ������� �� ������ � ��� ��������)

        // ������� ����� ������
        Option::delete(ADMIN_MODULE_NAME); // �������� ���� ����� ����� �������

        ModuleManager::unRegisterModule($this->MODULE_ID);

    }

    public function installDB()
    {

        return false;

        /* ���� ����� ���������� ����� ��
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

    // ������� ��������� ������
    public function InstallFiles(){

        // ���������� ����������� ������ �������� ��� ���������
        //CopyDirFiles(__DIR__.'/admin/', $_SERVER['DOCUMENT_ROOT'].'/bitrix/admin', true);

        // ���������� ����������� ������ �����������
        CopyDirFiles(__DIR__.'/components/', $_SERVER['DOCUMENT_ROOT'].'/bitrix/components', true, true);


        // ��� ���� ������������ js � css ����� ��� ����� ����������� ������ �.� ������ ����� � ��� ����� ��������
        // ������ ��� ����� ������ ������� ������
        // ����������� Css ������ � �������� ������ bitrix
        //CopyDirFiles(__DIR__.'/css/', $_SERVER['DOCUMENT_ROOT'].'/bitrix/css/'.$this->MODULE_ID.'/', true, true);

        // ����������� js ������ � �������� ������ bitrix
        //CopyDirFiles(__DIR__.'/js/', $_SERVER['DOCUMENT_ROOT'].'/bitrix/js/'.$this->MODULE_ID.'/', true, true);

        return false;

    }

    // ������� �������� ������
    public function UnInstallFiles(){

        // �������� ������ ��������� � ���������
        //DeleteDirFiles(__DIR__.'/admin/', $_SERVER['DOCUMENT_ROOT'].'/bitrix/admin');

        // �������� ������ �����������
        DeleteDirFilesEx("/bitrix/components/webmaxima/yandexmap.pointview");

        // �������� ��������� �����������
        //DeleteDirFilesEx('/bitrix/wizards/'.self::partnerName.'/'.self::solutionName.'/');

        return true;
    }

    // ��������� ��������� �����
    public function InstallIblock(){

        global $APPLICATION; // ��� ������ ������������� � ������ ������

        // ���� ������ �������� ����������� - �� �������� ��������� ���������� � ��������
        if(!CModule::IncludeModule("iblock")) {

            $this->all_log_wm('�� ��������� ������ iblock, ����� InstallIblock');

            return false;

        }

        $iblockID = true; // ������ ���������� ���������

        // ����������� ������������� ������ ��������� �� ������ ����������
        $rsIBlock = CIBlock::GetList(array(), array("TYPE" => $this->iblockType, "SITE_ID" => $this->ID_SITE));

        // ��������� ������������� ���������
        // ����������� ���� ������ ���� �������� ���� (����������� �� �� ��� � ����� ��������� ��������� ����� �� ������)
        if ($arIBlock = $rsIBlock->Fetch()) {

            $this->all_log_wm('Iblock ������, ������ ���� ������');

            $iblockID = false; //�������� ����, ��� ��������� �������� ����� ������

            // ���� �������� ������ �� �� ��� ������
            if ($arIBlock["ID"]) {

                if(CIBlock::Delete($arIBlock["ID"])) { // �������� ��������� � �������

                    $this->all_log_wm('Iblock ������� ������');
                    $iblockID = true; // �������� ������� ������

                } else {

                    $this->all_log_wm('Iblock �������� � ���������');
                    $iblockID = false; // �������� ������� ������

                }

            }
        }

        if($iblockID){

            // ��� ���� ����������
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

            // ������� ��� ���������
            // ���� ������� ��� ���������, ������ ���������
            if ($this->AddIblockType($arFieldsForType)){

                $this->all_log_wm('��� '.$this->iblockType.' ��������� ������� ������');

                $permissions = array("1" => "X", "2" => "R"); // ���������� ������� ����

                //�������� ������ content_editor ������ �� ID
                $dbGroup = CGroup::GetList($by = "", $order = "", array("STRING_ID" => "content_editor"));

                // ���������� ��� ������ ������ �������� ������ � ������� ��������
                if($arGroup = $dbGroup->Fetch()){
                    $permissions[$arGroup["ID"]] = "W";

                    $this->all_log_wm('������ ���� ������� ������� '.$arGroup["ID"].' = w');

                };

                // ��������� ��� �������� ���������
                $arFieldsForIblock = Array(
                    "ACTIVE" => "Y",
                    "NAME" => Loc::getMessage('YANDEX_POINT_MODULE_IBLOCK_NAME'),
                    "CODE" => $this->iblockCODE, // ��� � ��������
                    "IBLOCK_TYPE_ID" => $arFieldsForType["ID"],
                    "SITE_ID" => $this->ID_SITE,
                    "GROUP_ID" => $permissions, // ������ ���� ������� ������������� � ���������
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

                // ���� ���������� ��������� ������ ������� ��������� � ���� ��������
                if ($this->iblockId = $this->AddIblock($arFieldsForIblock)){

                    $this->all_log_wm('�������� ������� �������� ��������� � ���������� �������');

                    //���������� ������� ��� ��������
                    $this->AddPropIblock($this->iblockId);

                    return true;

                } else{

                    $this->all_log_wm('�������� � ���������� ��������� � ������ AddIblock ���������� �� ���������, ��������� ������');

                    // ��������� ������ � ����������, ������� ��� �� �������
                    $APPLICATION->ThrowException(
                        Loc::getMessage("YANDEX_POINT_MODULE_IBLOCK_NOT_INSTALLED")
                    );

                    return false;
                }

            } else{

                // ������ � APPLICATION->ThrowException ��������� � ������� AddIblockType

                $this->all_log_wm('��� '.$this->iblockType.' ��������� �� ������, ������������ ����������');

                return false;
            }

        }

        return false;

    }

    // �������� ���� ���������
    public function AddIblockType($arFieldsForType){

        global $DB;
        global $APPLICATION; // ��� ������ ������������� � ������ ������

        CModule::IncludeModule("iblock"); //��� ������ � ����������

        $iblockType = $arFieldsForType["ID"];

        // ������ � ����� ���������
        // ��������� ������� ������� ���� ���������
        $db_iblock_type = CIBlockType::GetList(Array("SORT" => "ASC"), Array("ID" => $iblockType));

        // ���� ��� ��� - ������
        if (!$ar_iblock_type = $db_iblock_type->Fetch()){

            $obBlocktype = new CIBlockType;

            $DB->StartTransaction();

            $resIBT = $obBlocktype->Add($arFieldsForType);

            if (!$resIBT){

                $DB->Rollback();

                // ��������� ������ � ����������, ������� ��� �� �������
                $APPLICATION->ThrowException(
                    'Error: '.$obBlocktype->LAST_ERROR.''
                );

                return false;


            }else{

                $DB->Commit();

                return true;

            }

        } else {

            // �������� ��� ����������, ����� � ���� ������
            return true;

        }

        return $iblockType;

    }

    // ������� ���������� ���������
    public function AddIblock($arFieldsIB){

        if(CModule::IncludeModule("iblock")) {

            $this->all_log_wm('iblock ��������� ����� AddIblock');

        } else {

            $this->all_log_wm('������, iblock �� ��������� ����� AddIblock, ���������� ���������� ���������');

            return false;

        };

        $iblockCode = $arFieldsIB["CODE"];
        $iblockType = $arFieldsIB["TYPE"];

        $ib = new CIBlock;

        // �������� �� ������� ��������/����������
        $resIBE = CIBlock::GetList(Array(), Array('TYPE' => $iblockType, "CODE" => $iblockCode));

        if ($ar_resIBE = $resIBE->Fetch()){

            $this->all_log_wm('�������� �������� ��� ��������� �����, ��������� ���������� �������, ������ ������ ���� �� ������ �.� ������ ���������� ��������');

            return false; // �������� ��� �����, ����� ��� �� ������ ���������� �.� �������� ���� � ������

        }else{

            $ID = $ib->Add($arFieldsIB);
            $iblockID = $ID;

        }

        return $iblockID;
    }

    // ���������� ������� � ���������
    public function AddProp($arFieldsProp){

        if(CModule::IncludeModule("iblock")) {

            $this->all_log_wm('iblock ��������� ����� AddProp');

        } else {

            $this->all_log_wm('������, iblock �� ��������� ����� AddProp, ���������� ���������� ���������');

            return false;

        };

        $ibp = new CIBlockProperty;
        $propID = $ibp->Add($arFieldsProp);

        if($propID) {

            $this->all_log_wm('���������� �������� ������ ������� �������');

            return $propID;

        } else {

            $this->all_log_wm('�������� � ����������� ��������');
            $this->all_log_wm($arFieldsProp);

            return false;

        }

    }

    // ���������� ������� � ��������� �����
    public function AddPropIblock($iblockID) {

        if(CModule::IncludeModule("iblock")) {

            $this->all_log_wm('iblock ��������� ����� AddPropIblock');

        } else {

            $this->all_log_wm('������, iblock �� ��������� ����� AddPropIblock, ���������� ���������� ���������');

            return false;

        };

        $arFieldsProp = Array(
            "NAME" => Loc::getMessage('YANDEX_POINT_MODULE_IBLOCK_PROP_NAME_1'), //�������� ��������
            "ACTIVE" => "Y", // ���������� ��������
            "SORT" => "100", // ������ ����������
            "MULTIPLE" => "N", // ���� ��������������
            "CODE" => "ADRESS", // ��� ��������
            "PROPERTY_TYPE" => "S", // ��� ��������, S ������
            //"USER_TYPE" => "UserID",
            "IBLOCK_ID" => $iblockID // ID �������� � �������� ����������� ��������
        );

        // ��������� �������� � ��������
        if($this->AddProp($arFieldsProp)) {

            // ��� �� ���� ������

        } else {

            return false; // ���������� ����������, ������ ����� ��������� � ��� ����� ����� AddProp, ��� ����������� ����������

        }

        $arFieldsProp = Array(
            "NAME" => Loc::getMessage('YANDEX_POINT_MODULE_IBLOCK_PROP_NAME_2'), //�������� ��������
            "ACTIVE" => "Y", // ���������� ��������
            "SORT" => "200", // ������ ����������
            "MULTIPLE" => "N", // ���� ��������������
            "CODE" => "TIMEWORK", // ��� ��������
            "PROPERTY_TYPE" => "S", // ��� ��������, S ������
            //"USER_TYPE" => "UserID",
            "IBLOCK_ID" => $iblockID // ID �������� � �������� ����������� ��������
        );

        // ��������� �������� � ��������
        if($this->AddProp($arFieldsProp)) {

            // ��� �� ���� ������

        } else {

            return false; // ���������� ����������, ������ ����� ��������� � ��� ����� ����� AddProp, ��� ����������� ����������

        }

        $arFieldsProp = Array(
            "NAME" => Loc::getMessage('YANDEX_POINT_MODULE_IBLOCK_PROP_NAME_3'), //�������� ��������
            "ACTIVE" => "Y", // ���������� ��������
            "SORT" => "300", // ������ ����������
            "MULTIPLE" => "Y", // ���� ��������������
            "CODE" => "PHONE", // ��� ��������
            "PROPERTY_TYPE" => "S", // ��� ��������, S ������
            //"USER_TYPE" => "UserID",
            "IBLOCK_ID" => $iblockID // ID �������� � �������� ����������� ��������
        );

        // ��������� �������� � ��������
        if($this->AddProp($arFieldsProp)) {

            // ��� �� ���� ������

        } else {

            return false; // ���������� ����������, ������ ����� ��������� � ��� ����� ����� AddProp, ��� ����������� ����������

        }

        $arFieldsProp = Array(
            "NAME" => Loc::getMessage('YANDEX_POINT_MODULE_IBLOCK_PROP_NAME_4'), //�������� ��������
            "ACTIVE" => "Y", // ���������� ��������
            "SORT" => "400", // ������ ����������
            "MULTIPLE" => "N", // ���� ��������������
            "CODE" => "COORDS", // ��� ��������
            "PROPERTY_TYPE" => "S", // ��� ��������, S ������
            //"USER_TYPE" => "UserID",
            "IBLOCK_ID" => $iblockID // ID �������� � �������� ����������� ��������
        );

        // ��������� �������� � ��������
        if($this->AddProp($arFieldsProp)) {

            // ��� �� ���� ������

        } else {

            return false; // ���������� ����������, ������ ����� ��������� � ��� ����� ����� AddProp, ��� ����������� ����������

        }

        $arFieldsProp = Array(
            "NAME" => Loc::getMessage('YANDEX_POINT_MODULE_IBLOCK_PROP_NAME_5'), //�������� ��������
            "ACTIVE" => "Y", // ���������� ��������
            "SORT" => "500", // ������ ����������
            "MULTIPLE" => "N", // ���� ��������������
            "CODE" => "SIZE_ICO", // ��� ��������
            "PROPERTY_TYPE" => "S", // ��� ��������, S ������
            //"USER_TYPE" => "UserID",
            "IBLOCK_ID" => $iblockID // ID �������� � �������� ����������� ��������
        );

        // ��������� �������� � ��������
        if($this->AddProp($arFieldsProp)) {

            // ��� �� ���� ������

        } else {

            return false; // ���������� ����������, ������ ����� ��������� � ��� ����� ����� AddProp, ��� ����������� ����������

        }


        $arFieldsProp = Array(
            "NAME" => Loc::getMessage('YANDEX_POINT_MODULE_IBLOCK_PROP_NAME_6'), //�������� ��������
            "ACTIVE" => "Y", // ���������� ��������
            "SORT" => "600", // ������ ����������
            "MULTIPLE" => "N", // ���� ��������������
            "CODE" => "CODEPLACE", // ��� ��������
            "PROPERTY_TYPE" => "S",
            "USER_TYPE" => "HTML", // ���� ���� HTML
            "IBLOCK_ID" => $iblockID // ID �������� � �������� ����������� ��������
        );

        // ��������� �������� � ��������
        if($this->AddProp($arFieldsProp)) {

            // ��� �� ���� ������

        } else {

            return false; // ���������� ����������, ������ ����� ��������� � ��� ����� ����� AddProp, ��� ����������� ����������

        }

        $arFieldsProp = Array(
            "NAME" => Loc::getMessage('YANDEX_POINT_MODULE_IBLOCK_PROP_NAME_7'), //�������� ��������
            "ACTIVE" => "Y", // ���������� ��������
            "SORT" => "700", // ������ ����������
            "MULTIPLE" => "N", // ���� ��������������
            "CODE" => "TEMPLIMGPLACE", // ��� ��������
            "PROPERTY_TYPE" => "S", // ��� ��������, S ������
            //"USER_TYPE" => "UserID",
            "IBLOCK_ID" => $iblockID // ID �������� � �������� ����������� ��������
        );

        // ��������� �������� � ��������
        if($this->AddProp($arFieldsProp)) {

            // ��� �� ���� ������

        } else {

            return false; // ���������� ����������, ������ ����� ��������� � ��� ����� ����� AddProp, ��� ����������� ����������

        }

        $arFieldsProp = Array(
            "NAME" => Loc::getMessage('YANDEX_POINT_MODULE_IBLOCK_PROP_NAME_8'), //�������� ��������
            "ACTIVE" => "Y", // ���������� ��������
            "SORT" => "800", // ������ ����������
            "MULTIPLE" => "N", // ���� ��������������
            "CODE" => "COLORIMGPLACE", // ��� ��������
            "PROPERTY_TYPE" => "S", // ��� ��������, S ������
            //"USER_TYPE" => "UserID",
            "IBLOCK_ID" => $iblockID // ID �������� � �������� ����������� ��������
        );

        // ��������� �������� � ��������
        if($this->AddProp($arFieldsProp)) {

            // ��� �� ���� ������

        } else {

            return false; // ���������� ����������, ������ ����� ��������� � ��� ����� ����� AddProp, ��� ����������� ����������

        }


    }

    // �������� ���� ���������
    public function unInstallIblock($iblockType) {

        global $DB;
        global $APPLICATION; // ��� ������ ������������� � ������ ������

        CModule::IncludeModule("iblock");

        $DB->StartTransaction();

        if (!CIBlockType::Delete($iblockType)){

            $DB->Rollback();

            // ��������� ������ � ����������, ������� ��� �� �������
            $APPLICATION->ThrowException(
                Loc::getMessage("YANDEX_POINT_MODULE_IBLOCK_TYPE_DELETE_ERROR")
            );

            return false;

        } else {

            $DB->Commit();

            return true;

        }

    }

    // ����� ������ ������ ������ � �������
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

            // ���� debug flase ������� �� �������, ������� ���������

            return false;

        }

        //see var debug

        $to_log; // ����� ���� � ��� ����� � ��������

        $ff='';
        $ff .= 'LOG: '.print_r($to_log,true);

        AddMessage2Log('NEW2'.$ff);


    }



}
